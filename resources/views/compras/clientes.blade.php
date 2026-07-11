<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Compras de Clientes
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Ventas a clientes externos y facturas generadas
                </p>
            </div>

            <a href="{{ route('compras.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nueva Compra
            </a>
        </div>

        {{-- Secciones separadas: proveedores vs clientes --}}
        <div class="flex gap-3 mb-8">
            <a href="{{ route('compras.index') }}"
               class="px-6 py-3 rounded-2xl font-bold transition bg-gray-100 text-gray-700 hover:bg-gray-200">
                Compras a Proveedores
            </a>
            <a href="{{ route('compras.clientes') }}"
               class="px-6 py-3 rounded-2xl font-bold transition bg-[#b71c1c] text-white shadow-md">
                Compras de Clientes
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Factura</th>
                        <th class="px-6 py-5 text-left">Cliente</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Total</th>
                        <th class="px-6 py-5 text-center">Método de pago</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($facturas as $factura)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $factura->numero_factura }}
                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                {{ optional($factura->cliente)->nombre ?? 'Sin cliente' }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡{{ number_format($factura->total, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center text-gray-700">
                                {{ optional($factura->metodoPago)->nombre ?? '—' }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if(optional($factura->estado)->nombre === 'pagado')
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Pagado
                                    </span>
                                @elseif(optional($factura->estado)->nombre === 'Anulado')
                                    <span class="px-4 py-2 rounded-full bg-gray-200 text-gray-600 text-sm font-bold">
                                        Anulado
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-amber-100 text-amber-800 text-sm font-bold">
                                        Pendiente
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <a href="{{ route('facturas.edit', $factura) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition">
                                    Ver factura
                                </a>
                            </td>
                        </tr>

                        <tr class="border-b border-gray-200 bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <details>
                                    <summary class="cursor-pointer font-bold text-[#b71c1c]">
                                        Ver productos ({{ $factura->detalles->count() }})
                                    </summary>

                                    <div class="mt-3 overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="text-gray-600 text-left">
                                                    <th class="px-4 py-2">Producto</th>
                                                    <th class="px-4 py-2">Cantidad</th>
                                                    <th class="px-4 py-2">Precio unitario</th>
                                                    <th class="px-4 py-2">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($factura->detalles as $detalle)
                                                    <tr class="border-t border-gray-200">
                                                        <td class="px-4 py-2 text-gray-700 font-semibold">
                                                            {{ optional($detalle->producto)->nombre ?? 'Producto eliminado' }}
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600">{{ $detalle->cantidad }}</td>
                                                        <td class="px-4 py-2 text-gray-700">₡{{ number_format($detalle->precio_unitario, 2) }}</td>
                                                        <td class="px-4 py-2 text-gray-700 font-bold">₡{{ number_format($detalle->subtotal, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-700 text-lg">
                                No hay compras de clientes registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>
