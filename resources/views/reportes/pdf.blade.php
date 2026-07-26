<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte financiero - {{ ucfirst($nombrePeriodo) }}</title>
    <style>
        @page { margin: 24px 28px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 0;
        }
        h1, h2, p { margin: 0; }
        .header {
            border-bottom: 3px solid #b71c1c;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .brand { color: #b71c1c; font-size: 20px; font-weight: bold; }
        .title { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .center { text-align: center; }
        .green { color: #15803d; font-weight: bold; }
        .red { color: #b91c1c; font-weight: bold; }
        .amber { color: #a16207; font-weight: bold; }
        .section {
            margin-top: 14px;
            background: #1f2937;
            color: white;
            padding: 7px 9px;
            font-size: 11px;
            font-weight: bold;
        }
        .cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 2px -6px 0;
        }
        .card {
            width: 25%;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 9px;
            vertical-align: top;
        }
        .card-label {
            color: #6b7280;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .card-value {
            margin-top: 5px;
            font-size: 14px;
            font-weight: bold;
        }
        .two-column {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 8px -8px 0;
        }
        .panel {
            width: 50%;
            vertical-align: top;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
        }
        table.data th {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 6px;
            font-weight: bold;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 6px;
        }
        .total-row td {
            background: #f3f4f6;
            font-weight: bold;
        }
        .result-row td {
            background: #1f2937;
            color: white;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .page-break { page-break-before: always; }
        .footer {
            margin-top: 16px;
            border-top: 1px solid #d1d5db;
            padding-top: 7px;
            color: #6b7280;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="brand">Distribuidora Ipacaraí S.A.</div>
                    <div class="title">Informe financiero gerencial</div>
                    <div class="muted">Período: {{ ucfirst($nombrePeriodo) }}</div>
                </td>
                <td class="right muted">
                    Generado: {{ $generadoEn->format('d/m/Y H:i') }}<br>
                    Moneda: colones costarricenses (CRC)
                </td>
            </tr>
        </table>
    </div>

    <table class="cards">
        <tr>
            <td class="card">
                <div class="card-label">Ventas netas</div>
                <div class="card-value">₡{{ number_format($ventasNetas, 2) }}</div>
                <div class="muted">{{ number_format($cantidadFacturas) }} facturas válidas</div>
            </td>
            <td class="card">
                <div class="card-label">Utilidad bruta estimada</div>
                <div class="card-value {{ $utilidadBruta < 0 ? 'red' : 'green' }}">₡{{ number_format($utilidadBruta, 2) }}</div>
                <div class="muted">Ventas netas menos costo mayorista</div>
            </td>
            <td class="card">
                <div class="card-label">Gastos operativos</div>
                <div class="card-value red">₡{{ number_format($gastos, 2) }}</div>
                <div class="muted">{{ number_format($cantidadGastos) }} movimientos</div>
            </td>
            <td class="card">
                <div class="card-label">Resultado estimado</div>
                <div class="card-value {{ $resultado < 0 ? 'red' : 'green' }}">₡{{ number_format($resultado, 2) }}</div>
                <div class="muted">Incluye otros ingresos manuales</div>
            </td>
        </tr>
    </table>

    <table class="two-column">
        <tr>
            <td class="panel">
                <div class="section">Estado de resultados estimado</div>
                <table class="data">
                    <tr><td>Ventas netas</td><td class="right">₡{{ number_format($ventasNetas, 2) }}</td></tr>
                    <tr><td>Costo mayorista estimado de ventas</td><td class="right red">− ₡{{ number_format($costoVentas, 2) }}</td></tr>
                    <tr class="total-row"><td>Utilidad bruta estimada</td><td class="right">₡{{ number_format($utilidadBruta, 2) }}</td></tr>
                    <tr><td>Otros ingresos manuales</td><td class="right green">+ ₡{{ number_format($ingresos, 2) }}</td></tr>
                    <tr><td>Gastos operativos</td><td class="right red">− ₡{{ number_format($gastos, 2) }}</td></tr>
                    <tr class="result-row"><td>Resultado estimado</td><td class="right">₡{{ number_format($resultado, 2) }}</td></tr>
                </table>
            </td>
            <td class="panel">
                <div class="section">Ventas y operación</div>
                <table class="data">
                    <tr><td>Facturación total con impuesto</td><td class="right">₡{{ number_format($ventas, 2) }}</td></tr>
                    <tr><td>Ventas de contado</td><td class="right">₡{{ number_format($ventasContado, 2) }}</td></tr>
                    <tr><td>Ventas a crédito</td><td class="right">₡{{ number_format($ventasCredito, 2) }}</td></tr>
                    <tr><td>Impuesto facturado</td><td class="right">₡{{ number_format($impuestosVentas, 2) }}</td></tr>
                    <tr><td>Compras del período</td><td class="right">₡{{ number_format($compras, 2) }}</td></tr>
                    <tr><td>Pagos a proveedores</td><td class="right">₡{{ number_format($pagosProveedores, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">Liquidez y posición financiera actual</div>
    <table class="cards">
        <tr>
            <td class="card">
                <div class="card-label">Saldo en bancos</div>
                <div class="card-value">₡{{ number_format($saldoBancos, 2) }}</div>
                <div class="muted">Flujo neto: ₡{{ number_format($flujoBancarioNeto, 2) }}</div>
            </td>
            <td class="card">
                <div class="card-label">Cuentas por cobrar</div>
                <div class="card-value amber">₡{{ number_format($cuentasCobrar, 2) }}</div>
                <div class="muted">Vencido: ₡{{ number_format($cuentasCobrarVencidas, 2) }}</div>
            </td>
            <td class="card">
                <div class="card-label">Cuentas por pagar</div>
                <div class="card-value red">₡{{ number_format($cuentasPagar, 2) }}</div>
                <div class="muted">Vencido: ₡{{ number_format($cuentasPagarVencidas, 2) }}</div>
            </td>
            <td class="card">
                <div class="card-label">Inventario mayorista</div>
                <div class="card-value">₡{{ number_format($valorInventario, 2) }}</div>
                <div class="muted">Stock bajo: {{ number_format($productosStockBajo) }} productos</div>
            </td>
        </tr>
    </table>

    <table class="two-column">
        <tr>
            <td class="panel">
                <div class="section">Comparativo mensual</div>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th class="right">Actual</th>
                            <th class="right">Anterior</th>
                            <th class="right">Variación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparativo as $item)
                            <tr>
                                <td>{{ $item->etiqueta }}</td>
                                <td class="right">₡{{ number_format($item->actual, 2) }}</td>
                                <td class="right">₡{{ number_format($item->anterior, 2) }}</td>
                                <td class="right {{ ($item->variacion ?? 0) < 0 ? 'red' : 'green' }}">
                                    {{ $item->variacion === null ? 'Sin base' : number_format($item->variacion, 1) . '%' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td class="panel">
                <div class="section">Cobros y tesorería</div>
                <table class="data">
                    <tr><td>Cobros de ventas de contado</td><td class="right">₡{{ number_format($cobrosContado, 2) }}</td></tr>
                    <tr><td>Cobros de cuentas por cobrar</td><td class="right">₡{{ number_format($cobrosClientes, 2) }}</td></tr>
                    <tr><td>Entradas bancarias</td><td class="right green">₡{{ number_format($entradasBancarias, 2) }}</td></tr>
                    <tr><td>Salidas bancarias</td><td class="right red">₡{{ number_format($salidasBancarias, 2) }}</td></tr>
                    <tr class="total-row"><td>Flujo bancario neto</td><td class="right">₡{{ number_format($flujoBancarioNeto, 2) }}</td></tr>
                    <tr><td>Índice de liquidez simplificado</td><td class="right">{{ $indiceLiquidez === null ? 'Sin obligaciones' : number_format($indiceLiquidez, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <div class="section">Desempeño por producto</div>
    <table class="data">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="right">Unidades</th>
                <th class="right">Facturación sin impuesto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProductos as $producto)
                <tr>
                    <td>{{ $producto->codigo_barras }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td class="right">{{ number_format($producto->cantidad) }}</td>
                    <td class="right">₡{{ number_format($producto->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center muted">No hay ventas registradas en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="two-column">
        <tr>
            <td class="panel">
                <div class="section">Principales categorías de gasto</div>
                <table class="data">
                    <thead><tr><th>Categoría</th><th class="right">Total</th></tr></thead>
                    <tbody>
                        @forelse($gastosPorCategoria as $categoria)
                            <tr>
                                <td>{{ $categoria->categoria }}</td>
                                <td class="right">₡{{ number_format($categoria->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="center muted">Sin gastos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td class="panel">
                <div class="section">Control presupuestario</div>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th class="right">Presupuesto</th>
                            <th class="right">Ejecutado</th>
                            <th class="center">Ejecución</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($presupuestoVsGasto as $item)
                            <tr>
                                <td>{{ $item->categoria }}</td>
                                <td class="right">₡{{ number_format($item->monto_presupuestado, 2) }}</td>
                                <td class="right">₡{{ number_format($item->gasto_real, 2) }}</td>
                                <td class="center">
                                    <span class="badge {{ $item->ejecucion > 100 ? 'badge-red' : ($item->ejecucion >= 80 ? 'badge-amber' : 'badge-green') }}">
                                        {{ number_format($item->ejecucion, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="center muted">No hay presupuesto registrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Reporte generado automáticamente por el Sistema Contable Ipacaraí.
        El costo de ventas se estima con el costo mayorista actual de los productos.
    </div>
</body>
</html>
