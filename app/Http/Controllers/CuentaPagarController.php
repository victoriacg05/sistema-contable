<?php

namespace App\Http\Controllers;

use App\Models\CuentaPagar;
use App\Models\PagoCuentaPagar;
use App\Models\MetodoPago;
use App\Models\Estado;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CuentaPagarController extends Controller
{
    public function index()
    {
        $cuentas = CuentaPagar::with(['proveedor', 'estado'])
            ->orderBy('fecha_vencimiento')
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

        return view('cuentas-pagar.pago', compact('cuenta', 'metodosPago'));
    }

    public function storePago(Request $request, $numero_compra, $proveedor_id)
    {
        $request->validate([
            'monto_pagado' => 'required|numeric|min:1',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'observacion' => 'nullable|string|max:500',
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

            BitacoraService::registrar('pago', 'cuentas_pagar', "Pago de ₡{$request->monto_pagado} a compra $numero_compra");
        });

        return redirect()
            ->route('cuentas-pagar.index')
            ->with('success', 'Pago registrado correctamente.');
    }
}