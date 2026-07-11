<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Compras
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Nueva Compra
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Registra una compra y actualiza el inventario
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo registrar la compra:</p>

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

            <form action="{{ route('compras.store') }}" method="POST" class="p-8" id="form-compra">
                @csrf

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
                                <option value="{{ $proveedor->id }}" {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                    {{ $proveedor->nombre }} - {{ $proveedor->empresa }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Producto
                        </label>

                        <select name="producto_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione un producto</option>

                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" {{ old('producto_id') == $producto->id ? 'selected' : '' }}>
                                    {{ $producto->nombre }} | Stock actual: {{ $producto->stock }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Cantidad
                        </label>

                        <input type="number"
                               name="cantidad"
                               id="cantidad"
                               value="{{ old('cantidad', 1) }}"
                               min="1"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Precio unitario
                        </label>

                        <input type="number"
                               step="0.01"
                               id="precio_unitario"
                               name="precio_unitario"
                               value="{{ old('precio_unitario') }}"
                               min="0"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Tipo de compra
                        </label>

                        <select name="tipo_compra"
                                id="tipo_compra"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="contado" {{ old('tipo_compra', 'contado') === 'contado' ? 'selected' : '' }}>Contado</option>
                            <option value="credito" {{ old('tipo_compra') === 'credito' ? 'selected' : '' }}>Crédito</option>
                        </select>
                    </div>

                </div>

                {{-- Sección de plazos (solo para compras a crédito) --}}
                <div id="seccion-credito" class="mt-8 hidden">
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-[#1f2937]">Plazos de pago</h3>
                                <p class="text-sm text-gray-600">
                                    Total de la compra:
                                    <span id="total-compra" class="font-bold text-[#b71c1c]">₡0.00</span>
                                    <span class="text-gray-500">(incluye 13% de impuesto)</span>
                                </p>
                            </div>

                            <div class="flex items-end gap-3">
                                <div>
                                    <label class="block mb-1 text-sm font-bold text-gray-700">
                                        Número de cuotas
                                    </label>
                                    <input type="number"
                                           id="num_cuotas"
                                           min="1"
                                           value="1"
                                           class="w-32 px-4 py-3 rounded-xl border border-gray-300 bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                                </div>

                                <button type="button"
                                        id="generar-cuotas"
                                        class="px-5 py-3 rounded-xl bg-[#2b2b2b] text-white font-bold hover:bg-black transition">
                                    Generar cuotas
                                </button>
                            </div>
                        </div>

                        <div id="lista-cuotas" class="space-y-3"></div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-4">
                            <span class="text-sm text-gray-600">
                                Suma de cuotas:
                                <span id="suma-cuotas" class="font-bold">₡0.00</span>
                            </span>
                            <span id="mensaje-cuotas" class="text-sm font-bold"></span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('compras.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const IMPUESTO = 0.13;

            const tipoCompra = document.getElementById('tipo_compra');
            const seccionCredito = document.getElementById('seccion-credito');
            const cantidad = document.getElementById('cantidad');
            const precio = document.getElementById('precio_unitario');
            const numCuotas = document.getElementById('num_cuotas');
            const generarBtn = document.getElementById('generar-cuotas');
            const listaCuotas = document.getElementById('lista-cuotas');
            const totalCompraEl = document.getElementById('total-compra');
            const sumaCuotasEl = document.getElementById('suma-cuotas');
            const mensajeEl = document.getElementById('mensaje-cuotas');
            const form = document.getElementById('form-compra');

            function calcularTotal() {
                const cant = parseFloat(cantidad.value) || 0;
                const pre = parseFloat(precio.value) || 0;
                const subtotal = cant * pre;
                return Math.round((subtotal + subtotal * IMPUESTO) * 100) / 100;
            }

            function formatear(valor) {
                return '₡' + (valor || 0).toLocaleString('es-CR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function esCredito() {
                return tipoCompra.value === 'credito';
            }

            function fechaSugerida(mesesAdelante) {
                const f = new Date();
                f.setMonth(f.getMonth() + mesesAdelante);
                return f.toISOString().slice(0, 10);
            }

            function generarCuotas() {
                const n = Math.max(1, parseInt(numCuotas.value) || 1);
                const total = calcularTotal();
                const montoBase = Math.floor((total / n) * 100) / 100;
                listaCuotas.innerHTML = '';

                for (let i = 0; i < n; i++) {
                    // La última cuota absorbe la diferencia por redondeo.
                    const monto = (i === n - 1)
                        ? Math.round((total - montoBase * (n - 1)) * 100) / 100
                        : montoBase;

                    const fila = document.createElement('div');
                    fila.className = 'grid grid-cols-1 md:grid-cols-3 gap-3 items-center bg-white border border-gray-200 rounded-xl p-3';
                    fila.innerHTML = `
                        <div class="font-bold text-gray-700">Cuota ${i + 1}</div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Fecha de vencimiento</label>
                            <input type="date" name="cuotas[${i}][fecha_vencimiento]" value="${fechaSugerida(i + 1)}"
                                   class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] outline-none transition cuota-fecha" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="cuotas[${i}][monto]" value="${monto.toFixed(2)}"
                                   class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] outline-none transition cuota-monto" required>
                        </div>
                    `;
                    listaCuotas.appendChild(fila);
                }

                actualizarSuma();
            }

            function actualizarSuma() {
                const montos = Array.from(document.querySelectorAll('.cuota-monto'));
                const suma = Math.round(
                    montos.reduce((acc, el) => acc + (parseFloat(el.value) || 0), 0) * 100
                ) / 100;
                const total = calcularTotal();

                totalCompraEl.textContent = formatear(total);
                sumaCuotasEl.textContent = formatear(suma);

                if (montos.length === 0) {
                    mensajeEl.textContent = '';
                    return true;
                }

                if (Math.abs(suma - total) > 0.01) {
                    mensajeEl.textContent = 'La suma de las cuotas debe ser igual al total de la compra.';
                    mensajeEl.className = 'text-sm font-bold text-red-700';
                    return false;
                }

                mensajeEl.textContent = 'La suma de las cuotas coincide con el total. ✔';
                mensajeEl.className = 'text-sm font-bold text-green-700';
                return true;
            }

            function toggleSeccion() {
                if (esCredito()) {
                    seccionCredito.classList.remove('hidden');
                    if (listaCuotas.children.length === 0) {
                        generarCuotas();
                    } else {
                        actualizarSuma();
                    }
                } else {
                    seccionCredito.classList.add('hidden');
                    // Elimina las cuotas para no enviar campos ocultos obligatorios.
                    listaCuotas.innerHTML = '';
                    mensajeEl.textContent = '';
                }
            }

            tipoCompra.addEventListener('change', toggleSeccion);
            generarBtn.addEventListener('click', generarCuotas);
            cantidad.addEventListener('input', function () { if (esCredito()) actualizarSuma(); });
            precio.addEventListener('input', function () { if (esCredito()) actualizarSuma(); });
            listaCuotas.addEventListener('input', function (e) {
                if (e.target.classList.contains('cuota-monto')) actualizarSuma();
            });

            form.addEventListener('submit', function (e) {
                if (esCredito()) {
                    if (listaCuotas.children.length === 0) {
                        e.preventDefault();
                        alert('Debe registrar al menos un plazo de pago para una compra a crédito.');
                        return;
                    }
                    if (!actualizarSuma()) {
                        e.preventDefault();
                        alert('La suma de las cuotas debe ser igual al total de la compra.');
                    }
                }
            });

            // Estado inicial (respeta valores previos tras un error de validación).
            toggleSeccion();
        })();
    </script>
</x-app-layout>