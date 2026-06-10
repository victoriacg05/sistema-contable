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
    Schema::table('facturas', function (Blueprint $table) {

        $table->dropColumn([
            'producto_id',
            'cantidad'
        ]);

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::table('facturas', function (Blueprint $table) {

        $table->unsignedBigInteger('producto_id')->nullable();

        $table->integer('cantidad')->nullable();

    });
    }
};
