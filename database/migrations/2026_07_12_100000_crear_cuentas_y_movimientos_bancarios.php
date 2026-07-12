<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_cuenta')->default('1.1.2');
            $table->string('banco_nombre');
            $table->string('numero_cuenta')->unique();
            $table->string('moneda', 10)->default('CRC');
            $table->decimal('saldo', 14, 2)->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('codigo_cuenta')
                ->references('codigo_cuenta')
                ->on('catalogo_cuentas')
                ->restrictOnDelete();
        });

        Schema::create('movimientos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')
                ->constrained('cuentas_bancarias')
                ->cascadeOnDelete();
            $table->string('tipo', 20); // entrada | salida
            $table->decimal('monto', 14, 2);
            $table->string('descripcion', 500)->default('');
            $table->string('referencia')->nullable();
            $table->decimal('saldo_anterior', 14, 2)->default(0);
            $table->decimal('saldo_nuevo', 14, 2)->default(0);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->dateTime('fecha')->useCurrent();
            $table->timestamps();

            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_bancaria_id')->nullable()->after('metodo_pago_id');

            $table->foreign('cuenta_bancaria_id')
                ->references('id')
                ->on('cuentas_bancarias')
                ->nullOnDelete();
        });

        // Semilla idempotente de cuentas bancarias iniciales (bancos de Costa Rica).
        // Se ejecuta dentro de la migración para no requerir correr los seeders
        // completos sobre bases de datos existentes.
        $ahora = now();
        $bancos = [
            ['banco_nombre' => 'BAC San José', 'numero_cuenta' => 'CR-BAC-0001'],
            ['banco_nombre' => 'Banco de Costa Rica (BCR)', 'numero_cuenta' => 'CR-BCR-0001'],
            ['banco_nombre' => 'Banco Nacional (BN)', 'numero_cuenta' => 'CR-BN-0001'],
            ['banco_nombre' => 'Scotiabank', 'numero_cuenta' => 'CR-SCO-0001'],
            ['banco_nombre' => 'Davivienda', 'numero_cuenta' => 'CR-DAV-0001'],
            ['banco_nombre' => 'Banco Promerica', 'numero_cuenta' => 'CR-PRO-0001'],
        ];

        foreach ($bancos as $banco) {
            DB::table('cuentas_bancarias')->insertOrIgnore([
                'codigo_cuenta' => '1.1.2',
                'banco_nombre' => $banco['banco_nombre'],
                'numero_cuenta' => $banco['numero_cuenta'],
                'moneda' => 'CRC',
                'saldo' => 1000000,
                'estado' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['cuenta_bancaria_id']);
            $table->dropColumn('cuenta_bancaria_id');
        });

        Schema::dropIfExists('movimientos_bancarios');
        Schema::dropIfExists('cuentas_bancarias');
    }
};
