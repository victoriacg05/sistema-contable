<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $productos = DB::table('productos')
                ->select('id', 'categoria_producto_id')
                ->orderBy('categoria_producto_id')
                ->orderBy('id')
                ->get();

            foreach ($productos as $producto) {
                DB::table('productos')
                    ->where('id', $producto->id)
                    ->update([
                        'codigo_barras' => 'TEMP-' . $producto->id . '-' . Str::uuid(),
                    ]);
            }

            $secuencias = [];

            foreach ($productos as $producto) {
                $categoriaId = (int) $producto->categoria_producto_id;
                $secuencias[$categoriaId] = ($secuencias[$categoriaId] ?? 0) + 1;

                DB::table('productos')
                    ->where('id', $producto->id)
                    ->update([
                        'codigo_barras' => sprintf(
                            'PRD-%03d-%04d',
                            $categoriaId,
                            $secuencias[$categoriaId]
                        ),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Los códigos anteriores no pueden reconstruirse después de normalizarlos.
    }
};
