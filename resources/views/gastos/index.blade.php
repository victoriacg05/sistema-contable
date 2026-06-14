<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Gastos
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Registro y control de gastos empresariales
                </p>
            </div>

            <a href="{{ route('gastos.create') }}"
               class="bg-[#c62828] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Gasto
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
                        <th class="px-6 py-5 text-left">Comprobante</th>
                        <th class="px-6 py-5 text-left">Categoría</th>
                        <th class="px-6 py-5 text-left">Tipo</th>
                        <th class="px-6 py-5 text-left">Método</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Monto</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($gastos as $gasto)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold">
                                {{ $gasto->numero_comprobante }}
                            </td>

                            <td class="px-6 py-5">
                                {{ $gasto->categoria->nombre ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-5">
                                {{ $gasto->tipoGasto->nombre ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-5">
                                {{ $gasto->metodoPago->nombre ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-5">
                                {{ \Carbon\Carbon::parse($gasto->fecha)->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-5 font-bold">
                                ₡{{ number_format($gasto->monto, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <a href="{{ route('gastos.edit', [$gasto->numero_comprobante, $gasto->categoria_gasto_id, $gasto->fecha]) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold">
                                    Editar
                                </a>

                                <form action="{{ route('gastos.destroy', [$gasto->numero_comprobante, $gasto->categoria_gasto_id, $gasto->fecha]) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Eliminar este gasto?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="bg-[#c62828] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold ml-2">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                No hay gastos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>