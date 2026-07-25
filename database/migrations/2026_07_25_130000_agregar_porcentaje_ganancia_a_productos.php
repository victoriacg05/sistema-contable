<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('productos', 'porcentaje_ganancia')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('porcentaje_ganancia', 5, 2)
                ->default(0)
                ->after('precio');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('productos', 'porcentaje_ganancia')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('porcentaje_ganancia');
        });
    }
};
