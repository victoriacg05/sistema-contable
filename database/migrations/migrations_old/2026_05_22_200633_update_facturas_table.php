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
    Schema::table('facturas', function (Blueprint $table) {

        $table->foreignId('usuario_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');

        $table->foreignId('metodo_pago_id')
              ->nullable()
              ->constrained('metodos_pago')
              ->onDelete('set null');

        $table->decimal('subtotal', 10, 2)->default(0);

        $table->decimal('impuesto', 10, 2)->default(0);

        $table->decimal('descuento', 10, 2)->default(0);

        $table->decimal('total', 10, 2)->default(0);

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::table('facturas', function (Blueprint $table) {

        $table->dropForeign(['usuario_id']);

        $table->dropForeign(['metodo_pago_id']);

        $table->dropColumn([
            'usuario_id',
            'metodo_pago_id',
            'subtotal',
            'impuesto',
            'descuento',
            'total'
        ]);

    });
    }
};
