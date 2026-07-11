<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Compras
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Editar Compra
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Actualiza la información de la compra {{ $compra->numero_compra }}
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo actualizar la compra:</p>

                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información de la Compra
                </h2>
            </div>

            <form action="{{ route('compras.update', $compra) }}" method="POST" class="p-8" id="form-compra">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Proveedor
                        </label>

                        <select name="proveedor_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione un proveedor</option>

                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}"
                                    {{ old('proveedor_id', $compra->proveedor_id) == $proveedor->id ? 'selected' : '' }}>
                                    {{ $proveedor->nombre }} - {{ $proveedor->empresa }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-700">
                                Productos
                            </label>

                            <button type="button"
                                    id="agregar-producto"
                                    class="px-4 py-2 rounded-xl bg-[#2b2b2b] text-white text-sm font-bold hover:bg-black transition">
                                + Agregar producto
                            </button>
                        </div>

                        <div id="lista-productos" class="space-y-3"></div>

                        <p class="mt-2 text-sm text-gray-600">
                            Subtotal: <span id="subtotal-productos" class="font-bold">₡0.00</span>
                            &middot; Impuesto (13%): <span id="impuesto-productos" class="font-bold">₡0.00</span>
                            &middot; Total: <span id="total-productos" class="font-bold text-[#b71c1c]">₡0.00</span>
                        </p>
                    </div>

                    {{-- Plantilla de línea de producto (se clona por JS) --}}
                    <template id="plantilla-producto">
                        <div class="linea-producto grid grid-cols-1 md:grid-cols-12 gap-3 items-end bg-gray-50 border border-gray-200 rounded-xl p-3">
                            <div class="md:col-span-6">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Producto</label>
                                <select data-campo="producto"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white focus:border-[#b71c1c] outline-none transition" required>
                                    <option value="">Seleccione un producto</option>
                                    @foreach($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ $producto->nombre }} | Stock actual: {{ $producto->stock }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Cantidad</label>
                                <input type="number" min="1" value="1" data-campo="cantidad"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white focus:border-[#b71c1c] outline-none transition" required>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Precio unitario</label>
                                <input type="number" step="0.01" min="0" data-campo="precio"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white focus:border-[#b71c1c] outline-none transition" required>
                            </div>
                            <div class="md:col-span-1 flex justify-center">
                                <button type="button"
                                        class="quitar-producto px-3 py-3 rounded-xl bg-red-100 text-red-700 font-bold hover:bg-red-200 transition"
                                        title="Quitar producto">✕</button>
                            </div>
                        </div>
                    </template>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('compras.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Actualizar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $lineasIniciales = old('productos', $detalles->map(function ($d) {
            return [
                'producto_id' => $d->producto_id,
                'cantidad' => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
            ];
        })->values());
    @endphp

    <script>
        (function () {
            const IMPUESTO = 0.13;
            const LINEAS_INICIALES = @json($lineasIniciales);

            const plantilla = document.getElementById('plantilla-producto');
            const listaProductos = document.getElementById('lista-productos');
            const agregarBtn = document.getElementById('agregar-producto');
            const subtotalEl = document.getElementById('subtotal-productos');
            const impuestoEl = document.getElementById('impuesto-productos');
            const totalEl = document.getElementById('total-productos');
            const form = document.getElementById('form-compra');

            function formatear(valor) {
                return '₡' + (valor || 0).toLocaleString('es-CR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function reindexar() {
                listaProductos.querySelectorAll('.linea-producto').forEach(function (linea, i) {
                    linea.querySelector('[data-campo="producto"]').setAttribute('name', `productos[${i}][producto_id]`);
                    linea.querySelector('[data-campo="cantidad"]').setAttribute('name', `productos[${i}][cantidad]`);
                    linea.querySelector('[data-campo="precio"]').setAttribute('name', `productos[${i}][precio_unitario]`);
                });
            }

            function agregarProducto(datos) {
                const nodo = plantilla.content.firstElementChild.cloneNode(true);
                if (datos) {
                    nodo.querySelector('[data-campo="producto"]').value = datos.producto_id ?? '';
                    nodo.querySelector('[data-campo="cantidad"]').value = datos.cantidad ?? 1;
                    nodo.querySelector('[data-campo="precio"]').value = datos.precio_unitario ?? '';
                }
                listaProductos.appendChild(nodo);
                reindexar();
                recalcular();
            }

            function recalcular() {
                let subtotal = 0;
                listaProductos.querySelectorAll('.linea-producto').forEach(function (linea) {
                    const cant = parseFloat(linea.querySelector('[data-campo="cantidad"]').value) || 0;
                    const pre = parseFloat(linea.querySelector('[data-campo="precio"]').value) || 0;
                    subtotal += cant * pre;
                });
                subtotal = Math.round(subtotal * 100) / 100;
                const impuesto = Math.round(subtotal * IMPUESTO * 100) / 100;
                subtotalEl.textContent = formatear(subtotal);
                impuestoEl.textContent = formatear(impuesto);
                totalEl.textContent = formatear(subtotal + impuesto);
            }

            agregarBtn.addEventListener('click', function () { agregarProducto(); });
            listaProductos.addEventListener('click', function (e) {
                if (e.target.classList.contains('quitar-producto')) {
                    if (listaProductos.querySelectorAll('.linea-producto').length <= 1) {
                        alert('La compra debe tener al menos un producto.');
                        return;
                    }
                    e.target.closest('.linea-producto').remove();
                    reindexar();
                    recalcular();
                }
            });
            listaProductos.addEventListener('input', recalcular);

            form.addEventListener('submit', function (e) {
                if (listaProductos.querySelectorAll('.linea-producto').length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos un producto a la compra.');
                }
            });

            // Precargar líneas existentes (o al menos una vacía).
            if (LINEAS_INICIALES && LINEAS_INICIALES.length > 0) {
                LINEAS_INICIALES.forEach(function (linea) { agregarProducto(linea); });
            } else {
                agregarProducto();
            }
        })();
    </script>
</x-app-layout>