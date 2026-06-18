<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Bitácora del Sistema</h1>
                <p class="mt-2 text-gray-700 text-lg">Registro de acciones realizadas por los usuarios</p>
            </div>

            <a href="{{ route('bitacora.intentos') }}"
               class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-3 rounded-2xl font-bold shadow-md transition">
                Ver Intentos de Acceso
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Usuario</th>
                        <th class="px-6 py-5 text-left">Acción</th>
                        <th class="px-6 py-5 text-left">Tabla</th>
                        <th class="px-6 py-5 text-left">Descripción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($registros as $reg)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($reg->fecha)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-6 py-4 font-semibold">{{ $reg->usuario_nombre }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    @if($reg->accion == 'crear') bg-green-100 text-green-700
                                    @elseif($reg->accion == 'editar') bg-amber-100 text-amber-800
                                    @elseif($reg->accion == 'eliminar') bg-red-100 text-red-700
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ ucfirst($reg->accion) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">{{ $reg->tabla_afectada }}</td>
                            <td class="px-6 py-4 text-sm">{{ Str::limit($reg->descripcion, 60) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-600">No hay registros en la bitácora.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $registros->links() }}
        </div>
    </div>
</x-app-layout>
