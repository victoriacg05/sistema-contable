<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-start justify-between mb-8 gap-4 flex-wrap">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    <span class="font-mono text-[#b71c1c]">{{ $cuenta->codigo_cuenta }}</span>
                    {{ $cuenta->nombre }}
                </h1>
                <p class="mt-2 text-gray-600 text-lg">{{ $cuenta->ruta }}</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                        {{ $cuenta->tipo_nombre }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $cuenta->estado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $cuenta->estado ? 'Activa' : 'Inactiva' }}
                    </span>
                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
                        {{ $cuenta->es_hoja ? 'Cuenta de detalle' : 'Cuenta agrupadora (incluye subcuentas)' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if($verMas)
                    <a href="{{ route($verMas['ruta']) }}"
                       class="bg-[#b71c1c] hover:bg-red-800 text-white px-6 py-3 rounded-2xl font-bold shadow-md transition">
                        Ver más en {{ $verMas['modulo'] }}
                    </a>
                @endif
                <a href="{{ route('contabilidad.cuentas.index') }}"
                   class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Volver al catálogo
                </a>
            </div>
        </div>

        {{-- Resumen de saldos --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-sm text-gray-600 font-semibold">Total Debe</p>
                <p class="mt-2 text-3xl font-extrabold text-green-700 font-mono">&#8353;{{ number_format($totalDebe, 2) }}</p>
            </div>
            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-sm text-gray-600 font-semibold">Total Haber</p>
                <p class="mt-2 text-3xl font-extrabold text-red-700 font-mono">&#8353;{{ number_format($totalHaber, 2) }}</p>
            </div>
            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-sm text-gray-600 font-semibold">Saldo</p>
                <p class="mt-2 text-3xl font-extrabold font-mono {{ $saldo >= 0 ? 'text-[#1f2937]' : 'text-red-700' }}">
                    &#8353;{{ number_format($saldo, 2) }}
                </p>
            </div>
        </div>

        {{-- Detalle de movimientos --}}
        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-xl font-bold text-[#1f2937]">Movimientos ({{ $movimientos->count() }})</h2>
                <p class="text-sm text-gray-600 mt-1">Transacciones y documentos relacionados con esta cuenta</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#2b2b2b] text-white">
                        <tr>
                            <th class="px-6 py-5 text-left">Fecha</th>
                            <th class="px-6 py-5 text-left">Documento</th>
                            <th class="px-6 py-5 text-left">Cuenta</th>
                            <th class="px-6 py-5 text-left">Descripción</th>
                            <th class="px-6 py-5 text-left">Estado</th>
                            <th class="px-6 py-5 text-right">Debe</th>
                            <th class="px-6 py-5 text-right">Haber</th>
                            <th class="px-6 py-5 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $mov)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($mov->fecha_asiento)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('contabilidad.asientos.show', [$mov->numero_asiento, $mov->fecha_asiento]) }}"
                                       class="font-mono text-sm text-[#b71c1c] font-semibold hover:underline">
                                        {{ $mov->numero_asiento }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs text-gray-500">{{ $mov->codigo_cuenta }}</span>
                                    <span class="block text-sm text-gray-800">{{ $mov->cuenta_nombre }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $mov->descripcion ?: ($mov->asiento_descripcion ?: '—') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                        {{ $mov->estado_nombre }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-green-700">
                                    {{ $mov->debe > 0 ? '₡' . number_format($mov->debe, 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-red-700">
                                    {{ $mov->haber > 0 ? '₡' . number_format($mov->haber, 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-semibold {{ $mov->saldo_acumulado >= 0 ? 'text-[#1f2937]' : 'text-red-700' }}">
                                    ₡{{ number_format($mov->saldo_acumulado, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    Esta cuenta no tiene movimientos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($movimientos->isNotEmpty())
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td class="px-6 py-4" colspan="5">TOTALES</td>
                                <td class="px-6 py-4 text-right font-mono text-green-700">₡{{ number_format($totalDebe, 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-red-700">₡{{ number_format($totalHaber, 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono {{ $saldo >= 0 ? 'text-[#1f2937]' : 'text-red-700' }}">₡{{ number_format($saldo, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
