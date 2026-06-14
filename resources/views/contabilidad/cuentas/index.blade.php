<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Catálogo de Cuentas</h1>
                <p class="mt-2 text-gray-500 text-lg">Gestión del catálogo de cuentas contables</p>
            </div>

            <a href="{{ route('contabilidad.cuentas.create') }}"
               class="bg-[#c62828] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nueva Cuenta
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
                        <th class="px-6 py-5 text-left">Código</th>
                        <th class="px-6 py-5 text-left">Nombre</th>
                        <th class="px-6 py-5 text-left">Tipo</th>
                        <th class="px-6 py-5 text-left">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($cuentas as $cuenta)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold">{{ $cuenta->codigo_cuenta }}</td>
                            <td class="px-6 py-5">{{ $cuenta->nombre }}</td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                    {{ $cuenta->tipo_nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $cuenta->estado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $cuenta->estado ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <a href="{{ route('contabilidad.cuentas.edit', $cuenta->codigo_cuenta) }}"
                                   class="text-blue-600 hover:text-blue-800 font-semibold">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400">No hay cuentas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
