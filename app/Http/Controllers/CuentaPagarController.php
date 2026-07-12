<?php

namespace App\Http\Controllers;

use App\Models\CuentaPagar;
use App\Models\PagoCuentaPagar;
use App\Models\PlazoCompra;
use App\Models\MetodoPago;
use App\Models\Estado;
use App\Models\CuentaBancaria;
use App\Services\BitacoraService;
use App\Services\AsientoContableService;
use App\Services\BancoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CuentaPagarController extends Controller
{
    public function index()
    {
        $cuentas = CuentaPagar::with(['proveedor', 'estado'])
            ->orderByDesc('fecha_emision')
            ->orderByDesc('numero_compra')
            ->get();

        return view('cuentas-pagar.index', compact('cuentas'));
    }

    public function createPago($numero_compra, $proveedor_id)
    {
        $cuenta = CuentaPagar::with(['proveedor', 'estado'])
            ->where('numero_compra', $numero_compra)
            ->where('proveedor_id', $proveedor_id)
            ->firstOrFail();

        $metodosPago = MetodoPago::where('nombre', '!=', 'Crédito')
            ->orderBy('nombre')
            ->get();

        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('cuentas-pagar.pago', compact('cuenta', 'metodosPago', 'cuentasBancarias'));
    }

    public function storePago(Request $request, $numero_compra, $proveedor_id)
    {
        $request->validate([
            'monto_pagado' => 'required|numeric|min:1',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'observacion' => 'nullable|string|max:500',
        ], [
            'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria desde la cual se realizará el pago.',
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
        ]);

        DB::transaction(function () use ($request, $numero_compra, $proveedor_id) {
            $cuenta = CuentaPagar::where('numero_compra', $numero_compra)
                ->where('proveedor_id', $proveedor_id)
                ->firstOrFail();

            if ($request->monto_pagado > $cuenta->saldo_pendiente) {
                abort(422, 'El monto pagado no puede ser mayor al saldo pendiente.');
            }

            $saldoAnterior = $cuenta->saldo_pendiente;

            PagoCuentaPagar::create([
                'numero_compra' => $cuenta->numero_compra,
                'proveedor_id' => $cuenta->proveedor_id,
                'fecha_pago' => now(),
                'monto_pagado' => $request->monto_pagado,
                'metodo_pago_id' => $request->metodo_pago_id,
                'observacion' => $request->observacion,
            ]);

            $nuevoSaldo = $cuenta->saldo_pendiente - $request->monto_pagado;

            $estadoPagado = Estado::where('nombre', 'pagado')->first();
            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();

            CuentaPagar::where('numero_compra', $cuenta->numero_compra)
                ->where('proveedor_id', $cuenta->proveedor_id)
                ->update([
                    'saldo_pendiente' => $nuevoSaldo,
                    'estado_id' => $nuevoSaldo <= 0
                        ? ($estadoPagado?->id ?? 2)
                        : ($estadoPendiente?->id ?? 1),
                ]);

            // Distribuir el pago entre las cuotas pendientes (de la más
            // próxima a la más lejana) para mantener actualizado el saldo
            // pendiente de cada plazo de una compra a crédito.
            $montoRestante = $request->monto_pagado;

            $plazos = PlazoCompra::where('numero_compra', $cuenta->numero_compra)
                ->where('proveedor_id', $cuenta->proveedor_id)
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
                'referencia_documento' => $numero_compra,
                'tipo_documento' => 'cuenta_pagar',
                'usuario_id' => Auth::id(),
                'saldo_anterior' => $saldoAnterior,
                'monto_movimiento' => $request->monto_pagado,
                'saldo_nuevo' => $nuevoSaldo,
                'fecha' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Afecta el banco solo al momento del pago de la obligación:
            // descuenta el saldo bancario y genera el asiento contable
            // Debe Cuentas por Pagar (2.1.1) / Haber Bancos (1.1.2).
            $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

            BancoService::debitar(
                $cuentaBancaria,
                (float) $request->monto_pagado,
                "Pago de compra {$numero_compra}",
                $numero_compra
            );

            AsientoContableService::generar(now(), "Pago de compra a crédito {$numero_compra}", [
                ['codigo_cuenta' => '2.1.1', 'debe' => $request->monto_pagado, 'haber' => 0, 'descripcion' => "Pago cuenta por pagar {$numero_compra}"],
                ['codigo_cuenta' => '1.1.2', 'debe' => 0, 'haber' => $request->monto_pagado, 'descripcion' => "Pago desde {$cuentaBancaria->banco_nombre}"],
            ]);

            BitacoraService::registrar('pago', 'cuentas_pagar', "Pago de ₡{$request->monto_pagado} a compra $numero_compra");
        });

        $montoFormateado = number_format($request->monto_pagado, 2);

        return redirect()
            ->route('cuentas-pagar.index')
            ->with('success', "Pago de ₡{$montoFormateado} registrado correctamente para la compra {$numero_compra}.");
    }
}