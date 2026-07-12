<?php

namespace App\Services;

use App\Models\Estado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Genera asientos contables balanceados de forma automática para que las
 * operaciones (compras de contado, a crédito y pagos) queden reflejadas en
 * el módulo de Contabilidad sin registro manual del usuario.
 */
class AsientoContableService
{
    /**
     * Registra un asiento contable con sus líneas de detalle.
     *
     * @param  array<int, array{codigo_cuenta: string, debe?: float, haber?: float, descripcion?: string}>  $lineas
     * @return string  Número de asiento generado.
     */
    public static function generar($fecha, string $descripcion, array $lineas): string
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        $numeroAsiento = 'ASI-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);

        $totalDebe = round(array_sum(array_map(fn ($l) => $l['debe'] ?? 0, $lineas)), 2);
        $totalHaber = round(array_sum(array_map(fn ($l) => $l['haber'] ?? 0, $lineas)), 2);

        $estadoId = Estado::where('nombre', 'Aprobado')->value('id')
            ?? Estado::where('nombre', 'Activo')->value('id')
            ?? 1;

        DB::table('asientos_contables')->insert([
            'numero_asiento' => $numeroAsiento,
            'usuario_id' => Auth::id(),
            'fecha' => $fecha,
            'descripcion' => $descripcion,
            'total_debe' => $totalDebe,
            'total_haber' => $totalHaber,
            'estado_id' => $estadoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($lineas as $linea) {
            DB::table('detalle_asientos_contables')->insert([
                'numero_asiento' => $numeroAsiento,
                'fecha_asiento' => $fecha,
                'codigo_cuenta' => $linea['codigo_cuenta'],
                'debe' => $linea['debe'] ?? 0,
                'haber' => $linea['haber'] ?? 0,
                'descripcion' => $linea['descripcion'] ?? $descripcion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $numeroAsiento;
    }
}
