<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alinea los datos históricos con la nueva regla de negocio: la condición
     * de pago (contado/crédito) determina si una factura queda pagada o
     * pendiente. Las facturas creadas con la lógica anterior (que usaba el
     * tipo de comprobante para inferir el crédito) quedaron marcadas como
     * "pendiente" aunque su pago fue inmediato. Aquí se marcan como pagadas y
     * se saldan sus cuentas por cobrar.
     */
    public function up(): void
    {
        $estadoPendienteId = DB::table('estados')->where('nombre', 'pendiente')->value('id');
        $estadoPagadoId = DB::table('estados')->where('nombre', 'pagado')->value('id');

        if (! $estadoPagadoId) {
            return;
        }

        $numerosPendientes = DB::table('facturas')
            ->when($estadoPendienteId, fn ($q) => $q->where('estado_id', $estadoPendienteId))
            ->pluck('numero_factura')
            ->all();

        DB::table('facturas')
            ->when($estadoPendienteId, fn ($q) => $q->where('estado_id', $estadoPendienteId))
            ->update(['estado_id' => $estadoPagadoId]);

        if (! empty($numerosPendientes)) {
            DB::table('cuentas_cobrar')
                ->whereIn('numero_factura', $numerosPendientes)
                ->update([
                    'saldo_pendiente' => 0,
                    'estado_id' => $estadoPagadoId,
                ]);
        }
    }

    /**
     * No se revierte: no es posible reconstruir con certeza el estado
     * anterior de cada factura.
     */
    public function down(): void
    {
    }
};
