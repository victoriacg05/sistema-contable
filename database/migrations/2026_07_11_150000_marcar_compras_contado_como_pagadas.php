<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alinea los datos históricos con la regla de negocio: las compras al
     * contado deben quedar pagadas automáticamente y sin saldo pendiente.
     * Las compras registradas antes de aplicar esta regla quedaron con
     * estado "pendiente" y con una cuenta por pagar abierta, por lo que se
     * corrigen aquí.
     */
    public function up(): void
    {
        $estadoPagadoId = DB::table('estados')->where('nombre', 'pagado')->value('id');

        if (! $estadoPagadoId) {
            return;
        }

        $numerosContado = DB::table('compras')
            ->where('tipo_compra', 'contado')
            ->pluck('numero_compra')
            ->all();

        DB::table('compras')
            ->where('tipo_compra', 'contado')
            ->update(['estado_id' => $estadoPagadoId]);

        if (! empty($numerosContado)) {
            DB::table('cuentas_pagar')
                ->whereIn('numero_compra', $numerosContado)
                ->update([
                    'saldo_pendiente' => 0,
                    'estado_id' => $estadoPagadoId,
                ]);
        }
    }

    /**
     * No se revierte: no es posible reconstruir con certeza el estado
     * anterior de cada compra.
     */
    public function down(): void
    {
    }
};
