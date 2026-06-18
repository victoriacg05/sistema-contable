<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Inventario
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Editar Producto
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Actualiza la información del producto
                </p>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Producto
                </h2>
            </div>

            <form action="{{ route('productos.update', $producto) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Código de barras
                        </label>
                        <input type="text" name="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>
                        <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Categoría
                        </label>
                        <select name="categoria_producto_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ old('categoria_producto_id', $producto->categoria_producto_id) == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Precio
                        </label>
                        <input type="number" step="0.01" name="precio" value="{{ old('precio', $producto->precio) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Stock
                        </label>
                        <input type="number" name="stock" value="{{ old('stock', $producto->stock) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Stock mínimo
                        </label>
                        <input type="number" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Descripción
                        </label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                  required>{{ old('descripcion', $producto->descripcion) }}</textarea>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-3 mt-2">
                            <input type="checkbox" name="estado" value="1"
                                   class="rounded border-gray-300 text-[#b71c1c] focus:ring-[#b71c1c]"
                                   {{ $producto->estado ? 'checked' : '' }}>

                            <span class="font-semibold text-gray-700">
                                Producto activo
                            </span>
                        </label>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">

                    <a href="{{ route('productos.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Actualizar Producto
                    </button>

                </div>
            </form>
        </div>
    </div>
</x-app-layout>