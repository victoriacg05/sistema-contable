<x-app-layout>
    @php
        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    @endphp

    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Presupuestos
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Control presupuestario por período y categoría
                </p>
            </div>

            <a href="{{ route('presupuesto.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Presupuesto
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if($periodos->isEmpty())
            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 px-6 py-16 text-center text-gray-600 text-lg">
                Aún no hay presupuestos registrados.<br>
                Crea el primero con el botón <span class="font-bold">Nuevo Presupuesto</span>.
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($periodos as $periodo)
                    <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
                        <div class="bg-[#2b2b2b] px-8 py-5 flex items-center justify-between">
                            <h2 class="text-white text-xl font-bold">
                                {{ $meses[$periodo->mes] ?? $periodo->mes }} {{ $periodo->anio }}
                            </h2>
                            <span class="text-gray-300 text-sm">
                                {{ $periodo->lineas }} {{ $periodo->lineas == 1 ? 'categoría' : 'categorías' }}
                            </span>
                        </div>

                        <div class="p-8">
                            <div class="grid grid-cols-3 gap-4 mb-5">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Asignado</p>
                                    <p class="text-lg font-bold text-gray-800">₡{{ number_format($periodo->total_presupuestado, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Ejecutado</p>
                                    <p class="text-lg font-bold text-gray-800">₡{{ number_format($periodo->total_ejecutado, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Disponible</p>
                                    <p class="text-lg font-bold {{ $periodo->disponible < 0 ? 'text-red-700' : 'text-green-700' }}">
                                        ₡{{ number_format($periodo->disponible, 2) }}
                                    </p>
                                </div>
                            </div>

                            @php $pct = min(100, $periodo->porcentaje); @endphp
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-semibold text-gray-600">Ejecución</span>
                                <span class="font-bold {{ $periodo->porcentaje > 100 ? 'text-red-700' : 'text-gray-700' }}">
                                    {{ $periodo->porcentaje }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full {{ $periodo->porcentaje > 100 ? 'bg-red-600' : ($periodo->porcentaje >= 80 ? 'bg-amber-500' : 'bg-green-600') }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>

                            <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-200">
                                <a href="{{ route('presupuesto.show', [$periodo->anio, $periodo->mes]) }}"
                                   class="bg-gray-800 hover:bg-black text-white px-5 py-2 rounded-xl font-bold transition">
                                    Ver detalle
                                </a>

                                <a href="{{ route('presupuesto.create', ['desde_anio' => $periodo->anio, 'desde_mes' => $periodo->mes]) }}"
                                   class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-5 py-2 rounded-xl font-bold transition">
                                    Copiar
                                </a>

                                <form action="{{ route('presupuesto.destroyPeriodo', [$periodo->anio, $periodo->mes]) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Eliminar todo el presupuesto de este período?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-app-layout>
