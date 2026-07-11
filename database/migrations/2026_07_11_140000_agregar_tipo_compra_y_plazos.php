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
        // Tipo de compra: contado o credito (por defecto contado para
        // conservar el comportamiento actual de las compras existentes).
        Schema::table('compras', function (Blueprint $table) {
            $table->string('tipo_compra', 20)->default('contado')->after('estado_id');
        });

        // Plazos (cuotas) asociados a una compra a credito.
        Schema::create('plazos_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compra');
            $table->unsignedBigInteger('proveedor_id');
            $table->integer('numero_cuota');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign(['numero_compra', 'proveedor_id'])
                ->references(['numero_compra', 'proveedor_id'])
                ->on('compras')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plazos_compra');

        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn('tipo_compra');
        });
    }
};
