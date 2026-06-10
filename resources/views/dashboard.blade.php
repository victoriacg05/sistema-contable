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

</div>

</x-app-layout>