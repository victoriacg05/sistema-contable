<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; }
        .encabezado {
            background-color: #b71c1c; color: #ffffff;
            padding: 20px 24px; overflow: hidden;
        }
        .encabezado .empresa { font-size: 20px; font-weight: bold; }
        .encabezado .tipo { color: #f3d3d3; font-size: 12px; }
        .encabezado .numero { text-align: right; }
        .seccion { padding: 20px 24px; }
        .datos-tabla { width: 100%; }
        .datos-tabla td { vertical-align: top; width: 50%; }
        .etiqueta { color: #6b7280; font-size: 10px; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; }
        .nombre { font-size: 14px; font-weight: bold; }
        .der { text-align: right; }
        table.detalle { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.detalle thead th {
            background-color: #2b2b2b; color: #ffffff;
            padding: 8px 10px; text-align: left;
        }
        table.detalle tbody td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        .totales { width: 260px; margin-left: auto; margin-top: 12px; }
        .totales td { padding: 4px 0; }
        .totales .total-final td {
            border-top: 1px solid #d1d5db; font-weight: bold;
            color: #b71c1c; font-size: 14px; padding-top: 6px;
        }
        .estado {
            display: inline-block; padding: 4px 12px; border-radius: 999px;
            font-size: 11px; font-weight: bold;
        }
        .estado-pagado { background-color: #dcfce7; color: #15803d; }
        .estado-anulado { background-color: #e5e7eb; color: #4b5563; }
        .estado-pendiente { background-color: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    @php $estadoNombre = strtolower(optional($factura->estado)->nombre ?? ''); @endphp

    <div class="encabezado">
        <table style="width:100%;">
            <tr>
                <td>
                    <div class="empresa">Distribuidora Ipacaraí</div>
                    <div class="tipo">{{ $tipoComprobante ?? 'Factura' }}</div>
                </td>
                <td class="numero">
                    <div class="tipo">N° de factura</div>
                    <div style="font-size:16px; font-weight:bold;">{{ $factura->numero_factura }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <table class="datos-tabla">
            <tr>
                <td>
                    <div class="etiqueta">Cliente</div>
                    <div class="nombre">{{ optional($factura->cliente)->nombre ?? 'Sin cliente' }}</div>
                    @if(optional($factura->cliente)->identificacion)
                        <div>Identificación: {{ $factura->cliente->identificacion }}</div>
                    @endif
                    @if(optional($factura->cliente)->email)
                        <div>{{ $factura->cliente->email }}</div>
                    @endif
                    @if(optional($factura->cliente)->telefono)
                        <div>Tel: {{ $factura->cliente->telefono }}</div>
                    @endif
                </td>
                <td class="der">
                    <div class="etiqueta">Detalles</div>
                    <div>Fecha: {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}</div>
                    <div>Método de pago: {{ optional($factura->metodoPago)->nombre ?? '—' }}</div>
                    <div>Registrada por: {{ $usuario ?? '—' }}</div>
                    <div style="margin-top:6px;">
                        @if($estadoNombre === 'pagado')
                            <span class="estado estado-pagado">Pagado</span>
                        @elseif($estadoNombre === 'anulado')
                            <span class="estado estado-anulado">Anulado</span>
                        @else
                            <span class="estado estado-pendiente">Pendiente</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion" style="padding-top:0;">
        <table class="detalle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cantidad</th>
                    <th style="text-align:right;">Precio unitario</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factura->detalles as $detalle)
                    <tr>
                        <td>{{ optional($detalle->producto)->nombre ?? 'Producto' }}</td>
                        <td style="text-align:center;">{{ $detalle->cantidad }}</td>
                        <td style="text-align:right;">₡{{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td style="text-align:right;">₡{{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:16px;">Sin productos en la factura.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totales">
            <tr>
                <td>Subtotal</td>
                <td class="der">₡{{ number_format($factura->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Impuesto (13%)</td>
                <td class="der">₡{{ number_format($factura->impuesto, 2) }}</td>
            </tr>
            <tr>
                <td>Descuento</td>
                <td class="der">₡{{ number_format($factura->descuento, 2) }}</td>
            </tr>
            <tr class="total-final">
                <td>Total</td>
                <td class="der">₡{{ number_format($factura->total, 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
