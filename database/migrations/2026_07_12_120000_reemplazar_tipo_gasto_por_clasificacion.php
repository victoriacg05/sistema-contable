<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza el "tipo de gasto" (Fijo/Variable/Extraordinario) por una
     * clasificación contable (Directo/Indirecto) que ahora proviene de la
     * categoría del gasto.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('categorias_gastos', 'clasificacion')) {
            Schema::table('categorias_gastos', function (Blueprint $table) {
                $table->string('clasificacion')->default('Indirecto')->after('descripcion');
            });
        }

        // Los gastos directos son los ligados directamente a la distribución y
        // venta del producto; el resto se consideran indirectos (generales o
        // administrativos).
        $directas = ['Transporte', 'Suministros'];

        DB::table('categorias_gastos')->update(['clasificacion' => 'Indirecto']);
        DB::table('categorias_gastos')
            ->whereIn('nombre', $directas)
            ->update(['clasificacion' => 'Directo']);

        // Se elimina el tipo de gasto: la clasificación se deriva de la categoría.
        if (Schema::hasColumn('gastos', 'tipo_gasto_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->dropForeign(['tipo_gasto_id']);
                $table->dropColumn('tipo_gasto_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('gastos', 'tipo_gasto_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_gasto_id')->nullable()->after('categoria_gasto_id');
                $table->foreign('tipo_gasto_id')
                    ->references('id')
                    ->on('tipos_gasto')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('categorias_gastos', 'clasificacion')) {
            Schema::table('categorias_gastos', function (Blueprint $table) {
                $table->dropColumn('clasificacion');
            });
        }
    }
};
