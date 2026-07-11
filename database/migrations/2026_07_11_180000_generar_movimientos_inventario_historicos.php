<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Genera los movimientos de inventario faltantes para las compras a
     * proveedor (Entrada) y las facturas de venta (Salida) que ya existían
     * antes de activar la sincronización automática. Usa insertOrIgnore para
     * no duplicar los movimientos ya registrados por las nuevas transacciones.
     */
    public function up(): void
    {
        $entradaId = DB::table('tipos_movimiento_inventario')
            ->whereRaw('LOWER(nombre) = ?', ['entrada'])
            ->value('id');

        $salidaId = DB::table('tipos_movimiento_inventario')
            ->whereRaw('LOWER(nombre) = ?', ['salida'])
            ->value('id');

        // Entradas por compras a proveedor.
        if ($entradaId) {
            $detallesCompra = DB::table('detalle_compras')
                ->join('compras', function ($join) {
                    $join->on('detalle_compras.numero_compra', '=', 'compras.numero_compra')
                        ->on('detalle_compras.proveedor_id', '=', 'compras.proveedor_id');
                })
                ->select(
                    'detalle_compras.numero_compra',
                    'detalle_compras.producto_id',
                    'detalle_compras.cantidad',
                    'compras.usuario_id',
                    'compras.fecha'
                )
                ->get();

            foreach ($detallesCompra as $d) {
                DB::table('movimientos_inventario')->insertOrIgnore([
                    'referencia_movimiento' => $d->numero_compra,
                    'producto_id' => $d->producto_id,
                    'usuario_id' => $d->usuario_id,
                    'tipo_movimiento_inventario_id' => $entradaId,
                    'cantidad' => $d->cantidad,
                    'descripcion' => "Compra a proveedor {$d->numero_compra}",
                    'fecha' => \Illuminate\Support\Carbon::parse($d->fecha)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Salidas por facturas de venta.
        if ($salidaId) {
            $detallesFactura = DB::table('detalle_facturas')
                ->join('facturas', 'detalle_facturas.numero_factura', '=', 'facturas.numero_factura')
                ->select(
                    'detalle_facturas.numero_factura',
                    'detalle_facturas.producto_id',
                    'detalle_facturas.cantidad',
                    'facturas.usuario_id',
                    'facturas.fecha'
                )
                ->get();

            foreach ($detallesFactura as $d) {
                DB::table('movimientos_inventario')->insertOrIgnore([
                    'referencia_movimiento' => $d->numero_factura,
                    'producto_id' => $d->producto_id,
                    'usuario_id' => $d->usuario_id,
                    'tipo_movimiento_inventario_id' => $salidaId,
                    'cantidad' => $d->cantidad,
                    'descripcion' => "Venta a cliente {$d->numero_factura}",
                    'fecha' => \Illuminate\Support\Carbon::parse($d->fecha)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * No se revierte: los movimientos generados son parte de la traza
     * histórica del inventario.
     */
    public function down(): void
    {
    }
};
