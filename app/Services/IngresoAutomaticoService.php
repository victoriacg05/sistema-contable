<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IngresoAutomaticoService
{
    public static function registrarVentaContado(
        string $numeroFactura,
        $fecha,
        int $usuarioId,
        int $metodoPagoId,
        float $monto
    ): void {
        self::guardar(
            'AUTO-VENTA-' . $numeroFactura,
            $fecha,
            $usuarioId,
            $metodoPagoId,
            "Venta de contado {$numeroFactura}",
            "Ingreso automático por venta de contado {$numeroFactura}",
            $monto
        );
    }

    public static function registrarCobro(
        string $referenciaPago,
        string $numeroFactura,
        $fecha,
        int $usuarioId,
        int $metodoPagoId,
        float $monto
    ): void {
        self::guardar(
            'AUTO-COBRO-' . $referenciaPago,
            $fecha,
            $usuarioId,
            $metodoPagoId,
            "Cobro de factura {$numeroFactura}",
            "Ingreso automático por cobro de cuenta por cobrar {$numeroFactura}",
            $monto
        );
    }

    public static function eliminarVentaContado(string $numeroFactura): void
    {
        DB::table('ingresos')
            ->where('referencia_ingreso', 'AUTO-VENTA-' . $numeroFactura)
            ->delete();
    }

    public static function esAutomatico(string $referencia): bool
    {
        return str_starts_with($referencia, 'AUTO-');
    }

    private static function guardar(
        string $referencia,
        $fecha,
        int $usuarioId,
        int $metodoPagoId,
        string $origen,
        string $descripcion,
        float $monto
    ): void {
        $fecha = Carbon::parse($fecha)->toDateString();

        DB::table('ingresos')->updateOrInsert(
            [
                'referencia_ingreso' => $referencia,
                'fecha' => $fecha,
                'usuario_id' => $usuarioId,
            ],
            [
                'metodo_pago_id' => $metodoPagoId,
                'origen' => $origen,
                'descripcion' => $descripcion,
                'monto' => round($monto, 2),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
