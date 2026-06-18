<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Resultados de Consulta</h1>
                <p class="mt-2 text-gray-700 text-lg">
                    Módulo: <span class="font-semibold text-[#b71c1c]">{{ ucfirst($modulo) }}</span>
                    @if($termino)
                        | Término: <span class="font-semibold">{{ $termino }}</span>
                    @endif
                    @if($fechaDesde)
                        | Desde: {{ $fechaDesde }}
                    @endif
                    @if($fechaHasta)
                        | Hasta: {{ $fechaHasta }}
                    @endif
                </p>
            </div>

            <a href="{{ route('consultas.index') }}"
               class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                Nueva Consulta
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            @if($resultados->count() > 0)
                <table class="w-full">
                    <thead class="bg-[#2b2b2b] text-white">
                        <tr>
                            @foreach(array_keys((array)$resultados->first()) as $col)
                                <th class="px-6 py-5 text-left">{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultados as $fila)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                @foreach((array)$fila as $valor)
                                    <td class="px-6 py-4">
                                        @if(is_numeric($valor) && $valor > 100)
                                            ₡{{ number_format($valor, 2) }}
                                        @else
                                            {{ $valor }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-10 text-center text-gray-600">
                    No se encontraron resultados con los criterios especificados.
                </div>
            @endif
        </div>

        <p class="mt-4 text-sm text-gray-700">
            {{ $resultados->count() }} resultado(s) encontrado(s).
        </p>
    </div>
</x-app-layout>
