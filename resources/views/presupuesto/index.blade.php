<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Presupuesto
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Comparación entre presupuesto asignado y gasto real
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

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Periodo</th>
                        <th class="px-6 py-5 text-left">Categoría</th>
                        <th class="px-6 py-5 text-left">Presupuesto</th>
                        <th class="px-6 py-5 text-left">Gasto Real</th>
                        <th class="px-6 py-5 text-left">Diferencia</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($presupuestos as $presupuesto)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ str_pad($presupuesto->mes, 2, '0', STR_PAD_LEFT) }}/{{ $presupuesto->anio }}
                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                {{ $presupuesto->categoria->nombre ?? 'Sin categoría' }}
                            </td>

                            <td class="px-6 py-5 font-bold text-gray-700">
                                ₡{{ number_format($presupuesto->monto_presupuestado, 2) }}
                            </td>

                            <td class="px-6 py-5 font-bold text-gray-700">
                                ₡{{ number_format($presupuesto->gasto_real, 2) }}
                            </td>

                            <td class="px-6 py-5 font-bold {{ $presupuesto->diferencia < 0 ? 'text-red-700' : 'text-green-700' }}">
                                ₡{{ number_format($presupuesto->diferencia, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($presupuesto->diferencia < 0)
                                    <span class="px-4 py-2 rounded-full bg-red-100 text-red-700 text-sm font-bold">
                                        Excedido
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Disponible
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <a href="{{ route('presupuesto.edit', [$presupuesto->anio, $presupuesto->mes, $presupuesto->categoria_gasto_id]) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition">
                                    Editar
                                </a>

                                <form action="{{ route('presupuesto.destroy', [$presupuesto->anio, $presupuesto->mes, $presupuesto->categoria_gasto_id]) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Está segura de eliminar este presupuesto?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition ml-2">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 text-lg">
                                No hay presupuestos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>