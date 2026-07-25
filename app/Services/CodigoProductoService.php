<?php

namespace App\Services;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CodigoProductoService
{
    public function asegurarEstructuraPrecios(): void
    {
        if (
            Schema::hasTable('productos')
            && ! Schema::hasColumn('productos', 'porcentaje_ganancia')
        ) {
            Schema::table('productos', function (Blueprint $table) {
                $table->decimal('porcentaje_ganancia', 5, 2)
                    ->default(0)
                    ->after('precio');
            });
        }
    }

    public function siguiente(int $categoriaId): string
    {
        $prefijo = sprintf('PRD-%03d-', $categoriaId);
        $ultimaSecuencia = Producto::where('categoria_producto_id', $categoriaId)
            ->where('codigo_barras', 'like', $prefijo . '%')
            ->pluck('codigo_barras')
            ->reduce(function (int $maximo, string $codigo) use ($prefijo) {
                $secuencia = substr($codigo, strlen($prefijo));

                return ctype_digit($secuencia)
                    ? max($maximo, (int) $secuencia)
                    : $maximo;
            }, 0);

        return $prefijo . sprintf('%04d', $ultimaSecuencia + 1);
    }

    public function normalizarExistentes(): void
    {
        if (! Producto::where('codigo_barras', 'not like', 'PRD-%')->exists()) {
            return;
        }

        DB::transaction(function () {
            CategoriaProducto::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $productos = Producto::query()
                ->orderBy('categoria_producto_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'categoria_producto_id']);

            foreach ($productos as $producto) {
                $producto->update([
                    'codigo_barras' => 'TEMP-' . $producto->id . '-' . Str::uuid(),
                ]);
            }

            $secuencias = [];

            foreach ($productos as $producto) {
                $categoriaId = (int) $producto->categoria_producto_id;
                $secuencias[$categoriaId] = ($secuencias[$categoriaId] ?? 0) + 1;

                $producto->update([
                    'codigo_barras' => sprintf(
                        'PRD-%03d-%04d',
                        $categoriaId,
                        $secuencias[$categoriaId]
                    ),
                ]);
            }
        }, 3);
    }
}
