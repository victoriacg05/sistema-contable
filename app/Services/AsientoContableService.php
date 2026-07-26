<?php

namespace App\Services;

use App\Models\Estado;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Genera asientos contables balanceados de forma automática para que las
 * operaciones del sistema queden reflejadas en el módulo de Contabilidad sin
 * registro manual del usuario.
 */
class AsientoContableService
{
    /**
     * Registra un asiento contable con sus líneas de detalle.
     *
     * @param  array<int, array{codigo_cuenta: string, debe?: float, haber?: float, descripcion?: string}>  $lineas
     * @return string  Número de asiento generado.
     */
    public static function generar(
        $fecha,
        string $descripcion,
        array $lineas,
        ?string $claveOperacion = null
    ): string
    {
        $fecha = Carbon::parse($fecha)->toDateString();
        $lineas = self::normalizarLineas($lineas);

        $totalDebe = round(array_sum(array_column($lineas, 'debe')), 2);
        $totalHaber = round(array_sum(array_column($lineas, 'haber')), 2);

        if (abs($totalDebe - $totalHaber) > 0.01) {
            throw new InvalidArgumentException(
                'El asiento automático no está balanceado: el debe debe ser igual al haber.'
            );
        }

        if ($claveOperacion === null) {
            return self::guardar(
                $fecha,
                $descripcion,
                $lineas,
                $totalDebe,
                $totalHaber
            );
        }

        $claveOperacion = Str::upper($claveOperacion);

        return Cache::lock('asiento-automatico-' . sha1($claveOperacion), 30)
            ->block(10, fn () => self::guardar(
                $fecha,
                $descripcion,
                $lineas,
                $totalDebe,
                $totalHaber,
                $claveOperacion
            ));
    }

    public static function revertir(
        $fecha,
        string $claveOperacion,
        string $claveReversion,
        string $descripcion
    ): ?string {
        $prefijo = self::prefijoOperacion(Str::upper($claveOperacion));

        $asiento = self::buscarAsientoOperacion(
            Str::upper($claveOperacion)
        );

        if (! $asiento) {
            return null;
        }

        $lineas = DB::table('detalle_asientos_contables')
            ->where('numero_asiento', $asiento->numero_asiento)
            ->where('fecha_asiento', $asiento->fecha)
            ->get()
            ->map(fn ($linea) => [
                'codigo_cuenta' => $linea->codigo_cuenta,
                'debe' => (float) $linea->haber,
                'haber' => (float) $linea->debe,
                'descripcion' => $descripcion,
            ])
            ->all();

        return self::generar(
            $fecha,
            $descripcion,
            $lineas,
            $claveReversion
        );
    }

    public static function cuentaPorMetodoPago(int $metodoPagoId): string
    {
        return self::requiereCuentaBancaria($metodoPagoId)
            ? '1.1.2'
            : '1.1.1';
    }

    public static function requiereCuentaBancaria(int $metodoPagoId): bool
    {
        $nombre = Str::lower((string) DB::table('metodos_pago')
            ->where('id', $metodoPagoId)
            ->value('nombre'));

        return $nombre !== '' && $nombre !== 'efectivo';
    }

    public static function registrarVenta(
        $fecha,
        string $numeroFactura,
        float $subtotal,
        float $impuesto,
        float $descuento,
        float $costoInventario,
        bool $esCredito,
        int $metodoPagoId
    ): string {
        $total = round(($subtotal + $impuesto) - $descuento, 2);
        $ventaNeta = round($subtotal - $descuento, 2);
        $cuentaCobro = $esCredito
            ? '1.1.3'
            : self::cuentaPorMetodoPago($metodoPagoId);

        return self::generar($fecha, "Venta {$numeroFactura}", [
            ['codigo_cuenta' => $cuentaCobro, 'debe' => $total, 'haber' => 0],
            ['codigo_cuenta' => '4.1', 'debe' => 0, 'haber' => $ventaNeta],
            ['codigo_cuenta' => '2.1.2', 'debe' => 0, 'haber' => $impuesto],
            ['codigo_cuenta' => '5.1', 'debe' => $costoInventario, 'haber' => 0],
            ['codigo_cuenta' => '1.1.4.1', 'debe' => 0, 'haber' => $costoInventario],
        ], 'VENTA:' . $numeroFactura);
    }

    public static function registrarCobro(
        $fecha,
        string $referenciaPago,
        string $numeroFactura,
        float $monto,
        int $metodoPagoId
    ): string {
        $cuentaCobro = self::cuentaPorMetodoPago($metodoPagoId);

        return self::generar($fecha, "Cobro de factura {$numeroFactura}", [
            ['codigo_cuenta' => $cuentaCobro, 'debe' => $monto, 'haber' => 0],
            ['codigo_cuenta' => '1.1.3', 'debe' => 0, 'haber' => $monto],
        ], 'COBRO:' . $referenciaPago);
    }

    public static function registrarIngreso(
        $fecha,
        string $referenciaIngreso,
        float $monto,
        int $metodoPagoId,
        string $descripcion
    ): string {
        $cuentaCobro = self::cuentaPorMetodoPago($metodoPagoId);

        return self::generar($fecha, "Ingreso {$referenciaIngreso}: {$descripcion}", [
            ['codigo_cuenta' => $cuentaCobro, 'debe' => $monto, 'haber' => 0],
            ['codigo_cuenta' => '4.2', 'debe' => 0, 'haber' => $monto],
        ], 'INGRESO:' . $referenciaIngreso);
    }

