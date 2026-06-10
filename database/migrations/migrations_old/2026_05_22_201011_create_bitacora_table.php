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
    Schema::create('bitacora', function (Blueprint $table) {

        $table->id();

        $table->foreignId('usuario_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');

        $table->string('accion');

        $table->string('tabla_afectada');

        $table->text('descripcion');

        $table->dateTime('fecha');

        $table->timestamps();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::dropIfExists('bitacora');
    }
};
