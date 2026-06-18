<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Intentos de Acceso</h1>
                <p class="mt-2 text-gray-500 text-lg">Registro de intentos de inicio de sesión</p>
            </div>

            <a href="{{ route('bitacora.index') }}"
               class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                Volver a Bitácora
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Correo</th>
                        <th class="px-6 py-5 text-left">IP</th>
                        <th class="px-6 py-5 text-left">Resultado</th>
                        <th class="px-6 py-5 text-left">Mensaje</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($intentos as $intento)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($intento->fecha)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-6 py-4">{{ $intento->email }}</td>
                            <td class="px-6 py-4 font-mono text-sm">{{ $intento->ip_address }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $intento->exitoso ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $intento->exitoso ? 'Exitoso' : 'Fallido' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $intento->mensaje }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-600">No hay intentos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $intentos->links() }}
        </div>
    </div>
</x-app-layout>
