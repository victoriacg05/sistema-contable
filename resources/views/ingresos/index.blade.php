<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Ingresos
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Registro y control de ingresos financieros
                </p>
            </div>

            <a href="{{ route('ingresos.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Ingreso
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
                        <th class="px-6 py-5 text-left">Referencia</th>
                        <th class="px-6 py-5 text-left">Origen</th>
                        <th class="px-6 py-5 text-left">Método</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Monto</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($ingresos as $ingreso)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $ingreso->referencia_ingreso }}
                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                {{ $ingreso->origen }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ $ingreso->metodoPago->nombre ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ \Carbon\Carbon::parse($ingreso->fecha)->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡{{ number_format($ingreso->monto, 2) }}
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <a href="{{ route('ingresos.edit', [$ingreso->referencia_ingreso, $ingreso->fecha, $ingreso->usuario_id]) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition">
                                    Editar
                                </a>

                                <form action="{{ route('ingresos.destroy', [$ingreso->referencia_ingreso, $ingreso->fecha, $ingreso->usuario_id]) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Está segura de eliminar este ingreso?');">
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
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 text-lg">
                                No hay ingresos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>