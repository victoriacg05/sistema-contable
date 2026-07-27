<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Compras
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Compras a proveedores para abastecimiento de inventario
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
               class="px-6 py-3 rounded-2xl font-bold transition bg-[#b71c1c] text-white shadow-md">
                Compras a Proveedores
            </a>
            <a href="{{ route('compras.clientes') }}"
               class="px-6 py-3 rounded-2xl font-bold transition bg-gray-100 text-gray-700 hover:bg-gray-200">
                Compras de Clientes
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-300 bg-red-50 px-6 py-4 text-red-800">
                <p class="font-bold">No fue posible eliminar la compra:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Compra</th>
                        <th class="px-6 py-5 text-left">Proveedor</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Total</th>
                        <th class="px-6 py-5 text-center">Condición</th>
                        <th class="px-6 py-5 text-center">Método de pago</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($compras as $compra)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $compra->numero_compra }}
                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                {{ $compra->proveedor->nombre ?? 'Sin proveedor' }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡{{ number_format($compra->total, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if(($compra->tipo_compra ?? 'contado') === 'credito')
                                    <span class="px-4 py-2 rounded-full bg-blue-100 text-blue-700 text-sm font-bold">
                                        Crédito
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 text-sm font-bold">
                                        Contado
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center text-gray-700">
                                {{ optional($compra->metodoPago)->nombre ?? '—' }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if(strtolower(optional($compra->estado)->nombre ?? '') === 'pagado')
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
                                @if(strtolower(optional($compra->estado)->nombre ?? '') !== 'pagado')
                                    <a href="{{ route('cuentas-pagar.pago.create', [$compra->numero_compra, $compra->proveedor_id]) }}"
                                       class="inline-block bg-green-100 hover:bg-green-200 text-green-700 px-5 py-2 rounded-xl font-bold transition">
                                        Registrar pago
                                    </a>
                                @endif

                                <a href="{{ route('compras.edit', $compra) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition ml-2">
                                    Editar
                                </a>

                                <form action="{{ route('compras.destroy', $compra) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Está segura de eliminar esta compra? Se revertirá el stock agregado.');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition ml-2">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <tr class="border-b border-gray-200 bg-gray-50">
                            <td colspan="8" class="px-6 py-4">
                                <details>
                                    <summary class="cursor-pointer font-bold text-[#b71c1c]">
                                        Ver productos ({{ $compra->detalles->count() }})
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
                                                @foreach($compra->detalles as $detalle)
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

                        @if($compra->plazos->isNotEmpty())
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <td colspan="8" class="px-6 py-4">
                                    <details>
                                        <summary class="cursor-pointer font-bold text-[#b71c1c]">
                                            Ver plazos de pago ({{ $compra->plazos->count() }} cuotas)
                                        </summary>

                                        <div class="mt-3 overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="text-gray-600 text-left">
                                                        <th class="px-4 py-2">Cuota</th>
                                                        <th class="px-4 py-2">Vencimiento</th>
                                                        <th class="px-4 py-2">Monto</th>
                                                        <th class="px-4 py-2">Saldo pendiente</th>
                                                        <th class="px-4 py-2 text-center">Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($compra->plazos as $plazo)
                                                        <tr class="border-t border-gray-200">
                                                            <td class="px-4 py-2 font-semibold text-gray-700">{{ $plazo->numero_cuota }}</td>
                                                            <td class="px-4 py-2 text-gray-600">
                                                                {{ \Carbon\Carbon::parse($plazo->fecha_vencimiento)->format('d/m/Y') }}
                                                            </td>
                                                            <td class="px-4 py-2 text-gray-700 font-bold">₡{{ number_format($plazo->monto, 2) }}</td>
                                                            <td class="px-4 py-2 text-gray-700 font-bold">₡{{ number_format($plazo->saldo_pendiente, 2) }}</td>
                                                            <td class="px-4 py-2 text-center">
                                                                @if($plazo->saldo_pendiente <= 0)
                                                                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">Pagada</span>
                                                                @elseif(\Carbon\Carbon::parse($plazo->fecha_vencimiento)->isPast())
                                                                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">Vencida</span>
                                                                @else
                                                                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">Pendiente</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-700 text-lg">
                                No hay compras a proveedores registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $compras->links() }}
        </div>

    </div>
</x-app-layout>
