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
    Schema::create('pagos', function (Blueprint $table) {

        $table->id();

        $table->foreignId('factura_id')
              ->constrained('facturas')
              ->onDelete('cascade');

        $table->foreignId('metodo_pago_id')
              ->constrained('metodos_pago')
              ->onDelete('cascade');

        $table->foreignId('usuario_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');

        $table->decimal('monto', 10, 2);

        $table->date('fecha_pago');

        $table->string('referencia')->nullable();

        $table->timestamps();

    });
    }
};
