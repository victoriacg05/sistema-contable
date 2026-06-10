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
    Schema::create('movimientos_inventario', function (Blueprint $table) {

        $table->id();

        $table->foreignId('producto_id')
              ->constrained('productos')
              ->onDelete('cascade');

        $table->foreignId('usuario_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');

        $table->string('tipo_movimiento');

        $table->integer('cantidad');

        $table->text('descripcion')->nullable();

        $table->date('fecha');

        $table->timestamps();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::dropIfExists('movimientos_inventario');
    }
};
