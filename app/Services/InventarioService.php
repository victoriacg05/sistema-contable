<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Centraliza el registro de movimientos de inventario para que compras y
 * ventas mantengan sincronizado el módulo de Inventario automáticamente,
 * sin intervención manual del usuario.
 */
class InventarioService
{
    /**
     * Registra un movimiento de entrada o salida de inventario asociado a la
     * transacción que lo originó (compra a proveedor o venta a cliente).
     *
     * No ajusta el stock del producto: eso lo hace el flujo que llama a este
     * servicio. Aquí solo se deja la traza en movimientos_inventario para que
     * el módulo de Inventario refleje la operación.
     */
    public static function registrarMovimiento(
        int $productoId,
        string $tipoNombre,
        int $cantidad,
        string $descripcion = '',
        ?string $referencia = null
    ): void {
        $tipoId = DB::table('tipos_movimiento_inventario')
            ->whereRaw('LOWER(nombre) = ?', [strtolower($tipoNombre)])
            ->value('id');

        if (! $tipoId) {
            $tipoId = DB::table('tipos_movimiento_inventario')->insertGetId([
                'nombre' => $tipoNombre,
                'descripcion' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $referencia = $referencia ?: ('MOV-' . now()->format('YmdHis'));

        DB::table('movimientos_inventario')->insert([
            'referencia_movimiento' => $referencia,
            'producto_id' => $productoId,
            'usuario_id' => Auth::id(),
            'tipo_movimiento_inventario_id' => $tipoId,
            'cantidad' => $cantidad,
            'descripcion' => $descripcion,
            'fecha' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
