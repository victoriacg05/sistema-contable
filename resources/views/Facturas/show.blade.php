<x-app-layout>
    <div class="max-w-4xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Factura</h1>
                <p class="mt-2 text-gray-700 text-lg">Vista previa de la factura de venta</p>
            </div>

            <a href="{{ route('compras.clientes') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Volver
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">

            <div class="bg-[#b71c1c] text-white px-10 py-8 flex items-center justify-between">
                <div>
                    <p class="text-2xl font-extrabold">Distribuidora Ipacaraí</p>
                    <p class="text-white/80">{{ $tipoComprobante ?? 'Factura' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-white/80">N° de factura</p>
                    <p class="text-xl font-bold">{{ $factura->numero_factura }}</p>
                </div>
            </div>

            <div class="px-10 py-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase mb-1">Cliente</p>
                    <p class="text-lg font-semibold text-gray-800">{{ optional($factura->cliente)->nombre ?? 'Sin cliente' }}</p>
                    @if(optional($factura->cliente)->identificacion)
                        <p class="text-gray-600">Identificación: {{ $factura->cliente->identificacion }}</p>
                    @endif
                    @if(optional($factura->cliente)->email)
                        <p class="text-gray-600">{{ $factura->cliente->email }}</p>
                    @endif
                    @if(optional($factura->cliente)->telefono)
                        <p class="text-gray-600">Tel: {{ $factura->cliente->telefono }}</p>
                    @endif
                </div>

                <div class="sm:text-right">
                    <p class="text-sm font-bold text-gray-500 uppercase mb-1">Detalles</p>
                    <p class="text-gray-700">Fecha: {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}</p>
                    <p class="text-gray-700">Método de pago: {{ optional($factura->metodoPago)->nombre ?? '—' }}</p>
                    <p class="text-gray-700">Registrada por: {{ $usuario ?? '—' }}</p>
                    <p class="mt-2">
                        @php $estadoNombre = strtolower(optional($factura->estado)->nombre ?? ''); @endphp
                        @if($estadoNombre === 'pagado')
                            <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">Pagado</span>
                        @elseif($estadoNombre === 'anulado')
                            <span class="px-4 py-2 rounded-full bg-gray-200 text-gray-600 text-sm font-bold">Anulado</span>
                        @else
                            <span class="px-4 py-2 rounded-full bg-amber-100 text-amber-800 text-sm font-bold">Pendiente</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="px-10 pb-4">
                <table class="w-full">
                    <thead class="bg-[#2b2b2b] text-white">
                        <tr>
                            <th class="px-6 py-4 text-left rounded-tl-xl">Producto</th>
                            <th class="px-6 py-4 text-center">Cantidad</th>
                            <th class="px-6 py-4 text-right">Precio unitario</th>
                            <th class="px-6 py-4 text-right rounded-tr-xl">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($factura->detalles as $detalle)
                            <tr class="border-b border-gray-200">
                                <td class="px-6 py-4 text-gray-700 font-semibold">
                                    {{ optional($detalle->producto)->nombre ?? 'Producto' }}
                                </td>
                                <td class="px-6 py-4 text-center text-gray-600">{{ $detalle->cantidad }}</td>
                                <td class="px-6 py-4 text-right text-gray-700">₡{{ number_format($detalle->precio_unitario, 2) }}</td>
                                <td class="px-6 py-4 text-right text-gray-700 font-bold">₡{{ number_format($detalle->subtotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-500">Sin productos en la factura.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-10 pb-10 flex justify-end">
                <div class="w-full sm:w-80 space-y-2">
                    <div class="flex justify-between text-gray-700">
                        <span>Subtotal</span>
                        <span class="font-mono">₡{{ number_format($factura->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Impuesto (13%)</span>
                        <span class="font-mono">₡{{ number_format($factura->impuesto, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Descuento</span>
                        <span class="font-mono">₡{{ number_format($factura->descuento, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-extrabold text-[#b71c1c] border-t border-gray-200 pt-2">
                        <span>Total</span>
                        <span class="font-mono">₡{{ number_format($factura->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
