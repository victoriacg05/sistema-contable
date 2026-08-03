<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Proveedores
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Nuevo Proveedor
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Registra los datos generales del proveedor
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-300 bg-red-100 px-6 py-4 font-semibold text-red-700">
                <p class="mb-2 font-bold">No se pudo guardar el proveedor:</p>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Proveedor
                </h2>
            </div>

            <form action="{{ route('proveedores.store') }}" method="POST" class="p-8" data-submit-on-click>
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Identificación
                        </label>
                        <input type="text" name="identificacion" value="{{ old('identificacion') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Ej: 3-101-123456" required>
                        @error('identificacion')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}"
                               pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$"
                               title="Solo se permiten letras y espacios"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Nombre del proveedor" required>
                        @error('nombre')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Empresa
                        </label>
                        <input type="text" name="empresa" value="{{ old('empresa') }}"
                               pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$"
                               title="Solo se permiten letras, espacios y caracteres básicos"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Nombre de la empresa" required>
                        @error('empresa')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Teléfono
                        </label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}"
                               pattern="^[245678]\d{3}-?\d{4}$"
                               title="El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9"
                               maxlength="9"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Ej: 2222-2222 o 8888-8888" required>
                        <p class="mt-1 text-xs text-gray-700">No puede iniciar con 0, 1, 3 o 9</p>
                        @error('telefono')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Correo
                        </label>
                        <input type="email" name="correo" value="{{ old('correo') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="proveedor@correo.com" required>
                        @error('correo')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">
                                    Productos nuevos del proveedor
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Las categorías y productos se crearán automáticamente; el stock inicial será cero.
                                </p>
                            </div>

                            <button type="button"
                                    id="agregar-producto-proveedor"
                                    class="shrink-0 rounded-xl bg-gray-800 px-5 py-3 font-bold text-white transition hover:bg-black">
                                + Agregar producto
                            </button>
                        </div>

                        @php
                            $productosNuevos = old('productos_nuevos', [[
                                'categoria_nombre' => '',
                                'categoria_descripcion' => '',
                                'nombre' => '',
                                'descripcion' => '',
                                'stock_minimo' => 0,
                                'precio' => '',
                                'porcentaje_ganancia' => 30,
                            ]]);
                        @endphp

                        <div id="productos-nuevos-proveedor" class="space-y-4">
                            @foreach($productosNuevos as $indice => $productoNuevo)
                                <article data-producto-proveedor
                                         class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h4 class="font-bold text-gray-800">
                                            Producto <span data-numero-producto>{{ $indice + 1 }}</span>
                                        </h4>
                                        <button type="button"
                                                data-eliminar-producto
                                                class="text-sm font-bold text-red-700 hover:underline">
                                            Eliminar
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Nueva categoría</label>
                                            <input type="text"
                                                   name="productos_nuevos[{{ $indice }}][categoria_nombre]"
                                                   value="{{ $productoNuevo['categoria_nombre'] ?? '' }}"
                                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                   placeholder="Nombre de la categoría"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Nombre</label>
                                            <input type="text"
                                                   name="productos_nuevos[{{ $indice }}][nombre]"
                                                   value="{{ $productoNuevo['nombre'] ?? '' }}"
                                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Precio mayorista</label>
                                            <input type="number"
                                                   name="productos_nuevos[{{ $indice }}][precio]"
                                                   value="{{ $productoNuevo['precio'] ?? '' }}"
                                                   min="0"
                                                   max="99999999.99"
                                                   step="0.01"
                                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Porcentaje de ganancia</label>
                                            <input type="number"
                                                   name="productos_nuevos[{{ $indice }}][porcentaje_ganancia]"
                                                   value="{{ $productoNuevo['porcentaje_ganancia'] ?? 30 }}"
                                                   min="0.01"
                                                   max="999.99"
                                                   step="0.01"
                                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                   required>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Descripción de la categoría</label>
                                            <textarea name="productos_nuevos[{{ $indice }}][categoria_descripcion]"
                                                      rows="2"
                                                      class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                      required>{{ $productoNuevo['categoria_descripcion'] ?? '' }}</textarea>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Stock mínimo</label>
                                            <input type="number"
                                                   name="productos_nuevos[{{ $indice }}][stock_minimo]"
                                                   value="{{ $productoNuevo['stock_minimo'] ?? 0 }}"
                                                   min="0"
                                                   max="2147483647"
                                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                   required>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-bold text-gray-700">Descripción</label>
                                            <textarea name="productos_nuevos[{{ $indice }}][descripcion]"
                                                      rows="2"
                                                      class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                                                      required>{{ $productoNuevo['descripcion'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('proveedores.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <template id="plantilla-producto-proveedor">
        <article data-producto-proveedor
                 class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h4 class="font-bold text-gray-800">
                    Producto <span data-numero-producto></span>
                </h4>
                <button type="button"
                        data-eliminar-producto
                        class="text-sm font-bold text-red-700 hover:underline">
                    Eliminar
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Nueva categoría</label>
                    <input type="text"
                           name="productos_nuevos[__INDEX__][categoria_nombre]"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                           placeholder="Nombre de la categoría"
                           required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Nombre</label>
                    <input type="text"
                           name="productos_nuevos[__INDEX__][nombre]"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                           required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Precio mayorista</label>
                    <input type="number"
                           name="productos_nuevos[__INDEX__][precio]"
                           min="0"
                           max="99999999.99"
                           step="0.01"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                           required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Porcentaje de ganancia</label>
                    <input type="number"
                           name="productos_nuevos[__INDEX__][porcentaje_ganancia]"
                           value="30"
                           min="0.01"
                           max="999.99"
                           step="0.01"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                           required>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-bold text-gray-700">Descripción de la categoría</label>
                    <textarea name="productos_nuevos[__INDEX__][categoria_descripcion]"
                              rows="2"
                              class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                              required></textarea>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">Stock mínimo</label>
                    <input type="number"
                           name="productos_nuevos[__INDEX__][stock_minimo]"
                           value="0"
                           min="0"
                           max="2147483647"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                           required>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-bold text-gray-700">Descripción</label>
                    <textarea name="productos_nuevos[__INDEX__][descripcion]"
                              rows="2"
                              class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3"
                              required></textarea>
                </div>
            </div>
        </article>
    </template>

    <script>
        (function () {
            const contenedor = document.getElementById('productos-nuevos-proveedor');
            const plantilla = document.getElementById('plantilla-producto-proveedor');
            const agregar = document.getElementById('agregar-producto-proveedor');

            function reindexar() {
                const productos = contenedor.querySelectorAll('[data-producto-proveedor]');

                productos.forEach(function (producto, indice) {
                    producto.querySelector('[data-numero-producto]').textContent = indice + 1;

                    producto.querySelectorAll('[name]').forEach(function (campo) {
                        campo.name = campo.name.replace(
                            /productos_nuevos\[\d+\]/,
                            `productos_nuevos[${indice}]`
                        );
                    });

                    producto.querySelector('[data-eliminar-producto]')
                        .classList.toggle('hidden', productos.length === 1);
                });
            }

            agregar.addEventListener('click', function () {
                const indice = contenedor.querySelectorAll('[data-producto-proveedor]').length;
                const contenido = plantilla.innerHTML.replaceAll('__INDEX__', indice);
                contenedor.insertAdjacentHTML('beforeend', contenido);
                reindexar();
            });

            contenedor.addEventListener('click', function (event) {
                const boton = event.target.closest('[data-eliminar-producto]');

                if (!boton) {
                    return;
                }

                boton.closest('[data-producto-proveedor]').remove();
                reindexar();
            });

            reindexar();
        })();
    </script>
</x-app-layout>