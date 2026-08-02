<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $distribucion = [
            'Diana Castro' => [
                'Jabon para llantas',
                'Repelente de insectos uso exterior 300ml',
                'Limpiador multiusos concentrado 1L',
                'Limpiador de muebles Gota Roja 500ml',
            ],
            'Juan Morales' => [
                'Encendedor líquido para parrilla 500ml',
                'Aceite penetrante aflojatodo 250ml',
                'Lubricante multiusos Gota Roja 150ml',
                'Lubricante multiusos Gota Roja 300ml',
            ],
        ];

        DB::transaction(function () use ($distribucion) {
            foreach ($distribucion as $nombreProveedor => $nombresProductos) {
                $proveedorId = DB::table('proveedores')
                    ->where('nombre', $nombreProveedor)
                    ->value('id');

                if (! $proveedorId) {
                    continue;
                }

                $productosIds = DB::table('productos')
                    ->whereIn('nombre', $nombresProductos)
                    ->pluck('id');

                if ($productosIds->count() !== count($nombresProductos)) {
                    continue;
                }

                DB::table('proveedor_producto')
                    ->where('proveedor_id', $proveedorId)
                    ->delete();

                $ahora = now();
                $relaciones = $productosIds->map(fn ($productoId) => [
                    'proveedor_id' => $proveedorId,
                    'producto_id' => $productoId,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ])->all();

                DB::table('proveedor_producto')->insert($relaciones);
            }
        });
    }

    public function down(): void
    {
        $proveedoresIds = DB::table('proveedores')
            ->whereIn('nombre', ['Diana Castro', 'Juan Morales'])
            ->pluck('id');

        if ($proveedoresIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($proveedoresIds) {
            DB::table('proveedor_producto')
                ->whereIn('proveedor_id', $proveedoresIds)
                ->delete();

            $relaciones = DB::table('detalle_compras as detalle')
                ->join('productos as producto', 'producto.id', '=', 'detalle.producto_id')
                ->whereIn('detalle.proveedor_id', $proveedoresIds)
                ->select('detalle.proveedor_id', 'detalle.producto_id')
                ->distinct()
                ->get();

            $ahora = now();
            foreach ($relaciones as $relacion) {
                DB::table('proveedor_producto')->insertOrIgnore([
                    'proveedor_id' => $relacion->proveedor_id,
                    'producto_id' => $relacion->producto_id,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        });
    }
};
