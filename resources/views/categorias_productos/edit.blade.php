<x-app-layout>
    <div class="mx-auto max-w-4xl">
        <div class="mb-8">
            <span class="mb-5 inline-block rounded-2xl bg-[#b71c1c] px-6 py-3 font-bold text-white shadow-md">
                Inventario
            </span>
            <h1 class="text-4xl font-extrabold text-[#1f2937]">Editar Categoría</h1>
            <p class="mt-2 text-lg text-gray-700">Actualización de categoría de producto</p>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-lg">
            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-xl font-bold text-white">Información de la Categoría</h2>
            </div>

            <form action="{{ route('categorias-productos.update', $categoria) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-gray-700">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre) }}"
                               class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-[#b71c1c] focus:bg-white focus:ring-2 focus:ring-[#b71c1c]/20"
                               required>
                        @error('nombre')
                            <p class="mt-2 text-sm font-semibold text-red-800">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-gray-700">Descripción</label>
                        <textarea name="descripcion" rows="4"
                                  class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-[#b71c1c] focus:bg-white focus:ring-2 focus:ring-[#b71c1c]/20"
                                  required>{{ old('descripcion', $categoria->descripcion) }}</textarea>
                        @error('descripcion')
                            <p class="mt-2 text-sm font-semibold text-red-800">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-4 border-t border-gray-200 pt-6">
                    <a href="{{ route('categorias-productos.index') }}"
                       class="rounded-2xl bg-gray-100 px-7 py-3 font-bold text-gray-700 transition hover:bg-gray-200">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="rounded-2xl bg-[#b71c1c] px-8 py-3 font-bold text-white shadow-md transition hover:bg-red-700">
                        Actualizar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
