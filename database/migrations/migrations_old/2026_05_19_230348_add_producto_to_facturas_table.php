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

            $table->foreignId('producto_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('cascade');

            $table->integer('cantidad')->default(1);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {

            $table->dropForeign(['producto_id']);

            $table->dropColumn('producto_id');

            $table->dropColumn('cantidad');

        });
    }
};