<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLA_MAPEO = 'mapeo_referencias_historicas_20260726';

    public function up(): void
    {
        $this->crearTablaMapeo();
        $this->registrarMapeos();
        $this->aplicarMapeos(false);
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLA_MAPEO)) {
            return;
        }

        $this->aplicarMapeos(true);
        Schema::drop(self::TABLA_MAPEO);
    }

    private function crearTablaMapeo(): void
    {
        if (Schema::hasTable(self::TABLA_MAPEO)) {
            return;
        }

        Schema::create(self::TABLA_MAPEO, function (Blueprint $table) {
            $table->string('tipo', 20);
            $table->string('referencia_anterior');
            $table->string('referencia_nueva')->unique();
            $table->dateTime('created_at')->useCurrent();

            $table->primary(['tipo', 'referencia_anterior']);
        });
    }

    private function registrarMapeos(): void
    {
        $referenciasTexto = $this->extraerReferenciasHistoricas();

        $this->registrarReferencias(
            'compra',
            DB::table('compras')->pluck('numero_compra')
                ->merge($referenciasTexto['compra'])
                ->unique(),
            '/^COM-\d{14}$/'
        );
        $this->registrarReferencias(
            'factura',
            DB::table('facturas')->pluck('numero_factura')
                ->merge($referenciasTexto['factura'])
                ->unique(),
            '/^FAC-\d{14}$/'
        );
    }

    private function extraerReferenciasHistoricas(): array
    {
        $referencias = [
            'compra' => collect(),
            'factura' => collect(),
        ];
        $columnas = [
            ['movimientos_inventario', 'referencia_movimiento'],
            ['movimientos_inventario', 'descripcion'],
            ['historial_saldos', 'referencia_documento'],
            ['movimientos_bancarios', 'referencia'],
            ['movimientos_bancarios', 'descripcion'],
            ['ingresos', 'referencia_ingreso'],
            ['ingresos', 'origen'],
            ['ingresos', 'descripcion'],
            ['asientos_contables', 'descripcion'],
            ['detalle_asientos_contables', 'descripcion'],
            ['bitacora', 'descripcion'],
        ];

        foreach ($columnas as [$tabla, $columna]) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            foreach (DB::table($tabla)->pluck($columna) as $texto) {
                preg_match_all('/COM-\d{14}(?!-)/', (string) $texto, $compras);
                preg_match_all('/FAC-\d{14}(?!-)/', (string) $texto, $facturas);

                $referencias['compra'] = $referencias['compra']->merge($compras[0]);
                $referencias['factura'] = $referencias['factura']->merge($facturas[0]);
            }
        }

        $referencias['compra'] = $referencias['compra']->unique()->values();
        $referencias['factura'] = $referencias['factura']->unique()->values();

        return $referencias;
    }

    private function registrarReferencias(
        string $tipo,
        $referencias,
        string $patron
    ): void {
        foreach ($referencias as $referencia) {
            if (! preg_match($patron, $referencia)) {
                continue;
            }

            $referenciaNueva = $referencia . '-' . strtoupper(
                substr(hash('sha256', "{$tipo}:{$referencia}"), 0, 6)
            );

            DB::table(self::TABLA_MAPEO)->insertOrIgnore([
                'tipo' => $tipo,
                'referencia_anterior' => $referencia,
                'referencia_nueva' => $referenciaNueva,
                'created_at' => now(),
            ]);
        }
    }

    private function aplicarMapeos(bool $revertir): void
    {
        $mapeos = DB::table(self::TABLA_MAPEO)
            ->orderBy('tipo')
            ->orderBy('referencia_anterior')
            ->get();

        if ($mapeos->isEmpty()) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($mapeos, $revertir) {
                foreach ($mapeos as $mapeo) {
                    $anterior = $revertir
                        ? $mapeo->referencia_nueva
                        : $mapeo->referencia_anterior;
                    $nueva = $revertir
                        ? $mapeo->referencia_anterior
                        : $mapeo->referencia_nueva;

                    if ($mapeo->tipo === 'compra') {
                        $this->actualizarCompra($anterior, $nueva);
                    } else {
                        $this->actualizarFactura($anterior, $nueva);
                    }

                    $this->actualizarReferenciasComunes($anterior, $nueva);
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function actualizarCompra(string $anterior, string $nueva): void
    {
        $this->actualizarColumna([
            'detalle_compras',
            'cuentas_pagar',
            'pagos_proveedores',
            'pagos_cuentas_pagar',
            'plazos_compra',
            'compras',
        ], 'numero_compra', $anterior, $nueva);

        $this->reemplazarTexto([
            ['pagos_cuentas_pagar', 'observacion'],
        ], $anterior, $nueva);
    }

    private function actualizarFactura(string $anterior, string $nueva): void
    {
        $this->actualizarColumna([
            'detalle_facturas',
            'facturas_electronicas',
            'anulaciones_facturas',
            'envios_comprobantes',
            'cuentas_cobrar',
            'pagos_clientes',
            'pagos_cuentas_cobrar',
            'alertas_morosidad',
            'plazos_venta',
            'facturas',
        ], 'numero_factura', $anterior, $nueva);
    }

    private function actualizarReferenciasComunes(string $anterior, string $nueva): void
    {
        $this->actualizarValorExacto(
            'historial_saldos',
            'referencia_documento',
            $anterior,
            $nueva
        );

        $this->reemplazarTexto([
            ['movimientos_inventario', 'referencia_movimiento'],
            ['movimientos_inventario', 'descripcion'],
            ['movimientos_bancarios', 'referencia'],
            ['movimientos_bancarios', 'descripcion'],
            ['ingresos', 'referencia_ingreso'],
            ['ingresos', 'origen'],
            ['ingresos', 'descripcion'],
            ['asientos_contables', 'descripcion'],
            ['detalle_asientos_contables', 'descripcion'],
            ['bitacora', 'descripcion'],
            ['consultas_realizadas', 'criterio_busqueda'],
            ['reportes_generados', 'parametros'],
            ['reportes_generados', 'descripcion'],
        ], $anterior, $nueva);
    }

    private function actualizarColumna(
        array $tablas,
        string $columna,
        string $anterior,
        string $nueva
    ): void {
        foreach ($tablas as $tabla) {
            $this->actualizarValorExacto($tabla, $columna, $anterior, $nueva);
        }
    }

    private function actualizarValorExacto(
        string $tabla,
        string $columna,
        string $anterior,
        string $nueva
    ): void {
        if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
            return;
        }

        DB::table($tabla)
            ->where($columna, $anterior)
            ->update([$columna => $nueva]);
    }

    private function reemplazarTexto(
        array $columnas,
        string $anterior,
        string $nueva
    ): void {
        foreach ($columnas as [$tabla, $columna]) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            DB::statement(
                "UPDATE {$tabla} SET {$columna} = REPLACE({$columna}, ?, ?) "
                    . "WHERE {$columna} LIKE ?",
                [$anterior, $nueva, "%{$anterior}%"]
            );
        }
    }
};
