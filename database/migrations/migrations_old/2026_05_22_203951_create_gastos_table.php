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
    Schema::create('gastos', function (Blueprint $table) {

        $table->id();

        $table->foreignId('categoria_id')
              ->nullable()
              ->constrained('categorias')
              ->onDelete('set null');

        $table->foreignId('usuario_id')
              ->nullable()
              ->constrained('users')
              ->onDelete('set null');

        $table->foreignId('metodo_pago_id')
              ->nullable()
              ->constrained('metodos_pago')
              ->onDelete('set null');

        $table->text('descripcion');

        $table->decimal('monto', 10, 2);

        $table->date('fecha');

        $table->timestamps();

    });
    }
};
