<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Cuentas por Pagar
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Control de obligaciones pendientes y pagos a proveedores
                </p>
            </div>
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
                        <th class="px-6 py-5 text-left">Compra</th>
                        <th class="px-6 py-5 text-left">Proveedor</th>
                        <th class="px-6 py-5 text-left">Vencimiento</th>
                        <th class="px-6 py-5 text-left">Monto</th>
                        <th class="px-6 py-5 text-left">Saldo</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($cuentas as $cuenta)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $cuenta->numero_compra }}
                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                {{ $cuenta->proveedor->nombre ?? 'Sin proveedor' }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ \Carbon\Carbon::parse($cuenta->fecha_vencimiento)->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡{{ number_format($cuenta->monto_original, 2) }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡{{ number_format($cuenta->saldo_pendiente, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if(optional($cuenta->estado)->nombre === 'pagado')
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Pagada
                                    </span>
                                @elseif(\Carbon\Carbon::parse($cuenta->fecha_vencimiento)->isPast())
                                    <span class="px-4 py-2 rounded-full bg-red-100 text-red-700 text-sm font-bold">
                                        Vencida
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-amber-100 text-amber-800 text-sm font-bold">
                                        Pendiente
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if(optional($cuenta->estado)->nombre !== 'pagado')
                                    <a href="{{ route('cuentas-pagar.pago.create', [$cuenta->numero_compra, $cuenta->proveedor_id]) }}"
                                       class="inline-block bg-[#b71c1c] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition">
                                        Registrar Pago
                                    </a>
                                @else
                                    <span class="text-gray-600 font-semibold">
                                        Sin acciones
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-700 text-lg">
                                No hay cuentas por pagar registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>