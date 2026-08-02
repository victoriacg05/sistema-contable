<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->default(null)->change();
            $table->string('ip_address', 45)->nullable()->default(null)->change();
            $table->text('user_agent')->nullable()->change();
        });
    }

    public function down(): void
    {
    }
};
