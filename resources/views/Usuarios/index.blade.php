<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Usuarios
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Gestión de usuarios del sistema
                </p>
            </div>

            <a href="{{ route('usuarios.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Usuario
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Nombre</th>
                        <th class="px-6 py-5 text-left">Correo</th>
                        <th class="px-6 py-5 text-left">Rol</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                {{ $usuario->name }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ $usuario->email }}
                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                {{ $usuario->role->nombre ?? 'Sin rol' }}
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($usuario->estado)
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Activo
                                    </span>
                                @else
                                    <span class="px-4 py-2 rounded-full bg-red-100 text-red-700 text-sm font-bold">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <a href="{{ route('usuarios.edit', $usuario) }}"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition">
                                    Editar
                                </a>

                                <form action="{{ route('usuarios.destroy', $usuario) }}"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Está segura de eliminar este usuario?');">
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
                            <td colspan="5" class="px-6 py-10 text-center text-gray-700 text-lg">
                                No hay usuarios registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>