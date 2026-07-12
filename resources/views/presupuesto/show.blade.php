<x-app-layout>
    @php
        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    @endphp

    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="{{ route('presupuesto.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700">← Volver a presupuestos</a>
                <h1 class="text-4xl font-extrabold text-[#1f2937] mt-2">
                    Presupuesto {{ $meses[(int)$mes] ?? $mes }} {{ $anio }}
                </h1>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('presupuesto.create', ['desde_anio' => $anio, 'desde_mes' => $mes]) }}"
                   class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-6 py-3 rounded-2xl font-bold transition">
                    Copiar
                </a>
                <a href="{{ route('presupuesto.create') }}"
                   class="bg-[#b71c1c] hover:bg-red-700 text-white px-6 py-3 rounded-2xl font-bold shadow-md transition">
                    Nuevo Presupuesto
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        {{-- Resumen del período --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase">Asignado</p>
                <p class="text-2xl font-extrabold text-gray-800">₡{{ number_format($totales['presupuestado'], 2) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase">Ejecutado</p>
                <p class="text-2xl font-extrabold text-gray-800">₡{{ number_format($totales['ejecutado'], 2) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase">Disponible</p>
                <p class="text-2xl font-extrabold {{ $totales['disponible'] < 0 ? 'text-red-700' : 'text-green-700' }}">
                    ₡{{ number_format($totales['disponible'], 2) }}
                </p>
            </div>
            <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase">% Utilizado</p>
                <p class="text-2xl font-extrabold {{ $totales['porcentaje'] > 100 ? 'text-red-700' : 'text-gray-800' }}">
                    {{ $totales['porcentaje'] }}%
                </p>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Categoría</th>
                        <th class="px-6 py-5 text-right">Presupuesto</th>
                        <th class="px-6 py-5 text-right">Ejecutado</th>
                        <th class="px-6 py-5 text-right">Disponible</th>
                        <th class="px-6 py-5 text-left w-64">% Utilizado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($lineas as $linea)
                        @php $pct = min(100, $linea->porcentaje); @endphp
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $linea->categoria->nombre ?? 'Sin categoría' }}
                            </td>
                            <td class="px-6 py-5 text-right font-bold text-gray-700">
                                ₡{{ number_format($linea->monto_presupuestado, 2) }}
                            </td>
                            <td class="px-6 py-5 text-right text-gray-700">
                                ₡{{ number_format($linea->ejecutado, 2) }}
                            </td>
                            <td class="px-6 py-5 text-right font-bold {{ $linea->disponible < 0 ? 'text-red-700' : 'text-green-700' }}">
                                ₡{{ number_format($linea->disponible, 2) }}
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full {{ $linea->porcentaje > 100 ? 'bg-red-600' : ($linea->porcentaje >= 80 ? 'bg-amber-500' : 'bg-green-600') }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold {{ $linea->porcentaje > 100 ? 'text-red-700' : 'text-gray-600' }} w-12 text-right">
                                        {{ $linea->porcentaje }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <a href="{{ route('presupuesto.edit', [$anio, $mes, $linea->categoria_gasto_id]) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition">
                                    Editar
                                </a>

                                <form action="{{ route('presupuesto.destroy', [$anio, $mes, $linea->categoria_gasto_id]) }}"
                                      method="POST" class="inline-block"
                                      onsubmit="return confirm('¿Eliminar esta línea presupuestaria?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition ml-2">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>
