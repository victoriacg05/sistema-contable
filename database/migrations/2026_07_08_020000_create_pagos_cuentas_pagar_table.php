<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cuentas_pagar', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compra');
            $table->unsignedBigInteger('proveedor_id');
            $table->dateTime('fecha_pago');
            $table->decimal('monto_pagado', 10, 2);
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->string('observacion', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign(['numero_compra', 'proveedor_id'])
                ->references(['numero_compra', 'proveedor_id'])
                ->on('cuentas_pagar')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cuentas_pagar');
    }
};
