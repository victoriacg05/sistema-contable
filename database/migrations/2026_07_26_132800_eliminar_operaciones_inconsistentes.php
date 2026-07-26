<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COMPRAS = [
        'COM-20260618213322',
        'COM-20260618213249',
    ];

    private const FACTURAS = [
        'FAC-20260617004434',
        'FAC-20260617004348',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $compras = DB::table('compras')
                ->whereIn('numero_compra', self::COMPRAS)
                ->lockForUpdate()
                ->get();
            $facturas = DB::table('facturas')
                ->whereIn('numero_factura', self::FACTURAS)
                ->lockForUpdate()
                ->get();

            // La migración también debe ser segura en instalaciones nuevas,
            // donde estos registros históricos nunca existieron.
            if ($compras->isEmpty() && $facturas->isEmpty()) {
                return;
            }

            $this->validarDocumentosEncontrados(
                $compras,
                'numero_compra',
                self::COMPRAS,
                'compras'
            );
            $this->validarDocumentosEncontrados(
                $facturas,
                'numero_factura',
                self::FACTURAS,
                'facturas'
            );

            $this->ajustarInventario($compras, $facturas);

            foreach ($compras as $compra) {
                $this->eliminarCompra($compra);
            }

            foreach ($facturas as $factura) {
                $this->eliminarFactura($factura);
            }
        });
    }

    private function validarDocumentosEncontrados(
        $documentos,
        string $columna,
        array $esperados,
        string $tipo
    ): void {
        $encontrados = $documentos->pluck($columna)->all();
        $faltantes = array_values(array_diff($esperados, $encontrados));

        if ($faltantes !== [] || $documentos->count() !== count($esperados)) {
            throw new \RuntimeException(
                'No se ejecutó la limpieza porque no se encontraron exactamente los '
                . count($esperados) . " registros de {$tipo}. Faltantes: "
                . implode(', ', $faltantes)
            );
        }
    }

    private function ajustarInventario($compras, $facturas): void
    {
        $ajustes = [];

        foreach ($compras as $compra) {
            $detalles = DB::table('detalle_compras')
                ->where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->get();

            foreach ($detalles as $detalle) {
                $ajustes[$detalle->producto_id] = ($ajustes[$detalle->producto_id] ?? 0)
                    - (int) $detalle->cantidad;
            }
        }

        foreach ($facturas as $factura) {
            if ($this->facturaEstaAnulada($factura)) {
                continue;
            }

            $detalles = DB::table('detalle_facturas')
                ->where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->get();

            foreach ($detalles as $detalle) {
                $ajustes[$detalle->producto_id] = ($ajustes[$detalle->producto_id] ?? 0)
                    + (int) $detalle->cantidad;
            }
        }

        if ($ajustes === []) {
            return;
        }

        $productos = DB::table('productos')
            ->whereIn('id', array_keys($ajustes))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($ajustes as $productoId => $ajuste) {
            $producto = $productos->get($productoId);

            if (! $producto) {
                throw new \RuntimeException(
                    "No se encontró el producto {$productoId} necesario para reconciliar el inventario."
                );
            }

            $stockNuevo = (int) $producto->stock + $ajuste;

            if ($stockNuevo < 0) {
                throw new \RuntimeException(
                    "La eliminación dejaría el producto {$producto->nombre} con stock negativo."
                );
            }

            DB::table('productos')
                ->where('id', $productoId)
                ->update([
                    'stock' => $stockNuevo,
                    'updated_at' => now(),
                ]);
        }
    }

    private function eliminarCompra(object $compra): void
    {
        $pagos = Schema::hasTable('pagos_cuentas_pagar')
            ? DB::table('pagos_cuentas_pagar')
                ->where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->get()
            : collect();
        $pagosLegados = Schema::hasTable('pagos_proveedores')
            ? DB::table('pagos_proveedores')
                ->where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->get()
            : collect();
        $referenciasBanco = collect([$compra->numero_compra])
            ->merge($pagosLegados->pluck('referencia_pago'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->neutralizarBanco(
            $referenciasBanco,
            (int) $compra->usuario_id,
            "Ajuste por eliminación de compra {$compra->numero_compra}",
            'LIMPIEZA-' . $compra->numero_compra
        );

        $clavesAsiento = [
            'COMPRA:' . $compra->numero_compra,
            'REVERSO-COMPRA:' . $compra->numero_compra,
        ];

        foreach ($pagos as $pago) {
            $clavesAsiento[] = 'PAGO-PROVEEDOR:' . $pago->id;
        }

        $this->eliminarAsientos($clavesAsiento, [
            "Compra a crédito {$compra->numero_compra}",
            "Compra de contado {$compra->numero_compra}",
            "Compra {$compra->numero_compra}",
            "Pago de compra a crédito {$compra->numero_compra}",
        ]);

        DB::table('movimientos_inventario')
            ->where('referencia_movimiento', $compra->numero_compra)
            ->delete();
        DB::table('historial_saldos')
            ->where('referencia_documento', $compra->numero_compra)
            ->delete();

        $this->registrarLimpieza(
            (int) $compra->usuario_id,
            'compras',
            "Se eliminó la compra inconsistente {$compra->numero_compra} y se reconciliaron sus módulos asociados."
        );

        DB::table('compras')
            ->where('numero_compra', $compra->numero_compra)
            ->where('proveedor_id', $compra->proveedor_id)
            ->delete();
    }

    private function eliminarFactura(object $factura): void
    {
        $pagos = collect();

        foreach (['pagos_cuentas_cobrar', 'pagos_clientes'] as $tablaPagos) {
            if (! Schema::hasTable($tablaPagos)) {
                continue;
            }

            $pagos = $pagos->merge(
                DB::table($tablaPagos)
                    ->where('numero_factura', $factura->numero_factura)
                    ->where('cliente_id', $factura->cliente_id)
                    ->get()
            );
        }

        $referenciasPago = $pagos->pluck('referencia_pago')
            ->filter()
            ->unique()
            ->values();
        $referenciasBanco = collect([$factura->numero_factura])
            ->merge($referenciasPago)
            ->unique()
            ->values()
            ->all();

        $this->neutralizarBanco(
            $referenciasBanco,
            (int) $factura->usuario_id,
            "Ajuste por eliminación de factura {$factura->numero_factura}",
            'LIMPIEZA-' . $factura->numero_factura
        );

        DB::table('ingresos')
            ->where('referencia_ingreso', 'AUTO-VENTA-' . $factura->numero_factura)
            ->delete();

        foreach ($referenciasPago as $referenciaPago) {
            DB::table('ingresos')
                ->where('referencia_ingreso', 'AUTO-COBRO-' . $referenciaPago)
                ->delete();
        }

        $clavesAsiento = [
            'VENTA:' . $factura->numero_factura,
            'REVERSO-VENTA:' . $factura->numero_factura,
            'ANULACION-VENTA:' . $factura->numero_factura,
        ];

        foreach ($referenciasPago as $referenciaPago) {
            $clavesAsiento[] = 'COBRO:' . $referenciaPago;
        }

        $this->eliminarAsientos($clavesAsiento, [
            "Venta {$factura->numero_factura}",
            "Cobro de factura {$factura->numero_factura}",
            "Anulación de factura {$factura->numero_factura}",
            "Reversión de factura eliminada {$factura->numero_factura}",
        ]);

        DB::table('movimientos_inventario')
            ->where('referencia_movimiento', $factura->numero_factura)
            ->delete();
        DB::table('historial_saldos')
            ->where('referencia_documento', $factura->numero_factura)
            ->delete();

        $this->registrarLimpieza(
            (int) $factura->usuario_id,
            'facturas',
            "Se eliminó la factura inconsistente {$factura->numero_factura} y se reconciliaron sus módulos asociados."
        );

        DB::table('facturas')
            ->where('numero_factura', $factura->numero_factura)
            ->where('cliente_id', $factura->cliente_id)
            ->delete();
    }

    private function facturaEstaAnulada(object $factura): bool
    {
        $estado = DB::table('estados')
            ->where('id', $factura->estado_id)
            ->value('nombre');

        return strtolower((string) $estado) === 'anulado'
            || DB::table('anulaciones_facturas')
                ->where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->exists();
    }

    private function neutralizarBanco(
        array $referencias,
        int $usuarioId,
        string $descripcion,
        string $referenciaAjuste
    ): void {
        if ($referencias === [] || ! Schema::hasTable('movimientos_bancarios')) {
            return;
        }

        $movimientos = DB::table('movimientos_bancarios')
            ->whereIn('referencia', $referencias)
            ->get()
            ->groupBy('cuenta_bancaria_id');

        foreach ($movimientos as $cuentaId => $movimientosCuenta) {
            $cuenta = DB::table('cuentas_bancarias')
                ->where('id', $cuentaId)
                ->lockForUpdate()
                ->first();

            if (! $cuenta) {
                continue;
            }

            $efectoNeto = round($movimientosCuenta->sum(function ($movimiento) {
                return $movimiento->tipo === 'entrada'
                    ? (float) $movimiento->monto
                    : -(float) $movimiento->monto;
            }), 2);

            if (abs($efectoNeto) < 0.01) {
                continue;
            }

            $saldoAnterior = (float) $cuenta->saldo;
            $saldoNuevo = round($saldoAnterior - $efectoNeto, 2);

            DB::table('cuentas_bancarias')
                ->where('id', $cuentaId)
                ->update([
                    'saldo' => $saldoNuevo,
                    'updated_at' => now(),
                ]);

            DB::table('movimientos_bancarios')->insert([
                'cuenta_bancaria_id' => $cuentaId,
                'tipo' => $efectoNeto > 0 ? 'salida' : 'entrada',
                'monto' => abs($efectoNeto),
                'descripcion' => $descripcion,
                'referencia' => $referenciaAjuste,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'usuario_id' => $usuarioId,
                'fecha' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function eliminarAsientos(array $claves, array $descripciones): void
    {
        $asientos = DB::table('asientos_contables')
            ->where(function ($consulta) use ($claves, $descripciones) {
                foreach ($claves as $clave) {
                    $consulta->orWhere('descripcion', 'like', "[AUTO:{$clave}] %");
                }

                foreach ($descripciones as $descripcion) {
                    $consulta->orWhere('descripcion', $descripcion);
                }
            })
            ->get(['numero_asiento', 'fecha']);

        foreach ($asientos as $asiento) {
            DB::table('detalle_asientos_contables')
                ->where('numero_asiento', $asiento->numero_asiento)
                ->where('fecha_asiento', $asiento->fecha)
                ->delete();
            DB::table('asientos_contables')
                ->where('numero_asiento', $asiento->numero_asiento)
                ->where('fecha', $asiento->fecha)
                ->delete();
        }
    }

    private function registrarLimpieza(int $usuarioId, string $tabla, string $descripcion): void
    {
        if (! Schema::hasTable('bitacora')) {
            return;
        }

        DB::table('bitacora')->insertOrIgnore([
            'usuario_id' => $usuarioId,
            'accion' => 'eliminar',
            'tabla_afectada' => $tabla,
            'descripcion' => $descripcion,
            'fecha' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
    }
};
