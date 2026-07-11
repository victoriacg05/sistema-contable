<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra con qué método (efectivo, transferencia, tarjeta, etc.) se
     * paga una compra a proveedor. Es opcional para conservar las compras
     * existentes que se registraron sin este dato.
     */
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->foreignId('metodo_pago_id')
                ->nullable()
                ->after('estado_id')
                ->constrained('metodos_pago')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metodo_pago_id');
        });
    }
};
