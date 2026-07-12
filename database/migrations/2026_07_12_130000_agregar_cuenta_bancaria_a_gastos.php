<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gastos', 'cuenta_bancaria_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->foreignId('cuenta_bancaria_id')
                    ->nullable()
                    ->after('metodo_pago_id')
                    ->constrained('cuentas_bancarias')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gastos', 'cuenta_bancaria_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cuenta_bancaria_id');
            });
        }
    }
};
