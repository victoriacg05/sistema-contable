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
    Schema::create('cuentas_pagar', function (Blueprint $table) {

        $table->id();

        $table->foreignId('compra_id')
              ->constrained('compras')
              ->onDelete('cascade');

        $table->foreignId('proveedor_id')
              ->constrained('proveedores')
              ->onDelete('cascade');

        $table->decimal('saldo_pendiente', 10, 2);

        $table->date('fecha_vencimiento');

        $table->string('estado');

        $table->timestamps();

    });
    }
};
