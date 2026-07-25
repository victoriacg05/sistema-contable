<?php

namespace App\Http\Controllers;

use App\Models\CuentaCobrar;
use App\Models\PagoCuentaCobrar;
use App\Models\PlazoVenta;
use App\Models\MetodoPago;
use App\Models\Estado;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CuentaCobrarController extends Controller
{
    public function index()
    {
        $cuentas = CuentaCobrar::with(['cliente', 'estado'])
            ->orderByDesc('created_at')
            ->orderByDesc('fecha_emision')
            ->orderByDesc('numero_factura')
            ->get();

        return view('cuentas-cobrar.index', compact('cuentas'));
    }

    public function createPago($numero_factura, $cliente_id)
    {
        $cuenta = CuentaCobrar::with(['cliente', 'estado'])
            ->where('numero_factura', $numero_factura)
            ->where('cliente_id', $cliente_id)
            ->firstOrFail();

        $metodosPago = MetodoPago::where('nombre', '!=', 'Crédito')
            ->orderBy('nombre')
            ->get();

        return view('cuentas-cobrar.pago', compact('cuenta', 'metodosPago'));
    }

    public function storePago(Request $request, $numero_factura, $cliente_id)
    {
        $request->validate([
            'monto_pagado' => 'required|numeric|min:1',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $numero_factura, $cliente_id) {
            $cuenta = CuentaCobrar::where('numero_factura', $numero_factura)
                ->where('cliente_id', $cliente_id)
                ->firstOrFail();

            if ($request->monto_pagado > $cuenta->saldo_pendiente) {
                abort(422, 'El monto pagado no puede ser mayor al saldo pendiente.');
            }

            $saldoAnterior = $cuenta->saldo_pendiente;

            PagoCuentaCobrar::create([
                'numero_factura' => $cuenta->numero_factura,
                'cliente_id' => $cuenta->cliente_id,
                'fecha_pago' => now(),
                'monto_pagado' => $request->monto_pagado,
                'metodo_pago_id' => $request->metodo_pago_id,
                'observacion' => $request->observacion,
            ]);

            $nuevoSaldo = $cuenta->saldo_pendiente - $request->monto_pagado;

            $estadoPagado = Estado::where('nombre', 'pagado')->first();
            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();

            CuentaCobrar::where('numero_factura', $cuenta->numero_factura)
                ->where('cliente_id', $cuenta->cliente_id)
                ->update([
                    'saldo_pendiente' => $nuevoSaldo,
                    'estado_id' => $nuevoSaldo <= 0
                        ? ($estadoPagado?->id ?? 2)
                        : ($estadoPendiente?->id ?? 1),
                ]);

            // Distribuir el pago entre las cuotas pendientes (de la más
            // próxima a la más lejana) para mantener actualizado el saldo
            // pendiente de cada plazo de una venta a crédito.
            $montoRestante = $request->monto_pagado;

            $plazos = PlazoVenta::where('numero_factura', $cuenta->numero_factura)
                ->where('cliente_id', $cuenta->cliente_id)
                ->where('saldo_pendiente', '>', 0)
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->get();

            foreach ($plazos as $plazo) {
                if ($montoRestante <= 0) {
                    break;
                }

                $abono = min($montoRestante, (float) $plazo->saldo_pendiente);
                $plazo->saldo_pendiente = (float) $plazo->saldo_pendiente - $abono;
                $plazo->save();

                $montoRestante -= $abono;
            }

            DB::table('historial_saldos')->insert([
                'referencia_documento' => $numero_factura,
                'tipo_documento' => 'cuenta_cobrar',
                'usuario_id' => Auth::id(),
                'saldo_anterior' => $saldoAnterior,
                'monto_movimiento' => $request->monto_pagado,
                'saldo_nuevo' => $nuevoSaldo,
                'fecha' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            BitacoraService::registrar('pago', 'cuentas_cobrar', "Pago de ₡{$request->monto_pagado} a factura $numero_factura");
        });

        return redirect()
            ->route('cuentas-cobrar.index')
            ->with('success', 'Pago registrado correctamente.');
    }
}