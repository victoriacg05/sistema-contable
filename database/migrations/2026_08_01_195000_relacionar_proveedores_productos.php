<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_producto', function (Blueprint $table) {
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->cascadeOnDelete();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['proveedor_id', 'producto_id']);
        });

        $relaciones = DB::table('detalle_compras as detalle')
            ->join('proveedores as proveedor', 'proveedor.id', '=', 'detalle.proveedor_id')
            ->join('productos as producto', 'producto.id', '=', 'detalle.producto_id')
            ->select('detalle.proveedor_id', 'detalle.producto_id')
            ->distinct()
            ->get();

        foreach ($relaciones as $relacion) {
            DB::table('proveedor_producto')->insertOrIgnore([
                'proveedor_id' => $relacion->proveedor_id,
                'producto_id' => $relacion->producto_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_producto');
    }
};
