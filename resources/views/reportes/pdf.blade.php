<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #2b2b2b;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #c62828;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            color: #c62828;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0;
            color: #555;
        }

        .section-title {
            background: #2b2b2b;
            color: white;
            padding: 10px;
            margin-top: 20px;
            font-weight: bold;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary td {
            width: 50%;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .summary strong {
            display: block;
            color: #555;
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f1f1;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .green {
            color: #15803d;
            font-weight: bold;
        }

        .red {
            color: #b91c1c;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Distribuidora Ipacaraí S.A.</h1>
        <p>Reporte financiero del periodo {{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $anio }}</p>
    </div>

    <div class="section-title">Resumen Financiero</div>

    <table class="summary">
        <tr>
            <td>
                <strong>Ventas del mes</strong>
                ₡{{ number_format($ventas, 2) }}
            </td>
            <td>
                <strong>Compras del mes</strong>
                ₡{{ number_format($compras, 2) }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Ingresos adicionales</strong>
                ₡{{ number_format($ingresos, 2) }}
            </td>
            <td>
                <strong>Gastos del mes</strong>
                ₡{{ number_format($gastos, 2) }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Cuentas por cobrar pendientes</strong>
                ₡{{ number_format($cuentasCobrar, 2) }}
            </td>
            <td>
                <strong>Cuentas por pagar pendientes</strong>
                ₡{{ number_format($cuentasPagar, 2) }}
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <strong>Utilidad estimada</strong>
                <span class="{{ $utilidad < 0 ? 'red' : 'green' }}">
                    ₡{{ number_format($utilidad, 2) }}
                </span>
            </td>
        </tr>
    </table>

    <div class="section-title">Presupuesto vs Gasto Real</div>

    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Presupuesto</th>
                <th>Gasto Real</th>
                <th>Diferencia</th>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>
            @forelse($presupuestoVsGasto as $item)
                <tr>
                    <td>{{ $item->categoria }}</td>
                    <td>₡{{ number_format($item->monto_presupuestado, 2) }}</td>
                    <td>₡{{ number_format($item->gasto_real, 2) }}</td>
                    <td class="{{ $item->diferencia < 0 ? 'red' : 'green' }}">
                        ₡{{ number_format($item->diferencia, 2) }}
                    </td>
                    <td>
                        {{ $item->diferencia < 0 ? 'Excedido' : 'Disponible' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay presupuesto registrado para este periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte generado automáticamente por el Sistema Contable Ipacaraí.
    </div>

</body>
</html>