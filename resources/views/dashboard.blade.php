<x-app-layout>

<div>

    @if(Auth::check() && Auth::user()->rol_id == 1)

    <div class="mb-6 inline-flex items-center bg-[#c62828] text-white px-5 py-3 rounded-2xl font-semibold shadow-md">

    Panel Administrativo

    </div>

    @endif

    <!-- Encabezado -->
    <div class="mb-10">

        <h1 class="text-4xl font-bold text-[#2b2b2b]">
            Dashboard
        </h1>

        <p class="text-gray-500 mt-2">
            Resumen general del sistema contable
        </p>

    </div>

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <!-- Ventas -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">

            <p class="text-gray-500 text-sm mb-3">
                Ventas Totales
            </p>

            <h2 class="text-3xl font-bold text-[#c62828]">

                ₡{{ number_format($ventasTotales, 2) }}

            </h2>

        </div>

        <!-- Facturas Pagadas -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">

            <p class="text-gray-500 text-sm mb-3">
                Facturas Pagadas
            </p>

            <h2 class="text-3xl font-bold text-green-600">

                {{ $facturasPagadas }}

            </h2>

        </div>

        <!-- Facturas Pendientes -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">

            <p class="text-gray-500 text-sm mb-3">
                Facturas Pendientes
            </p>

            <h2 class="text-3xl font-bold text-yellow-500">

                {{ $facturasPendientes }}

            </h2>

        </div>

        <!-- Clientes -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">

            <p class="text-gray-500 text-sm mb-3">
                Clientes Registrados
            </p>

            <h2 class="text-3xl font-bold text-[#2b2b2b]">

                {{ $clientesRegistrados }}

            </h2>

        </div>

        <!-- Productos -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">

            <p class="text-gray-500 text-sm mb-3">
                Productos Registrados
            </p>

            <h2 class="text-3xl font-bold text-[#2b2b2b]">

                {{ $productosRegistrados }}

            </h2>

        </div>

        <!-- Stock Bajo -->
        <div class="bg-white rounded-3xl shadow-md p-6 border border-gray-100">

            <p class="text-gray-500 text-sm mb-3">
                Productos con Stock Bajo
            </p>

            <h2 class="text-3xl font-bold text-red-600">

                {{ $stockBajo }}

            </h2>

        </div>

    </div>

    <!-- Alertas de Morosidad -->
    @if($cuentasVencidas->count() > 0)
        <div class="mt-10">
            <h2 class="text-2xl font-bold text-[#2b2b2b] mb-4">Alertas de Morosidad</h2>
            <div class="bg-white rounded-3xl shadow-md border border-red-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-red-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-red-700">Factura</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-red-700">Cliente</th>
                            <th class="px-6 py-4 text-right text-sm font-bold text-red-700">Saldo</th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-red-700">Días de Atraso</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-red-700">Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cuentasVencidas as $cuenta)
                            <tr class="border-b border-red-100">
                                <td class="px-6 py-3 font-semibold">{{ $cuenta->numero_factura }}</td>
                                <td class="px-6 py-3">{{ $cuenta->cliente_nombre }}</td>
                                <td class="px-6 py-3 text-right font-mono text-red-600">₡{{ number_format($cuenta->saldo_pendiente, 2) }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">
                                        {{ $cuenta->dias_atraso }} días
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm">{{ \Carbon\Carbon::parse($cuenta->fecha_vencimiento)->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Cuentas por Pagar Próximas -->
    @if($cuentasPorVencer->count() > 0)
        <div class="mt-10">
            <h2 class="text-2xl font-bold text-[#2b2b2b] mb-4">Obligaciones Próximas a Vencer</h2>
            <div class="bg-white rounded-3xl shadow-md border border-yellow-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-yellow-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-yellow-700">Compra</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-yellow-700">Proveedor</th>
                            <th class="px-6 py-4 text-right text-sm font-bold text-yellow-700">Saldo</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-yellow-700">Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cuentasPorVencer as $cuenta)
                            <tr class="border-b border-yellow-100">
                                <td class="px-6 py-3 font-semibold">{{ $cuenta->numero_compra }}</td>
                                <td class="px-6 py-3">{{ $cuenta->proveedor_nombre }}</td>
                                <td class="px-6 py-3 text-right font-mono text-yellow-700">₡{{ number_format($cuenta->saldo_pendiente, 2) }}</td>
                                <td class="px-6 py-3 text-sm">{{ \Carbon\Carbon::parse($cuenta->fecha_vencimiento)->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

</x-app-layout>