<x-app-layout>
    <div class="max-w-4xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#c62828] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Inventario
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Nueva Categoría
            </h1>

            <p class="mt-2 text-gray-500 text-lg">
                Registro de categoría de producto
            </p>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información de la Categoría
                </h2>
            </div>

            <form action="{{ route('categorias-productos.store') }}" method="POST" class="p-8">
                @csrf

                <div class="space-y-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>

                        <input type="text"
                               name="nombre"
                               value="{{ old('nombre') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Descripción
                        </label>

                        <textarea name="descripcion"
                                  rows="4"
                                  class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                                  required>{{ old('descripcion') }}</textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('productos.create') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Volver a Producto
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#c62828] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>