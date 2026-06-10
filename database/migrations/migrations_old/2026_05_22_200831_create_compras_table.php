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
    Schema::create('compras', function (Blueprint $table) {

        $table->id();

        $table->foreignId('proveedor_id')
              ->constrained('proveedores')
              ->onDelete('cascade');

        $table->foreignId('usuario_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');

        $table->date('fecha');

        $table->decimal('subtotal', 10, 2);

        $table->decimal('impuesto', 10, 2);

        $table->decimal('total', 10, 2);

        $table->string('estado')->default('Pendiente');

        $table->timestamps();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::dropIfExists('compras');
    }
};
