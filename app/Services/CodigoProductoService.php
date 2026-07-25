<?php

namespace App\Services;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CodigoProductoService
{
    private const GANANCIA_EXISTENTE = 30;
    private const ESTADO_REVERTIDO = 'revertida';
    private const TABLA_RESPALDO_PRECIOS = 'respaldo_precios_productos_20260725';
    private const TABLA_ESTADO_CONVERSION = 'estado_conversion_precios_productos_20260725';

    public function asegurarEstructuraPrecios(): void
    {
        if (! Schema::hasTable('productos') || Schema::hasColumn('productos', 'porcentaje_ganancia')) {
            return;
        }

        Cache::lock('preparar-estructura-precios-productos', 30)->block(10, function () {
            if (! Schema::hasColumn('productos', 'porcentaje_ganancia')) {
                Schema::table('productos', function (Blueprint $table) {
                    $table->decimal('porcentaje_ganancia', 5, 2)
                        ->default(0)
                        ->after('precio');
                });
            }
        });
    }

    public function convertirPreciosExistentes(): void
    {
        if (! Producto::where('porcentaje_ganancia', 0)->exists()) {
            return;
        }

        Cache::lock('convertir-precios-existentes-productos', 60)->block(10, function () {
            $this->asegurarEstadoConversion();

            if (
                DB::table(self::TABLA_ESTADO_CONVERSION)
                    ->where('id', 1)
                    ->value('estado') === self::ESTADO_REVERTIDO
            ) {
                return;
            }

            if (! Producto::where('porcentaje_ganancia', 0)->exists()) {
                return;
            }

            $this->asegurarRespaldoPrecios();

            DB::transaction(function () {
                Producto::where('porcentaje_ganancia', 0)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->each(function (Producto $producto) {
                        DB::table(self::TABLA_RESPALDO_PRECIOS)->insertOrIgnore([
                            'producto_id' => $producto->id,
                            'precio_anterior' => $producto->precio,
                            'porcentaje_anterior' => $producto->porcentaje_ganancia,
                        ]);

                        $producto->update([
                            'precio' => round(
                                (float) $producto->precio / (1 + (self::GANANCIA_EXISTENTE / 100)),
                                2
                            ),
                            'porcentaje_ganancia' => self::GANANCIA_EXISTENTE,
                        ]);
                    });

                DB::table(self::TABLA_ESTADO_CONVERSION)->updateOrInsert(
                    ['id' => 1],
                    ['estado' => 'aplicada']
                );
            }, 3);
        });
    }

    public function conversionPreciosRevertida(): bool
    {
        return Schema::hasTable(self::TABLA_ESTADO_CONVERSION)
            && DB::table(self::TABLA_ESTADO_CONVERSION)
                ->where('id', 1)
                ->value('estado') === self::ESTADO_REVERTIDO;
    }

    private function asegurarEstadoConversion(): void
    {
        if (Schema::hasTable(self::TABLA_ESTADO_CONVERSION)) {
            return;
        }

        Schema::create(self::TABLA_ESTADO_CONVERSION, function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('estado', 20);
        });
    }

    private function asegurarRespaldoPrecios(): void
    {
        if (Schema::hasTable(self::TABLA_RESPALDO_PRECIOS)) {
            return;
        }

        Schema::create(self::TABLA_RESPALDO_PRECIOS, function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->primary();
            $table->decimal('precio_anterior', 10, 2);
            $table->decimal('porcentaje_anterior', 5, 2);
        });
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
