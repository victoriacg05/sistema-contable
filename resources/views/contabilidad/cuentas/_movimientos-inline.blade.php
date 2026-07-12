{{--
    Panel de movimientos en línea de una cuenta contable.
    Parámetros:
      $movs   → Collection de movimientos (detalle de asientos) del subárbol.
      $modulo → array ['ruta','modulo'] o null, para la opción "Ver más".
--}}
@php
    $tDebe = $movs->sum('debe');
    $tHaber = $movs->sum('haber');
    $saldo = $tDebe - $tHaber;
    $acumulado = 0;
@endphp

<div class="bg-gray-50 border border-gray-200 rounded-xl p-4">

    @if($movs->isEmpty())
        <p class="text-sm text-gray-500 italic">Sin movimientos registrados para esta cuenta.</p>
    @else
        {{-- Totales --}}
        <div class="flex flex-wrap gap-3 mb-4">
            <div class="px-4 py-2 rounded-lg bg-white border border-gray-200">
                <p class="text-xs text-gray-500 font-semibold uppercase">Total Debe</p>
                <p class="text-base font-bold text-gray-800">&#8353; {{ number_format($tDebe, 2) }}</p>
            </div>
            <div class="px-4 py-2 rounded-lg bg-white border border-gray-200">
                <p class="text-xs text-gray-500 font-semibold uppercase">Total Haber</p>
                <p class="text-base font-bold text-gray-800">&#8353; {{ number_format($tHaber, 2) }}</p>
            </div>
            <div class="px-4 py-2 rounded-lg bg-white border border-gray-200">
                <p class="text-xs text-gray-500 font-semibold uppercase">Saldo</p>
                <p class="text-base font-bold {{ $saldo >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    &#8353; {{ number_format($saldo, 2) }}
                </p>
            </div>
        </div>

        {{-- Tabla de transacciones --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="py-2 pr-3 font-semibold">Fecha</th>
                        <th class="py-2 pr-3 font-semibold">Documento</th>
                        <th class="py-2 pr-3 font-semibold">Cuenta</th>
                        <th class="py-2 pr-3 font-semibold">Descripción</th>
                        <th class="py-2 pr-3 font-semibold">Estado</th>
                        <th class="py-2 pr-3 font-semibold text-right">Debe</th>
                        <th class="py-2 pr-3 font-semibold text-right">Haber</th>
                        <th class="py-2 pl-3 font-semibold text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movs as $mov)
                        @php $acumulado += ($mov->debe - $mov->haber); @endphp
                        <tr class="border-b border-gray-100 hover:bg-white transition">
                            <td class="py-2 pr-3 whitespace-nowrap text-gray-700">
                                {{ \Carbon\Carbon::parse($mov->fecha_asiento)->format('d/m/Y') }}
                            </td>
                            <td class="py-2 pr-3 whitespace-nowrap">
                                <a href="{{ route('contabilidad.asientos.show', [$mov->numero_asiento, $mov->fecha_asiento]) }}"
                                   class="text-[#b71c1c] hover:text-red-800 font-semibold font-mono text-xs">
                                    {{ $mov->numero_asiento }}
                                </a>
                            </td>
                            <td class="py-2 pr-3 whitespace-nowrap font-mono text-xs text-gray-600">
                                {{ $mov->codigo_cuenta }}
                            </td>
                            <td class="py-2 pr-3 text-gray-700">
                                {{ $mov->descripcion ?: $mov->asiento_descripcion ?: '—' }}
                            </td>
                            <td class="py-2 pr-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                                    {{ $mov->estado_nombre }}
                                </span>
                            </td>
                            <td class="py-2 pr-3 text-right whitespace-nowrap text-gray-800">
                                {{ $mov->debe > 0 ? number_format($mov->debe, 2) : '—' }}
                            </td>
                            <td class="py-2 pr-3 text-right whitespace-nowrap text-gray-800">
                                {{ $mov->haber > 0 ? number_format($mov->haber, 2) : '—' }}
                            </td>
                            <td class="py-2 pl-3 text-right whitespace-nowrap font-semibold {{ $acumulado >= 0 ? 'text-gray-800' : 'text-red-700' }}">
                                {{ number_format($acumulado, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold text-gray-800 border-t-2 border-gray-300">
                        <td class="py-2 pr-3" colspan="5">Totales</td>
                        <td class="py-2 pr-3 text-right">&#8353; {{ number_format($tDebe, 2) }}</td>
                        <td class="py-2 pr-3 text-right">&#8353; {{ number_format($tHaber, 2) }}</td>
                        <td class="py-2 pl-3 text-right {{ $saldo >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            &#8353; {{ number_format($saldo, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    @if($modulo)
        <div class="mt-4">
            <a href="{{ route($modulo['ruta']) }}"
               class="inline-flex items-center gap-1 text-sm font-semibold text-[#b71c1c] hover:text-red-800">
                Ver más en {{ $modulo['modulo'] }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    @endif

</div>
