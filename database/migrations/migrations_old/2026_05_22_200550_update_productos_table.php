<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::table('productos', function (Blueprint $table) {

        $table->foreignId('categoria_id')
              ->nullable()
              ->after('id')
              ->constrained('categorias')
              ->onDelete('set null');

        $table->string('codigo_barras')->nullable();

        $table->integer('stock_minimo')->default(5);

        $table->boolean('estado')->default(true);

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::table('productos', function (Blueprint $table) {

        $table->dropForeign(['categoria_id']);

        $table->dropColumn([
            'categoria_id',
            'codigo_barras',
            'stock_minimo',
            'estado'
        ]);

    });
    }
};
