<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Métodos de Pago</h1>
                <p class="mt-2 text-gray-500 text-lg">Administración de métodos de pago del sistema</p>
            </div>

            <a href="{{ route('metodos-pago.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Método
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
                        <th class="px-6 py-5 text-left">Nombre</th>
                        <th class="px-6 py-5 text-left">Descripción</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metodos as $metodo)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold">{{ $metodo->nombre }}</td>
                            <td class="px-6 py-5 text-gray-600">{{ $metodo->descripcion }}</td>
                            <td class="px-6 py-5 text-center space-x-3">
                                <a href="{{ route('metodos-pago.edit', $metodo) }}" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                                <form method="POST" action="{{ route('metodos-pago.destroy', $metodo) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-semibold"
                                            onclick="return confirm('¿Eliminar este método de pago?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-10 text-center text-gray-600">No hay métodos de pago registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
