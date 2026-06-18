<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Reportes
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Resumen financiero y análisis del sistema
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('reportes.index') }}"
              class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6 mb-8">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label class="block mb-2 text-sm font-bold text-gray-700">
                        Año
                    </label>

                    <input type="number"
                           name="anio"
                           value="{{ $anio }}"
                           class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-bold text-gray-700">
                        Mes
                    </label>

                    <select name="mes"
                            class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                                {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                        class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                    Filtrar Reporte
                </button>
            </div>
        </form>
        

        </form>

        <div class="flex justify-end mb-8">
            <a href="{{ route('reportes.pdf', ['anio' => $anio, 'mes' => $mes]) }}"
            class="inline-block bg-[#2b2b2b] hover:bg-black text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Descargar PDF
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Ventas del mes</p>
                <h2 class="text-3xl font-extrabold text-[#1f2937] mt-2">
                    ₡{{ number_format($ventas, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Compras del mes</p>
                <h2 class="text-3xl font-extrabold text-[#1f2937] mt-2">
                    ₡{{ number_format($compras, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Utilidad estimada</p>
                <h2 class="text-3xl font-extrabold {{ $utilidad < 0 ? 'text-red-700' : 'text-green-700' }} mt-2">
                    ₡{{ number_format($utilidad, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Ingresos adicionales</p>
                <h2 class="text-3xl font-extrabold text-[#1f2937] mt-2">
                    ₡{{ number_format($ingresos, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Gastos del mes</p>
                <h2 class="text-3xl font-extrabold text-[#1f2937] mt-2">
                    ₡{{ number_format($gastos, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Resultado operativo</p>
                <h2 class="text-3xl font-extrabold {{ ($ingresos - $gastos) < 0 ? 'text-red-700' : 'text-green-700' }} mt-2">
                    ₡{{ number_format($ingresos - $gastos, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Cuentas por cobrar pendientes</p>
                <h2 class="text-3xl font-extrabold text-amber-800 mt-2">
                    ₡{{ number_format($cuentasCobrar, 2) }}
                </h2>
            </div>

            <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-6">
                <p class="text-gray-700 font-semibold">Cuentas por pagar pendientes</p>
                <h2 class="text-3xl font-extrabold text-red-700 mt-2">
                    ₡{{ number_format($cuentasPagar, 2) }}
                </h2>
            </div>

        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Presupuesto vs Gasto Real
                </h2>
            </div>

            <table class="w-full">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-6 py-5 text-left">Categoría</th>
                        <th class="px-6 py-5 text-left">Presupuesto</th>
                        <th class="px-6 py-5 text-left">Gasto Real</th>
                        <th class="px-6 py-5 text-left">Diferencia</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($presupuestoVsGasto as $item)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $item->categoria }}
                            </td>

                            <td class="px-6 py-5 font-bold text-gray-700">
                                ₡{{ number_format($item->monto_presupuestado, 2) }}
                            </td>

                            <td class="px-6 py-5 font-bold text-gray-700">
                                ₡{{ number_format($item->gasto_real, 2) }}
                            </td>

                            <td class="px-6 py-5 font-bold {{ $item->diferencia < 0 ? 'text-red-700' : 'text-green-700' }}">
                                ₡{{ number_format($item->diferencia, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($item->diferencia < 0)
                                    <span class="px-4 py-2 rounded-full bg-red-100 text-red-700 text-sm font-bold">
                                        Excedido
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Disponible
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-700 text-lg">
                                No hay presupuesto registrado para este periodo
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>