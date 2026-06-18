<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Facturas
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Gestión de ventas y facturación
                </p>
            </div>

            <a href="{{ route('facturas.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nueva Factura
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
                        <th class="px-6 py-5 text-left">Método Pago</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Total</th>
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
                                {{ $factura->cliente->nombre ?? 'Sin cliente' }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ $factura->metodoPago->nombre ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡{{ number_format($factura->total, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if(optional($factura->estado)->nombre === 'pagado')
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Pagado
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-amber-100 text-amber-800 text-sm font-bold">
                                        Pendiente
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">

                                @if(optional($factura->estado)->nombre !== 'pagado')
                                    <form action="{{ route('facturas.pagar', $factura) }}"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('¿Deseas marcar esta factura como pagada?');">
                                        @csrf
                                        @method('PUT')

                                        <button type="submit"
                                                class="bg-green-100 hover:bg-green-200 text-green-700 px-5 py-2 rounded-xl font-bold transition">
                                            Pagar
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('facturas.edit', $factura) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition ml-2">
                                    Editar
                                </a>

                                <form action="{{ route('facturas.destroy', $factura) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Está segura de eliminar esta factura? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition ml-2">
                                        Eliminar
                                    </button>
                                </form>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-700 text-lg">
                                No hay facturas registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>