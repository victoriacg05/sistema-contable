<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $categoriaId = DB::table('categorias_productos')
                ->where('nombre', 'Alimentos')
                ->value('id');

            if (! $categoriaId) {
                return;
            }

            $productosIds = DB::table('productos')
                ->where('categoria_producto_id', $categoriaId)
                ->pluck('id');

            if ($productosIds->isNotEmpty()) {
                $tieneMovimientos = DB::table('detalle_compras')
                    ->whereIn('producto_id', $productosIds)
                    ->exists()
                    || DB::table('detalle_facturas')
                        ->whereIn('producto_id', $productosIds)
                        ->exists()
                    || DB::table('movimientos_inventario')
                        ->whereIn('producto_id', $productosIds)
                        ->exists();

                if ($tieneMovimientos) {
                    throw new \RuntimeException(
                        'No se puede eliminar Alimentos porque sus productos ya tienen movimientos registrados.'
                    );
                }

                DB::table('proveedor_producto')
                    ->whereIn('producto_id', $productosIds)
                    ->delete();

                DB::table('productos')
                    ->whereIn('id', $productosIds)
                    ->delete();
            }

            $this->eliminarProveedorSinUso();

            DB::table('categorias_productos')
                ->where('id', $categoriaId)
                ->delete();
        });
    }

    public function down(): void
    {
        // La eliminación solicitada no debe recrear inventario eliminado.
    }

    private function eliminarProveedorSinUso(): void
    {
        $proveedorId = DB::table('proveedores')
            ->where('identificacion', '3-101-900002')
            ->value('id');

        if (! $proveedorId) {
            return;
        }

        $estaEnUso = DB::table('compras')
            ->where('proveedor_id', $proveedorId)
            ->exists()
            || DB::table('proveedor_producto')
                ->where('proveedor_id', $proveedorId)
                ->exists();

        if (! $estaEnUso) {
            DB::table('proveedores')
                ->where('id', $proveedorId)
                ->delete();
        }
    }
};
