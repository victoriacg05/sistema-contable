<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FECHA_CORTE = '2026-07-26';
    private const DESDE = '2026-07-26 00:00:00';

    public function up(): void
    {
        DB::transaction(function () {
            $compras = DB::table('compras')
                ->whereDate('fecha', '>=', self::FECHA_CORTE)
                ->lockForUpdate()
                ->get();
            $facturas = DB::table('facturas')
                ->whereDate('fecha', '>=', self::FECHA_CORTE)
                ->lockForUpdate()
                ->get();

            $comprasRefs = $compras->pluck('numero_compra')->unique()->values();
            $facturasRefs = $facturas->pluck('numero_factura')->unique()->values();
            $anulacionesHistoricas = DB::table('anulaciones_facturas')
                ->join('facturas', function ($join) {
                    $join->on(
                        'anulaciones_facturas.numero_factura',
                        '=',
                        'facturas.numero_factura'
                    )->on(
                        'anulaciones_facturas.cliente_id',
                        '=',
                        'facturas.cliente_id'
                    );
                })
                ->whereDate('anulaciones_facturas.fecha_anulacion', '>=', self::FECHA_CORTE)
                ->whereDate('facturas.fecha', '<', self::FECHA_CORTE)
                ->pluck('anulaciones_facturas.numero_factura')
                ->unique()
                ->values();

            $this->revertirInventario();
            $this->revertirMovimientosBancarios($anulacionesHistoricas);
            $this->recalcularCuentasConPagosRecientes($comprasRefs, $facturasRefs);
            $this->eliminarAsientosRecientes($anulacionesHistoricas);

            $this->eliminarDesde('ingresos', 'fecha');
            $this->eliminarDesde('gastos', 'fecha');
            $this->eliminarDesde('presupuesto', 'created_at');
            $this->eliminarDesde('historial_saldos', 'fecha');
            $this->eliminarDesde('reportes_generados', 'fecha_generacion');
            $this->eliminarDesde('consultas_realizadas', 'fecha_consulta');
            $this->eliminarDesde('bitacora', 'fecha');
            $this->eliminarDesde('intentos_acceso', 'fecha');

            foreach ($compras as $compra) {
                DB::table('compras')
                    ->where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->delete();
            }

            foreach ($facturas as $factura) {
                DB::table('facturas')
                    ->where('numero_factura', $factura->numero_factura)
                    ->where('cliente_id', $factura->cliente_id)
                    ->delete();
            }

            $this->eliminarMaestrosDePrueba();
            $this->eliminarConfiguracionDePrueba();
        });
    }

    private function revertirInventario(): void
    {
        $movimientos = DB::table('movimientos_inventario')
            ->join(
                'tipos_movimiento_inventario',
                'movimientos_inventario.tipo_movimiento_inventario_id',
                '=',
                'tipos_movimiento_inventario.id'
            )
            ->whereDate('movimientos_inventario.fecha', '>=', self::FECHA_CORTE)
            ->select(
                'movimientos_inventario.referencia_movimiento',
                'movimientos_inventario.producto_id',
                'movimientos_inventario.cantidad',
                'tipos_movimiento_inventario.nombre as tipo_nombre'
            )
            ->get();

        $facturasAnuladas = DB::table('anulaciones_facturas')
            ->pluck('numero_factura')
            ->flip();
        $ajustes = [];

        foreach ($movimientos as $movimiento) {
            if ($facturasAnuladas->has($movimiento->referencia_movimiento)) {
                continue;
            }

            $esEntrada = in_array(mb_strtolower($movimiento->tipo_nombre), [
                'entrada',
                'compra',
                'ajuste positivo',
                'devolución',
            ], true);

            $ajustes[$movimiento->producto_id] = ($ajustes[$movimiento->producto_id] ?? 0)
                + ($esEntrada ? -(int) $movimiento->cantidad : (int) $movimiento->cantidad);
        }

        if ($ajustes !== []) {
            $productos = DB::table('productos')
                ->whereIn('id', array_keys($ajustes))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($ajustes as $productoId => $ajuste) {
                $producto = $productos->get($productoId);

                if (! $producto) {
                    throw new RuntimeException(
                        "No se encontró el producto {$productoId} necesario para restaurar el inventario."
                    );
                }

                $stockNuevo = (int) $producto->stock + $ajuste;

                if ($stockNuevo < 0) {
                    throw new RuntimeException(
                        "La limpieza dejaría el producto {$producto->nombre} con stock negativo."
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

        DB::table('movimientos_inventario')
            ->whereDate('fecha', '>=', self::FECHA_CORTE)
            ->delete();
    }

    private function revertirMovimientosBancarios($referenciasProtegidas): void
    {
        if (! Schema::hasTable('movimientos_bancarios')) {
            return;
        }

        $consultaMovimientos = DB::table('movimientos_bancarios')
            ->whereDate('fecha', '>=', self::FECHA_CORTE)
            ->where(function ($consulta) {
                $consulta->whereNull('referencia')
                    ->orWhere('referencia', 'not like', 'LIMPIEZA-%');
            });

        if ($referenciasProtegidas->isNotEmpty()) {
            $consultaMovimientos->whereNotIn('referencia', $referenciasProtegidas);
        }

        $movimientosRecientes = $consultaMovimientos->get();
        $referencias = $movimientosRecientes->pluck('referencia')->filter()->unique();
        $referenciasNeutralizadas = $referencias->isEmpty()
            ? collect()
            : DB::table('movimientos_bancarios')
                ->whereIn(
                    'referencia',
                    $referencias->map(fn ($referencia) => 'LIMPIEZA-' . $referencia)
                )
                ->pluck('referencia')
                ->map(fn ($referencia) => substr($referencia, strlen('LIMPIEZA-')))
                ->unique()
                ->values();

        $movimientos = $movimientosRecientes
            ->reject(fn ($movimiento) => $referenciasNeutralizadas->contains($movimiento->referencia))
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

            DB::table('cuentas_bancarias')
                ->where('id', $cuentaId)
                ->update([
                    'saldo' => round((float) $cuenta->saldo - $efectoNeto, 2),
                    'updated_at' => now(),
                ]);
        }

        $consultaEliminar = DB::table('movimientos_bancarios')
            ->whereDate('fecha', '>=', self::FECHA_CORTE)
            ->where(function ($consulta) {
                $consulta->whereNull('referencia')
                    ->orWhere('referencia', 'not like', 'LIMPIEZA-%');
            });

        if ($referenciasProtegidas->isNotEmpty()) {
            $consultaEliminar->whereNotIn('referencia', $referenciasProtegidas);
        }

        if ($referenciasNeutralizadas->isNotEmpty()) {
            $consultaEliminar->whereNotIn('referencia', $referenciasNeutralizadas);
        }

        $consultaEliminar->delete();

        if ($referenciasNeutralizadas->isNotEmpty()) {
            DB::table('movimientos_bancarios')
                ->whereIn('referencia', $referenciasNeutralizadas)
                ->orWhereIn(
                    'referencia',
                    $referenciasNeutralizadas->map(
                        fn ($referencia) => 'LIMPIEZA-' . $referencia
                    )
                )
                ->delete();
        }
    }

    private function recalcularCuentasConPagosRecientes($comprasRefs, $facturasRefs): void
    {
        $facturasAfectadas = DB::table('pagos_clientes')
            ->whereDate('fecha_pago', '>=', self::FECHA_CORTE)
            ->whereNotIn('numero_factura', $facturasRefs)
            ->get(['numero_factura', 'cliente_id'])
            ->unique(fn ($pago) => $pago->numero_factura . '|' . $pago->cliente_id);

        foreach ($facturasAfectadas as $pago) {
            $pagadoAnterior = (float) DB::table('pagos_clientes')
                ->where('numero_factura', $pago->numero_factura)
                ->where('cliente_id', $pago->cliente_id)
                ->whereDate('fecha_pago', '<', self::FECHA_CORTE)
                ->sum('monto');

            $this->restaurarCuenta(
                'cuentas_cobrar',
                'plazos_venta',
                'numero_factura',
                $pago->numero_factura,
                'cliente_id',
                $pago->cliente_id,
                $pagadoAnterior
            );
        }

        DB::table('pagos_clientes')
            ->whereDate('fecha_pago', '>=', self::FECHA_CORTE)
            ->delete();

        $comprasAfectadas = collect();

        if (Schema::hasTable('pagos_cuentas_pagar')) {
            $comprasAfectadas = DB::table('pagos_cuentas_pagar')
                ->whereDate('fecha_pago', '>=', self::FECHA_CORTE)
                ->whereNotIn('numero_compra', $comprasRefs)
                ->get(['numero_compra', 'proveedor_id'])
                ->unique(fn ($pago) => $pago->numero_compra . '|' . $pago->proveedor_id);
        }

        if (Schema::hasTable('pagos_proveedores')) {
            $comprasAfectadas = $comprasAfectadas
                ->merge(
                    DB::table('pagos_proveedores')
                        ->whereDate('fecha_pago', '>=', self::FECHA_CORTE)
                        ->whereNotIn('numero_compra', $comprasRefs)
                        ->get(['numero_compra', 'proveedor_id'])
                )
                ->unique(fn ($pago) => $pago->numero_compra . '|' . $pago->proveedor_id);
        }

        foreach ($comprasAfectadas as $pago) {
            $pagadoAnterior = (float) DB::table('pagos_cuentas_pagar')
                ->where('numero_compra', $pago->numero_compra)
                ->where('proveedor_id', $pago->proveedor_id)
                ->whereDate('fecha_pago', '<', self::FECHA_CORTE)
                ->sum('monto_pagado');

            if (Schema::hasTable('pagos_proveedores')) {
                $pagadoAnterior += (float) DB::table('pagos_proveedores')
                    ->where('numero_compra', $pago->numero_compra)
                    ->where('proveedor_id', $pago->proveedor_id)
                    ->whereDate('fecha_pago', '<', self::FECHA_CORTE)
                    ->sum('monto');
            }

            $this->restaurarCuenta(
                'cuentas_pagar',
                'plazos_compra',
                'numero_compra',
                $pago->numero_compra,
                'proveedor_id',
                $pago->proveedor_id,
                $pagadoAnterior
            );
        }

        if (Schema::hasTable('pagos_cuentas_pagar')) {
            DB::table('pagos_cuentas_pagar')
                ->whereDate('fecha_pago', '>=', self::FECHA_CORTE)
                ->delete();
        }

        if (Schema::hasTable('pagos_proveedores')) {
            DB::table('pagos_proveedores')
                ->whereDate('fecha_pago', '>=', self::FECHA_CORTE)
                ->delete();
        }
    }

    private function restaurarCuenta(
        string $tablaCuenta,
        string $tablaPlazos,
        string $documentoColumna,
        string $documento,
        string $terceroColumna,
        int $terceroId,
        float $pagadoAnterior
    ): void {
        $cuenta = DB::table($tablaCuenta)
            ->where($documentoColumna, $documento)
            ->where($terceroColumna, $terceroId)
            ->lockForUpdate()
            ->first();

        if (! $cuenta) {
            return;
        }

        $saldo = max(0, round((float) $cuenta->monto_original - $pagadoAnterior, 2));
        $estadoNombre = match (true) {
            $saldo <= 0 => 'pagado',
            $saldo < (float) $cuenta->monto_original => 'parcial',
            default => 'pendiente',
        };
        $estado = DB::table('estados')
            ->whereRaw('LOWER(nombre) = ?', [$estadoNombre])
            ->value('id');

        DB::table($tablaCuenta)
            ->where($documentoColumna, $documento)
            ->where($terceroColumna, $terceroId)
            ->update([
                'saldo_pendiente' => $saldo,
                'estado_id' => $estado ?? $cuenta->estado_id,
                'updated_at' => now(),
            ]);

        if (! Schema::hasTable($tablaPlazos)) {
            return;
        }

        $restante = $pagadoAnterior;
        $plazos = DB::table($tablaPlazos)
            ->where($documentoColumna, $documento)
            ->where($terceroColumna, $terceroId)
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->get();

        foreach ($plazos as $plazo) {
            $aplicado = min($restante, (float) $plazo->monto);
            $restante -= $aplicado;

            DB::table($tablaPlazos)
                ->where('id', $plazo->id)
                ->update([
                    'saldo_pendiente' => round((float) $plazo->monto - $aplicado, 2),
                    'updated_at' => now(),
                ]);
        }
    }

    private function eliminarAsientosRecientes($referenciasProtegidas): void
    {
        $consultaAsientos = DB::table('asientos_contables')
            ->whereDate('fecha', '>=', self::FECHA_CORTE);

        foreach ($referenciasProtegidas as $referencia) {
            $consultaAsientos->where('descripcion', 'not like', "%{$referencia}%");
        }

        $asientos = $consultaAsientos->get(['numero_asiento', 'fecha']);

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

    private function eliminarDesde(string $tabla, string $columnaFecha): void
    {
        if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, $columnaFecha)) {
            DB::table($tabla)
                ->whereDate($columnaFecha, '>=', self::FECHA_CORTE)
                ->delete();
        }
    }

    private function eliminarMaestrosDePrueba(): void
    {
        DB::table('productos')
            ->where('created_at', '>=', self::DESDE)
            ->whereNotExists(function ($consulta) {
                $consulta->selectRaw('1')
                    ->from('detalle_compras')
                    ->whereColumn('detalle_compras.producto_id', 'productos.id');
            })
            ->whereNotExists(function ($consulta) {
                $consulta->selectRaw('1')
                    ->from('detalle_facturas')
                    ->whereColumn('detalle_facturas.producto_id', 'productos.id');
            })
            ->whereNotExists(function ($consulta) {
                $consulta->selectRaw('1')
                    ->from('movimientos_inventario')
                    ->whereColumn('movimientos_inventario.producto_id', 'productos.id');
            })
            ->delete();

        DB::table('clientes')
            ->where('created_at', '>=', self::DESDE)
            ->whereNotExists(function ($consulta) {
                $consulta->selectRaw('1')
                    ->from('facturas')
                    ->whereColumn('facturas.cliente_id', 'clientes.id');
            })
            ->delete();

        DB::table('proveedores')
            ->where('created_at', '>=', self::DESDE)
            ->whereNotExists(function ($consulta) {
                $consulta->selectRaw('1')
                    ->from('compras')
                    ->whereColumn('compras.proveedor_id', 'proveedores.id');
            })
            ->delete();
    }

    private function eliminarConfiguracionDePrueba(): void
    {
        $this->eliminarRegistrosSinReferencias(
            'users',
            DB::table('users')
                ->where('created_at', '>=', self::DESDE)
                ->where('email', '!=', 'admin@ipacarai.com')
                ->pluck('id'),
            [
                ['asientos_contables', 'usuario_id'],
                ['facturas', 'usuario_id'],
                ['compras', 'usuario_id'],
                ['pagos_clientes', 'usuario_id'],
                ['pagos_proveedores', 'usuario_id'],
                ['ingresos', 'usuario_id'],
                ['gastos', 'usuario_id'],
                ['movimientos_inventario', 'usuario_id'],
                ['historial_saldos', 'usuario_id'],
                ['reportes_generados', 'usuario_id'],
                ['consultas_realizadas', 'usuario_id'],
                ['bitacora', 'usuario_id'],
                ['alertas_morosidad', 'usuario_id'],
                ['anulaciones_facturas', 'usuario_id'],
            ]
        );

        $this->eliminarRegistrosSinReferencias(
            'categorias_productos',
            DB::table('categorias_productos')
                ->where('created_at', '>=', self::DESDE)
                ->whereNotIn('nombre', ['Bebidas', 'Alimentos', 'Limpieza', 'Otros'])
                ->pluck('id'),
            [['productos', 'categoria_producto_id']]
        );

        $this->eliminarRegistrosSinReferencias(
            'categorias_gastos',
            DB::table('categorias_gastos')
                ->where('created_at', '>=', self::DESDE)
                ->whereNotIn('nombre', [
                    'Servicios públicos',
                    'Alquiler',
                    'Salarios',
                    'Transporte',
                    'Suministros',
                    'Mantenimiento',
                    'Impuestos',
                    'Otros',
                ])
                ->pluck('id'),
            [
                ['gastos', 'categoria_gasto_id'],
                ['presupuesto', 'categoria_gasto_id'],
            ]
        );

        $this->eliminarRegistrosSinReferencias(
            'metodos_pago',
            DB::table('metodos_pago')
                ->where('created_at', '>=', self::DESDE)
                ->whereNotIn('nombre', [
                    'Efectivo',
                    'Transferencia bancaria',
                    'Tarjeta de crédito',
                    'Tarjeta de débito',
                    'Cheque',
                    'SINPE Móvil',
                    'Crédito',
                ])
                ->pluck('id'),
            [
                ['facturas', 'metodo_pago_id'],
                ['compras', 'metodo_pago_id'],
                ['pagos_clientes', 'metodo_pago_id'],
                ['pagos_proveedores', 'metodo_pago_id'],
                ['pagos_cuentas_pagar', 'metodo_pago_id'],
                ['ingresos', 'metodo_pago_id'],
                ['gastos', 'metodo_pago_id'],
            ]
        );

        $this->eliminarRegistrosSinReferencias(
            'estados',
            DB::table('estados')
                ->where('created_at', '>=', self::DESDE)
                ->whereNotIn('nombre', [
                    'Pendiente',
                    'Pagado',
                    'Parcial',
                    'Vencido',
                    'Anulado',
                    'Activo',
                    'Inactivo',
                    'Aprobado',
                ])
                ->pluck('id'),
            [
                ['asientos_contables', 'estado_id'],
                ['facturas', 'estado_id'],
                ['compras', 'estado_id'],
                ['anulaciones_facturas', 'estado_id'],
                ['cuentas_cobrar', 'estado_id'],
                ['cuentas_pagar', 'estado_id'],
                ['alertas_morosidad', 'estado_id'],
            ]
        );
    }

    private function eliminarRegistrosSinReferencias(
        string $tabla,
        $identificadores,
        array $referencias
    ): void {
        foreach ($identificadores as $id) {
            $tieneReferencias = collect($referencias)->contains(function ($referencia) use ($id) {
                [$tablaReferencia, $columna] = $referencia;

                return Schema::hasTable($tablaReferencia)
                    && Schema::hasColumn($tablaReferencia, $columna)
                    && DB::table($tablaReferencia)->where($columna, $id)->exists();
            });

            if ($tieneReferencias) {
                continue;
            }

            if ($tabla === 'users' && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $id)->delete();
            }

            DB::table($tabla)->where('id', $id)->delete();
        }
    }

    public function down(): void
    {
    }
};
