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
    Schema::create('cuentas_cobrar', function (Blueprint $table) {

        $table->id();

        $table->foreignId('factura_id')
              ->constrained('facturas')
              ->onDelete('cascade');

        $table->foreignId('cliente_id')
              ->constrained('clientes')
              ->onDelete('cascade');

        $table->decimal('saldo_pendiente', 10, 2);

        $table->date('fecha_vencimiento');

        $table->string('estado');

        $table->timestamps();

    });
    }
};
