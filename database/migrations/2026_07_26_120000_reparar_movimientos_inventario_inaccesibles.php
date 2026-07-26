<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->tablaEsAccesible()) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::statement('DROP TABLE IF EXISTS movimientos_inventario');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->string('referencia_movimiento');
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tipo_movimiento_inventario_id')
                ->constrained('tipos_movimiento_inventario')
                ->restrictOnDelete();
            $table->integer('cantidad');
            $table->string('descripcion', 500)->default('');
            $table->date('fecha');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary([
                'referencia_movimiento',
                'producto_id',
                'tipo_movimiento_inventario_id',
            ]);
        });

        $this->reconstruirHistorial();
    }

    private function tablaEsAccesible(): bool
    {
        if (! Schema::hasTable('movimientos_inventario')) {
            return false;
        }

        try {
            DB::table('movimientos_inventario')->limit(1)->get();

            return true;
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1932) {
                return false;
            }

            throw $exception;
        }
    }

    private function reconstruirHistorial(): void
    {
        $entradaId = DB::table('tipos_movimiento_inventario')
            ->whereRaw('LOWER(nombre) = ?', ['entrada'])
            ->value('id');

        $salidaId = DB::table('tipos_movimiento_inventario')
            ->whereRaw('LOWER(nombre) = ?', ['salida'])
            ->value('id');

        if ($entradaId) {
            $detallesCompra = DB::table('detalle_compras')
                ->join('compras', function ($join) {
                    $join->on('detalle_compras.numero_compra', '=', 'compras.numero_compra')
                        ->on('detalle_compras.proveedor_id', '=', 'compras.proveedor_id');
                })
                ->select(
                    'detalle_compras.numero_compra',
                    'detalle_compras.producto_id',
                    'detalle_compras.cantidad',
                    'compras.usuario_id',
                    'compras.fecha'
                )
                ->get();

            foreach ($detallesCompra as $detalle) {
                DB::table('movimientos_inventario')->insertOrIgnore([
                    'referencia_movimiento' => $detalle->numero_compra,
                    'producto_id' => $detalle->producto_id,
                    'usuario_id' => $detalle->usuario_id,
                    'tipo_movimiento_inventario_id' => $entradaId,
                    'cantidad' => $detalle->cantidad,
                    'descripcion' => "Compra a proveedor {$detalle->numero_compra}",
                    'fecha' => $detalle->fecha,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($salidaId) {
            $detallesFactura = DB::table('detalle_facturas')
                ->join('facturas', 'detalle_facturas.numero_factura', '=', 'facturas.numero_factura')
                ->select(
                    'detalle_facturas.numero_factura',
                    'detalle_facturas.producto_id',
                    'detalle_facturas.cantidad',
                    'facturas.usuario_id',
                    'facturas.fecha'
                )
                ->get();

            foreach ($detallesFactura as $detalle) {
                DB::table('movimientos_inventario')->insertOrIgnore([
                    'referencia_movimiento' => $detalle->numero_factura,
                    'producto_id' => $detalle->producto_id,
                    'usuario_id' => $detalle->usuario_id,
                    'tipo_movimiento_inventario_id' => $salidaId,
                    'cantidad' => $detalle->cantidad,
                    'descripcion' => "Venta a cliente {$detalle->numero_factura}",
                    'fecha' => $detalle->fecha,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};