    public static function registrarMovimientoInventario(
        $fecha,
        string $referencia,
        float $valor,
        bool $esEntrada,
        string $descripcion
    ): string {
        $tipo = Str::lower($descripcion);

        if ($esEntrada) {
            $cuentaContrapartida = Str::contains($tipo, 'devolución')
                ? '5.1'
                : '4.2';
            $lineas = [
                ['codigo_cuenta' => '1.1.4.1', 'debe' => $valor, 'haber' => 0],
                ['codigo_cuenta' => $cuentaContrapartida, 'debe' => 0, 'haber' => $valor],
            ];
        } else {
            $cuentaContrapartida = Str::contains($tipo, 'ajuste')
                ? '5.2'
                : '5.1';
            $lineas = [
                ['codigo_cuenta' => $cuentaContrapartida, 'debe' => $valor, 'haber' => 0],
                ['codigo_cuenta' => '1.1.4.1', 'debe' => 0, 'haber' => $valor],
            ];
        }

        return self::generar(
            $fecha,
            "Movimiento de inventario {$referencia}: {$descripcion}",
            $lineas,
            'INVENTARIO:' . $referencia
        );
    }

    private static function guardar(
        string $fecha,
        string $descripcion,
        array $lineas,
        float $totalDebe,
        float $totalHaber,
        ?string $claveOperacion = null
    ): string {
        $descripcionGuardada = $claveOperacion
            ? self::prefijoOperacion($claveOperacion) . $descripcion
            : $descripcion;

        $estadoId = Estado::where('nombre', 'Aprobado')->value('id')
            ?? Estado::where('nombre', 'Activo')->value('id')
            ?? 1;

        $asientoExistente = $claveOperacion
            ? self::buscarAsientoOperacion($claveOperacion, $descripcion)
            : null;

        $numeroAsiento = $asientoExistente?->numero_asiento
            ?? 'ASI-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(8));

        if ($asientoExistente) {
            DB::table('detalle_asientos_contables')
                ->where('numero_asiento', $asientoExistente->numero_asiento)
                ->where('fecha_asiento', $asientoExistente->fecha)
                ->delete();

            DB::table('asientos_contables')
                ->where('numero_asiento', $asientoExistente->numero_asiento)
                ->where('fecha', $asientoExistente->fecha)
                ->update([
                    'fecha' => $fecha,
                    'descripcion' => $descripcionGuardada,
                    'total_debe' => $totalDebe,
                    'total_haber' => $totalHaber,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('asientos_contables')->insert([
                'numero_asiento' => $numeroAsiento,
                'usuario_id' => Auth::id(),
                'fecha' => $fecha,
                'descripcion' => $descripcionGuardada,
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'estado_id' => $estadoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($lineas as $linea) {
            DB::table('detalle_asientos_contables')->insert([
                'numero_asiento' => $numeroAsiento,
                'fecha_asiento' => $fecha,
                'codigo_cuenta' => $linea['codigo_cuenta'],
                'debe' => $linea['debe'],
                'haber' => $linea['haber'],
                'descripcion' => $linea['descripcion'] ?? $descripcion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $numeroAsiento;
    }

    private static function normalizarLineas(array $lineas): array
    {
        $agrupadas = [];

        foreach ($lineas as $linea) {
            $codigo = $linea['codigo_cuenta'];

            if (! isset($agrupadas[$codigo])) {
                $agrupadas[$codigo] = [
                    'codigo_cuenta' => $codigo,
                    'debe' => 0.0,
                    'haber' => 0.0,
                    'descripcion' => $linea['descripcion'] ?? '',
                ];
            }

            $agrupadas[$codigo]['debe'] += (float) ($linea['debe'] ?? 0);
            $agrupadas[$codigo]['haber'] += (float) ($linea['haber'] ?? 0);
        }

        return array_values(array_filter(array_map(function (array $linea) {
            $linea['debe'] = round($linea['debe'], 2);
            $linea['haber'] = round($linea['haber'], 2);

            return $linea;
        }, $agrupadas), fn (array $linea) => $linea['debe'] > 0 || $linea['haber'] > 0));
    }

    private static function prefijoOperacion(string $claveOperacion): string
    {
        return '[AUTO:' . $claveOperacion . '] ';
    }

    private static function buscarAsientoOperacion(
        string $claveOperacion,
        ?string $descripcionExacta = null
    ): ?object {
        $consulta = DB::table('asientos_contables')
            ->where('descripcion', 'like', self::prefijoOperacion($claveOperacion) . '%')
            ->orderByDesc('created_at');

        $asiento = $consulta->lockForUpdate()->first();

        if ($asiento) {
            return $asiento;
        }

        $descripciones = $descripcionExacta !== null
            ? [$descripcionExacta]
            : self::descripcionesLegadas($claveOperacion);

        if ($descripciones === []) {
            return null;
        }

        return DB::table('asientos_contables')
            ->whereIn('descripcion', $descripciones)
            ->orderByDesc('created_at')
            ->lockForUpdate()
            ->first();
    }

    private static function descripcionesLegadas(string $claveOperacion): array
    {
        if (Str::startsWith($claveOperacion, 'COMPRA:')) {
            $referencia = Str::after($claveOperacion, 'COMPRA:');

            return [
                "Compra a crédito {$referencia}",
                "Compra de contado {$referencia}",
                "Compra {$referencia}",
            ];
        }

        if (Str::startsWith($claveOperacion, 'GASTO:')) {
            $referencia = Str::after($claveOperacion, 'GASTO:');

            return ["Gasto {$referencia}"];
        }

        return [];
    }
}
