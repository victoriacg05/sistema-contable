<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GANANCIA_EXISTENTE = 30;
    private const TABLA_RESPALDO_PRECIOS = 'respaldo_precios_productos_20260725';
    private const TABLA_ESTADO_CONVERSION = 'estado_conversion_precios_productos_20260725';

    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'porcentaje_ganancia')) {
            return;
        }

        Cache::lock('convertir-precios-existentes-productos', 60)->block(10, function () {
            $this->asegurarEstadoConversion();
            $this->asegurarRespaldoPrecios();

            DB::transaction(function () {
                DB::table('productos')
                    ->where('porcentaje_ganancia', 0)
                    ->orderBy('id')
                    ->get(['id', 'precio'])
                    ->each(function ($producto) {
                        DB::table(self::TABLA_RESPALDO_PRECIOS)->insertOrIgnore([
                            'producto_id' => $producto->id,
                            'precio_anterior' => $producto->precio,
                            'porcentaje_anterior' => 0,
                        ]);

                        DB::table('productos')
                            ->where('id', $producto->id)
                            ->update([
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
            });
        });
    }

    public function down(): void
    {
        Cache::lock('convertir-precios-existentes-productos', 60)->block(10, function () {
            $this->asegurarEstadoConversion();

            if (Schema::hasTable(self::TABLA_RESPALDO_PRECIOS)) {
                DB::transaction(function () {
                    DB::table(self::TABLA_RESPALDO_PRECIOS)
                        ->orderBy('producto_id')
                        ->get()
                        ->each(function ($respaldo) {
                            DB::table('productos')
                                ->where('id', $respaldo->producto_id)
                                ->update([
                                    'precio' => $respaldo->precio_anterior,
                                    'porcentaje_ganancia' => $respaldo->porcentaje_anterior,
                                ]);
                        });

                    DB::table(self::TABLA_ESTADO_CONVERSION)->updateOrInsert(
                        ['id' => 1],
                        ['estado' => 'revertida']
                    );
                });

                Schema::drop(self::TABLA_RESPALDO_PRECIOS);
            } else {
                DB::table(self::TABLA_ESTADO_CONVERSION)->updateOrInsert(
                    ['id' => 1],
                    ['estado' => 'revertida']
                );
            }
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
};
