<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas_cobrar', function (Blueprint $table) {
            $table->index(
                ['fecha_vencimiento', 'saldo_pendiente'],
                'cuentas_cobrar_morosidad_index'
            );
        });

        Schema::table('cuentas_pagar', function (Blueprint $table) {
            $table->index(
                ['fecha_vencimiento', 'saldo_pendiente'],
                'cuentas_pagar_morosidad_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_cobrar', function (Blueprint $table) {
            $table->dropIndex('cuentas_cobrar_morosidad_index');
        });

        Schema::table('cuentas_pagar', function (Blueprint $table) {
            $table->dropIndex('cuentas_pagar_morosidad_index');
        });
    }
};
