<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tipoActivoId = DB::table('tipos_cuenta_contable')
            ->where('nombre', 'Activo')
            ->value('id');

        if ($tipoActivoId) {
            DB::table('catalogo_cuentas')->insertOrIgnore([
                'codigo_cuenta' => '1.1.4.1',
                'tipo_cuenta_contable_id' => $tipoActivoId,
                'nombre' => 'Inventario en Bodega',
                'descripcion' => 'Mercadería almacenada en bodega',
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('catalogo_cuentas')
            ->where('codigo_cuenta', '1.1.4.1')
            ->delete();
    }
};
