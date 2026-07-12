{{--
    Panel de movimientos en línea de una cuenta contable.
    Parámetros:
      $movs      → Collection de movimientos (detalle de asientos) del subárbol.
      $modulo    → array ['ruta','modulo'] o null, para la opción "Ver más".
      $operativo → array con datos operativos del módulo (cuentas por pagar/
                   cobrar, inventario) o null. Estructura:
                   ['tipo' => 'documentos'|'inventario', 'titulo' => string,
                    'tercero_label' => string, 'items' => Collection,
                    'total_saldo' => float].
--}}
@php
    $operativo = $operativo ?? null;
    $tieneOperativo = $operativo && $operativo['items']->isNotEmpty();

    $tDebe = $movs->sum('debe');
    $tHaber = $movs->sum('haber');
    $saldo = $tDebe - $tHaber;
    $acumulado = 0;
@endphp

<div class="bg-gray-50 border border-gray-200 rounded-xl p-4">

    {{-- Datos operativos del módulo relacionado (documentos reales) --}}
    @if($tieneOperativo)
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-bold text-gray-700 uppercase">{{ $operativo['titulo'] }}</h4>
                @if($operativo['tipo'] === 'documentos')
                    <span class="text-sm font-semibold text-gray-700">
                        Saldo pendiente: <span class="text-[#b71c1c]">&#8353; {{ number_format($operativo['total_saldo'], 2) }}</span>
                    </span>
                @else
                    <span class="text-sm font-semibold text-gray-700">
                        Valor de inventario: <span class="text-[#b71c1c]">&#8353; {{ number_format($operativo['total_saldo'], 2) }}</span>
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                @if($operativo['tipo'] === 'documentos')
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200">
                                <th class="py-2 pr-3 font-semibold">Documento</th>
                                <th class="py-2 pr-3 font-semibold">{{ $operativo['tercero_label'] }}</th>
                                <th class="py-2 pr-3 font-semibold">Emisión</th>
                                <th class="py-2 pr-3 font-semibold">Vencimiento</th>
                                <th class="py-2 pr-3 font-semibold">Estado</th>
                                <th class="py-2 pr-3 font-semibold text-right">Monto</th>
                                <th class="py-2 pl-3 font-semibold text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($operativo['items'] as $doc)
                                <tr class="border-b border-gray-100 hover:bg-white transition">
                                    <td class="py-2 pr-3 whitespace-nowrap font-mono text-xs text-gray-700">{{ $doc->documento }}</td>
                                    <td class="py-2 pr-3 text-gray-700">{{ $doc->tercero }}</td>
                                    <td class="py-2 pr-3 whitespace-nowrap text-gray-600">
                                        {{ \Carbon\Carbon::parse($doc->fecha_emision)->format('d/m/Y') }}
                                    </td>
                                    <td class="py-2 pr-3 whitespace-nowrap text-gray-600">
                                        {{ \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d/m/Y') }}
                                    </td>
                                    <td class="py-2 pr-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $doc->saldo_pendiente > 0 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                                            {{ ucfirst($doc->estado_nombre) }}
                                        </span>
                                    </td>
                                    <td class="py-2 pr-3 text-right whitespace-nowrap text-gray-800">{{ number_format($doc->monto_original, 2) }}</td>
                                    <td class="py-2 pl-3 text-right whitespace-nowrap font-semibold {{ $doc->saldo_pendiente > 0 ? 'text-[#b71c1c]' : 'text-gray-500' }}">
                                        {{ number_format($doc->saldo_pendiente, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold text-gray-800 border-t-2 border-gray-300">
                                <td class="py-2 pr-3" colspan="6">Saldo pendiente total</td>
                                <td class="py-2 pl-3 text-right text-[#b71c1c]">&#8353; {{ number_format($operativo['total_saldo'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200">
                                <th class="py-2 pr-3 font-semibold">Producto</th>
                                <th class="py-2 pr-3 font-semibold text-right">Stock</th>
                                <th class="py-2 pr-3 font-semibold text-right">Precio unit.</th>
                                <th class="py-2 pl-3 font-semibold text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($operativo['items'] as $prod)
                                <tr class="border-b border-gray-100 hover:bg-white transition">
                                    <td class="py-2 pr-3 text-gray-700">{{ $prod->nombre }}</td>
                                    <td class="py-2 pr-3 text-right whitespace-nowrap text-gray-800">{{ number_format($prod->stock) }}</td>
                                    <td class="py-2 pr-3 text-right whitespace-nowrap text-gray-800">{{ number_format($prod->precio, 2) }}</td>
                                    <td class="py-2 pl-3 text-right whitespace-nowrap font-semibold text-gray-800">{{ number_format($prod->valor, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold text-gray-800 border-t-2 border-gray-300">
                                <td class="py-2 pr-3" colspan="3">Valor total de inventario</td>
                                <td class="py-2 pl-3 text-right text-[#b71c1c]">&#8353; {{ number_format($operativo['total_saldo'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    @endif

    {{-- Movimientos de asientos contables --}}
    @if($movs->isNotEmpty())
        @if($tieneOperativo)
            <h4 class="text-sm font-bold text-gray-700 uppercase mb-2 mt-4">Asientos contables</h4>
        @endif

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

    {{-- Sin datos --}}
    @if($movs->isEmpty() && ! $tieneOperativo)
        <p class="text-sm text-gray-500 italic">Sin movimientos registrados para esta cuenta.</p>
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
