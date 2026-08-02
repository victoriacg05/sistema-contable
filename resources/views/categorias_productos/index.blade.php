<x-app-layout>
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Categorías de Productos</h1>
                <p class="mt-2 text-lg text-gray-700">Administración de categorías para el inventario</p>
            </div>

            <a href="{{ route('categorias-productos.create') }}"
               class="rounded-2xl bg-[#b71c1c] px-8 py-4 font-bold text-white shadow-md transition hover:bg-red-700">
                Nueva Categoría
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-green-300 bg-green-100 px-6 py-4 font-semibold text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-300 bg-red-50 px-6 py-4 text-red-800">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-lg">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Nombre</th>
                        <th class="px-6 py-5 text-left">Descripción</th>
                        <th class="px-6 py-5 text-center">Productos</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categorias as $categoria)
                        <tr class="border-b border-gray-200 transition hover:bg-gray-50">
                            <td class="px-6 py-5 font-semibold text-gray-800">{{ $categoria->nombre }}</td>
                            <td class="px-6 py-5 text-gray-600">{{ $categoria->descripcion }}</td>
                            <td class="px-6 py-5 text-center">
                                <span class="inline-flex min-w-10 justify-center rounded-full px-3 py-1 text-sm font-bold
                                    {{ $categoria->productos_count > 0
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800' }}">
                                    {{ $categoria->productos_count }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <a href="{{ route('categorias-productos.edit', $categoria) }}"
                                   class="inline-block rounded-xl bg-gray-100 px-5 py-2 font-bold text-gray-700 transition hover:bg-gray-200">
                                    Editar
                                </a>

                                <form action="{{ route('categorias-productos.destroy', $categoria) }}"
                                      method="POST" class="inline-block"
                                      onsubmit="return confirm('¿Desea eliminar esta categoría?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="ml-2 rounded-xl bg-[#b71c1c] px-5 py-2 font-bold text-white transition hover:bg-red-700">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-lg text-gray-700">
                                No hay categorías registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
