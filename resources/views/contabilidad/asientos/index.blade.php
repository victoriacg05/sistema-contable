<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Asientos Contables</h1>
                <p class="mt-2 text-gray-500 text-lg">Registro de asientos contables del sistema</p>
            </div>

            <a href="{{ route('contabilidad.asientos.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Asiento
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
                        <th class="px-6 py-5 text-left">N° Asiento</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Descripción</th>
                        <th class="px-6 py-5 text-right">Debe</th>
                        <th class="px-6 py-5 text-right">Haber</th>
                        <th class="px-6 py-5 text-left">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($asientos as $asiento)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold">{{ $asiento->numero_asiento }}</td>
                            <td class="px-6 py-5">{{ \Carbon\Carbon::parse($asiento->fecha)->format('d/m/Y') }}</td>
                            <td class="px-6 py-5">{{ Str::limit($asiento->descripcion, 40) }}</td>
                            <td class="px-6 py-5 text-right font-mono">₡{{ number_format($asiento->total_debe, 2) }}</td>
                            <td class="px-6 py-5 text-right font-mono">₡{{ number_format($asiento->total_haber, 2) }}</td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                    {{ $asiento->estado_nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <a href="{{ route('contabilidad.asientos.show', [$asiento->numero_asiento, $asiento->fecha]) }}"
                                   class="text-blue-600 hover:text-blue-800 font-semibold">
                                    Ver Detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-600">No hay asientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
