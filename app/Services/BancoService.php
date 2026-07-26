<?php

namespace App\Services;

use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza los movimientos de las cuentas bancarias (tesorería) para que
 * compras de contado y pagos de cuentas por pagar descuenten el saldo y dejen
 * su traza de forma consistente en todo el sistema.
 */
class BancoService
{
    public static function acreditar(
        CuentaBancaria $cuenta,
        float $monto,
        string $descripcion,
        ?string $referencia = null
    ): MovimientoBancario {
        $saldoAnterior = (float) $cuenta->saldo;
        $saldoNuevo = round($saldoAnterior + $monto, 2);

        $cuenta->saldo = $saldoNuevo;
        $cuenta->save();

        return MovimientoBancario::create([
            'cuenta_bancaria_id' => $cuenta->id,
            'tipo' => 'entrada',
            'monto' => $monto,
            'descripcion' => $descripcion,
            'referencia' => $referencia,
            'saldo_anterior' => $saldoAnterior,
            'saldo_nuevo' => $saldoNuevo,
            'usuario_id' => Auth::id(),
            'fecha' => now(),
        ]);
    }

    /**
     * Descuenta un monto del saldo de una cuenta bancaria y registra el
     * movimiento de salida. Valida fondos suficientes antes de aplicar.
     */
    public static function debitar(
        CuentaBancaria $cuenta,
        float $monto,
        string $descripcion,
        ?string $referencia = null
    ): MovimientoBancario {
        $saldoAnterior = (float) $cuenta->saldo;

        if ($monto > $saldoAnterior + 0.0001) {
            throw ValidationException::withMessages([
                'cuenta_bancaria_id' => 'Fondos insuficientes en «' . $cuenta->banco_nombre
                    . '». Saldo disponible: ₡' . number_format($saldoAnterior, 2)
                    . ', monto requerido: ₡' . number_format($monto, 2) . '.',
            ]);
        }

        $saldoNuevo = round($saldoAnterior - $monto, 2);

        $cuenta->saldo = $saldoNuevo;
        $cuenta->save();

        return MovimientoBancario::create([
            'cuenta_bancaria_id' => $cuenta->id,
            'tipo' => 'salida',
            'monto' => $monto,
            'descripcion' => $descripcion,
            'referencia' => $referencia,
            'saldo_anterior' => $saldoAnterior,
            'saldo_nuevo' => $saldoNuevo,
            'usuario_id' => Auth::id(),
            'fecha' => now(),
        ]);
    }
}
