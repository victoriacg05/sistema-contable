<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#c62828] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Inventario
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Nuevo Producto
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Registra los datos del producto para el inventario
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo guardar el producto:</p>

                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Producto
                </h2>
            </div>

            <form action="{{ route('productos.store') }}" method="POST" class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Código de barras
                        </label>

                        <input type="text"
                               name="codigo_barras"
                               value="{{ old('codigo_barras') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               placeholder="Ej: PROD-001"
                               required>

                        @error('codigo_barras')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>

                        <input type="text"
                               name="nombre"
                               value="{{ old('nombre') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               placeholder="Nombre del producto"
                               required>

                        @error('nombre')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-700">
                                Categoría
                            </label>

                            <a href="{{ route('categorias-productos.create') }}"
                               class="text-sm font-bold text-[#c62828] hover:underline">
                                + Nueva categoría
                            </a>
                        </div>

                        <select name="categoria_producto_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                                required>
                            <option value="">Seleccione una categoría</option>

                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}"
                                    {{ old('categoria_producto_id') == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('categoria_producto_id')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Precio
                        </label>

                        <input type="number"
                               step="0.01"
                               name="precio"
                               value="{{ old('precio') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               placeholder="0.00"
                               required>

                        @error('precio')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Stock
                        </label>

                        <input type="number"
                               name="stock"
                               value="{{ old('stock', 0) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               min="0"
                               required>

                        @error('stock')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Stock mínimo
                        </label>

                        <input type="number"
                               name="stock_minimo"
                               value="{{ old('stock_minimo', 0) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               min="0"
                               required>

                        @error('stock_minimo')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Descripción
                        </label>

                        <textarea name="descripcion"
                                  rows="3"
                                  class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                                  placeholder="Descripción del producto"
                                  required>{{ old('descripcion') }}</textarea>

                        @error('descripcion')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('productos.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#c62828] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>