<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <h1 class="text-4xl font-extrabold text-[#1f2937]">Detalle del Asiento</h1>
            <a href="{{ route('contabilidad.asientos.index') }}"
               class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                Volver
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-10 mb-8">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-700">N° Asiento</p>
                    <p class="font-bold text-lg">{{ $asiento->numero_asiento }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-700">Fecha</p>
                    <p class="font-bold text-lg">{{ \Carbon\Carbon::parse($asiento->fecha)->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-700">Registrado por</p>
                    <p class="font-bold">{{ $asiento->usuario_nombre }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-700">Estado</p>
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                        {{ $asiento->estado_nombre }}
                    </span>
                </div>
                <div class="col-span-2">
                    <p class="text-sm text-gray-700">Descripción</p>
                    <p class="font-medium">{{ $asiento->descripcion ?: 'Sin descripción' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Código</th>
                        <th class="px-6 py-5 text-left">Cuenta</th>
                        <th class="px-6 py-5 text-right">Debe</th>
                        <th class="px-6 py-5 text-right">Haber</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalles as $detalle)
                        <tr class="border-b border-gray-200">
                            <td class="px-6 py-4 font-mono">{{ $detalle->codigo_cuenta }}</td>
                            <td class="px-6 py-4">{{ $detalle->cuenta_nombre }}</td>
                            <td class="px-6 py-4 text-right font-mono">₡{{ number_format($detalle->debe, 2) }}</td>
                            <td class="px-6 py-4 text-right font-mono">₡{{ number_format($detalle->haber, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td class="px-6 py-4" colspan="2">TOTALES</td>
                        <td class="px-6 py-4 text-right font-mono">₡{{ number_format($asiento->total_debe, 2) }}</td>
                        <td class="px-6 py-4 text-right font-mono">₡{{ number_format($asiento->total_haber, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
