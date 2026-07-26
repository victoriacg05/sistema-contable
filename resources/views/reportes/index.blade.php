<x-app-layout>
    @php
        $margenBruto = $ventasNetas > 0 ? ($utilidadBruta / $ventasNetas) * 100 : 0;
        $maxProducto = max(1, (float) ($topProductos->max('total') ?? 0));
        $maxGasto = max(1, (float) ($gastosPorCategoria->max('total') ?? 0));
    @endphp

    <div class="max-w-7xl mx-auto space-y-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="inline-flex items-center rounded-full bg-red-100 px-4 py-2 text-sm font-bold text-red-800">
                    Informe gerencial
                </span>
                <h1 class="mt-4 text-4xl font-extrabold text-gray-900">Reporte financiero</h1>
                <p class="mt-2 text-lg text-gray-600">
                    Resultados, liquidez y operación de {{ ucfirst($nombrePeriodo) }}
                </p>
            </div>

            <a href="{{ route('reportes.pdf', ['anio' => $anio, 'mes' => $mes]) }}"
               class="inline-flex items-center justify-center rounded-2xl bg-gray-900 px-7 py-4 font-bold text-white shadow-md transition hover:bg-black">
                Descargar informe PDF
            </a>
        </div>

        <form method="GET" action="{{ route('reportes.index') }}"
              class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-lg">
            <div class="grid grid-cols-1 items-end gap-5 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Año</label>
                    <input type="number"
                           name="anio"
                           min="2000"
                           max="2100"
                           value="{{ $anio }}"
                           class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-red-700 focus:bg-white focus:ring-2 focus:ring-red-100"
                           required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Mes</label>
                    <select name="mes"
                            class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-red-700 focus:bg-white focus:ring-2 focus:ring-red-100"
                            required>
                        @foreach(range(1, 12) as $numeroMes)
                            <option value="{{ $numeroMes }}" {{ $mes === $numeroMes ? 'selected' : '' }}>
                                {{ ucfirst(\Carbon\Carbon::create(2000, $numeroMes, 1)->locale('es')->translatedFormat('F')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                        class="rounded-2xl bg-[#b71c1c] px-8 py-4 font-bold text-white shadow-md transition hover:bg-red-800">
                    Actualizar período
                </button>
            </div>
        </form>

        <section>
            <div class="mb-4">
                <h2 class="text-2xl font-extrabold text-gray-900">Resumen ejecutivo</h2>
                <p class="mt-1 text-gray-600">Indicadores principales del período seleccionado.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-md">
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Ventas netas</p>
                    <p class="mt-3 text-3xl font-extrabold text-gray-900">₡{{ number_format($ventasNetas, 2) }}</p>
                    <p class="mt-2 text-sm text-gray-500">Sin impuesto y después de descuentos</p>
                </div>

                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-md">
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Utilidad bruta estimada</p>
                    <p class="mt-3 text-3xl font-extrabold {{ $utilidadBruta < 0 ? 'text-red-700' : 'text-green-700' }}">
                        ₡{{ number_format($utilidadBruta, 2) }}
                    </p>
                    <p class="mt-2 text-sm text-gray-500">Margen bruto: {{ number_format($margenBruto, 1) }}%</p>
                </div>

                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-md">
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-500">Gastos operativos</p>
                    <p class="mt-3 text-3xl font-extrabold text-red-700">₡{{ number_format($gastos, 2) }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ number_format($cantidadGastos) }} movimientos registrados</p>
                </div>

                <div class="rounded-3xl border {{ $resultado < 0 ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }} p-6 shadow-md">
                    <p class="text-sm font-bold uppercase tracking-wide {{ $resultado < 0 ? 'text-red-700' : 'text-green-700' }}">
                        Resultado estimado
                    </p>
                    <p class="mt-3 text-3xl font-extrabold {{ $resultado < 0 ? 'text-red-800' : 'text-green-800' }}">
                        ₡{{ number_format($resultado, 2) }}
                    </p>
                    <p class="mt-2 text-sm {{ $resultado < 0 ? 'text-red-700' : 'text-green-700' }}">
                        Utilidad bruta + otros ingresos − gastos
                    </p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="rounded-[2rem] border border-gray-200 bg-white p-7 shadow-lg xl:col-span-2">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Estado de resultados estimado</h2>
                        <p class="mt-1 text-sm text-gray-500">Las compras se presentan como operación, no como gasto directo.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="font-semibold text-gray-700">Ventas netas</span>
                        <span class="font-bold text-gray-900">₡{{ number_format($ventasNetas, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="font-semibold text-gray-700">Costo mayorista estimado de ventas</span>
                        <span class="font-bold text-red-700">− ₡{{ number_format($costoVentas, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-5 py-4">
                        <span class="font-extrabold text-gray-900">Utilidad bruta estimada</span>
                        <span class="text-xl font-extrabold {{ $utilidadBruta < 0 ? 'text-red-700' : 'text-green-700' }}">
                            ₡{{ number_format($utilidadBruta, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="font-semibold text-gray-700">Otros ingresos manuales</span>
                        <span class="font-bold text-green-700">+ ₡{{ number_format($ingresos, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="font-semibold text-gray-700">Gastos operativos</span>
                        <span class="font-bold text-red-700">− ₡{{ number_format($gastos, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-gray-900 px-5 py-4 text-white">
                        <span class="font-extrabold">Resultado estimado del período</span>
                        <span class="text-xl font-extrabold">₡{{ number_format($resultado, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-gray-200 bg-white p-7 shadow-lg">
                <h2 class="text-2xl font-extrabold text-gray-900">Composición de ventas</h2>
                <div class="mt-6 space-y-5">
                    <div>
                        <div class="flex justify-between text-sm font-bold text-gray-700">
                            <span>Contado</span>
                            <span>₡{{ number_format($ventasContado, 2) }}</span>
                        </div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-green-600"
                                 style="width: {{ $ventas > 0 ? min(100, ($ventasContado / $ventas) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm font-bold text-gray-700">
                            <span>Crédito</span>
                            <span>₡{{ number_format($ventasCredito, 2) }}</span>
                        </div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-amber-500"
                                 style="width: {{ $ventas > 0 ? min(100, ($ventasCredito / $ventas) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Facturación total con impuesto</p>
                        <p class="mt-1 text-xl font-extrabold text-gray-900">₡{{ number_format($ventas, 2) }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Impuesto facturado</p>
                        <p class="mt-1 text-xl font-extrabold text-gray-900">₡{{ number_format($impuestosVentas, 2) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-2xl font-extrabold text-gray-900">Liquidez y obligaciones</h2>
                <p class="mt-1 text-gray-600">Saldos actuales y movimientos de tesorería del período.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
                    <p class="text-sm font-bold text-blue-700">Saldo en bancos</p>
                    <p class="mt-2 text-2xl font-extrabold text-blue-900">₡{{ number_format($saldoBancos, 2) }}</p>
                    <p class="mt-2 text-sm text-blue-700">
                        Índice de liquidez: {{ $indiceLiquidez === null ? 'Sin obligaciones' : number_format($indiceLiquidez, 2) }}
                    </p>
                </div>
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
                    <p class="text-sm font-bold text-amber-700">Cuentas por cobrar</p>
                    <p class="mt-2 text-2xl font-extrabold text-amber-900">₡{{ number_format($cuentasCobrar, 2) }}</p>
                    <p class="mt-2 text-sm text-amber-700">
                        Vencido: ₡{{ number_format($cuentasCobrarVencidas, 2) }} ({{ number_format($porcentajeMorosidad, 1) }}%)
                    </p>
                </div>
                <div class="rounded-3xl border border-red-200 bg-red-50 p-6">
                    <p class="text-sm font-bold text-red-700">Cuentas por pagar</p>
                    <p class="mt-2 text-2xl font-extrabold text-red-900">₡{{ number_format($cuentasPagar, 2) }}</p>
                    <p class="mt-2 text-sm text-red-700">Vencido: ₡{{ number_format($cuentasPagarVencidas, 2) }}</p>
                </div>
                <div class="rounded-3xl border border-gray-200 bg-white p-6">
                    <p class="text-sm font-bold text-gray-600">Flujo bancario neto</p>
                    <p class="mt-2 text-2xl font-extrabold {{ $flujoBancarioNeto < 0 ? 'text-red-700' : 'text-green-700' }}">
                        ₡{{ number_format($flujoBancarioNeto, 2) }}
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Entradas ₡{{ number_format($entradasBancarias, 2) }} · Salidas ₡{{ number_format($salidasBancarias, 2) }}
                    </p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-lg">
                <div class="border-b border-gray-200 px-7 py-5">
                    <h2 class="text-xl font-extrabold text-gray-900">Comparativo mensual</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ ucfirst($nombrePeriodo) }} frente a {{ ucfirst($nombrePeriodoAnterior) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 text-sm text-gray-600">
                            <tr>
                                <th class="px-6 py-4 text-left">Indicador</th>
                                <th class="px-6 py-4 text-right">Período actual</th>
                                <th class="px-6 py-4 text-right">Período anterior</th>
                                <th class="px-6 py-4 text-right">Variación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparativo as $item)
                                <tr class="border-t border-gray-100">
                                    <td class="px-6 py-4 font-semibold text-gray-700">{{ $item->etiqueta }}</td>
                                    <td class="px-6 py-4 text-right font-bold">₡{{ number_format($item->actual, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-gray-600">₡{{ number_format($item->anterior, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-bold {{ ($item->variacion ?? 0) < 0 ? 'text-red-700' : 'text-green-700' }}">
                                        {{ $item->variacion === null ? 'Sin base' : number_format($item->variacion, 1) . '%' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-[2rem] border border-gray-200 bg-white p-7 shadow-lg">
                <h2 class="text-xl font-extrabold text-gray-900">Actividad operativa</h2>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-500">Facturas emitidas</p>
                        <p class="mt-1 text-2xl font-extrabold text-gray-900">{{ number_format($cantidadFacturas) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-500">Compras registradas</p>
                        <p class="mt-1 text-2xl font-extrabold text-gray-900">{{ number_format($cantidadCompras) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-500">Compras del período</p>
                        <p class="mt-1 text-xl font-extrabold text-gray-900">₡{{ number_format($compras, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-500">Pagos a proveedores</p>
                        <p class="mt-1 text-xl font-extrabold text-gray-900">₡{{ number_format($pagosProveedores, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-500">Cobros de crédito</p>
                        <p class="mt-1 text-xl font-extrabold text-gray-900">₡{{ number_format($cobrosClientes, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-500">Cobros de contado</p>
                        <p class="mt-1 text-xl font-extrabold text-gray-900">₡{{ number_format($cobrosContado, 2) }}</p>
                    </div>
                    <div class="col-span-2 rounded-2xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Inventario a costo mayorista</p>
                                <p class="mt-1 text-2xl font-extrabold text-gray-900">₡{{ number_format($valorInventario, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Stock bajo</p>
                                <p class="mt-1 text-2xl font-extrabold {{ $productosStockBajo > 0 ? 'text-red-700' : 'text-green-700' }}">
                                    {{ number_format($productosStockBajo) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] border border-gray-200 bg-white p-7 shadow-lg">
                <h2 class="text-xl font-extrabold text-gray-900">Productos con mayor facturación</h2>
                <div class="mt-6 space-y-5">
                    @forelse($topProductos as $producto)
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-bold text-gray-800">{{ $producto->nombre }}</p>
                                    <p class="text-sm text-gray-500">{{ $producto->codigo_barras }} · {{ number_format($producto->cantidad) }} unidades</p>
                                </div>
                                <p class="font-extrabold text-gray-900">₡{{ number_format($producto->total, 2) }}</p>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-[#b71c1c]"
                                     style="width: {{ min(100, ((float) $producto->total / $maxProducto) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-gray-50 p-6 text-center text-gray-500">No hay ventas registradas en este período.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] border border-gray-200 bg-white p-7 shadow-lg">
                <h2 class="text-xl font-extrabold text-gray-900">Principales categorías de gasto</h2>
                <div class="mt-6 space-y-5">
                    @forelse($gastosPorCategoria as $categoria)
                        <div>
                            <div class="flex justify-between gap-4">
                                <p class="font-bold text-gray-800">{{ $categoria->categoria }}</p>
                                <p class="font-extrabold text-gray-900">₡{{ number_format($categoria->total, 2) }}</p>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-amber-500"
                                     style="width: {{ min(100, ((float) $categoria->total / $maxGasto) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-gray-50 p-6 text-center text-gray-500">No hay gastos registrados en este período.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-lg">
            <div class="bg-gray-900 px-7 py-5 text-white">
                <h2 class="text-xl font-extrabold">Control presupuestario</h2>
                <p class="mt-1 text-sm text-gray-300">Presupuesto aprobado frente al gasto ejecutado.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-sm text-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left">Categoría</th>
                            <th class="px-6 py-4 text-right">Presupuesto</th>
                            <th class="px-6 py-4 text-right">Ejecutado</th>
                            <th class="px-6 py-4 text-right">Disponible</th>
                            <th class="px-6 py-4 text-center">Ejecución</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($presupuestoVsGasto as $item)
                            <tr class="border-t border-gray-100">
                                <td class="px-6 py-4 font-semibold text-gray-700">{{ $item->categoria }}</td>
                                <td class="px-6 py-4 text-right font-bold">₡{{ number_format($item->monto_presupuestado, 2) }}</td>
                                <td class="px-6 py-4 text-right font-bold">₡{{ number_format($item->gasto_real, 2) }}</td>
                                <td class="px-6 py-4 text-right font-bold {{ $item->diferencia < 0 ? 'text-red-700' : 'text-green-700' }}">
                                    ₡{{ number_format($item->diferencia, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold
                                        {{ $item->ejecucion > 100 ? 'bg-red-100 text-red-700' : ($item->ejecucion >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-700') }}">
                                        {{ number_format($item->ejecucion, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    No hay presupuesto registrado para este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="pb-4 text-center text-sm text-gray-500">
            Informe generado el {{ $generadoEn->format('d/m/Y H:i') }}. El costo de ventas se estima con el costo mayorista actual de los productos.
        </p>
    </div>
</x-app-layout>
