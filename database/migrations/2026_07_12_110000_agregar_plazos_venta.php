<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plazos (cuotas) asociados a una venta a crédito (factura), análogo a
        // plazos_compra para las compras a proveedor.
        Schema::create('plazos_venta', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->integer('numero_cuota');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plazos_venta');
    }
};
