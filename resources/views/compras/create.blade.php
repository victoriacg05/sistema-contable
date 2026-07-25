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

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Tipo de compra
                        </label>

                        <select name="tipo_operacion"
                                id="tipo_operacion"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="proveedor" {{ old('tipo_operacion', 'proveedor') === 'proveedor' ? 'selected' : '' }}>
                                Compra a proveedor (abastecimiento — aumenta inventario)
                            </option>
                            <option value="cliente" {{ old('tipo_operacion') === 'cliente' ? 'selected' : '' }}>
                                Compra de cliente externo (venta — descuenta inventario)
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2 grupo-proveedor">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Proveedor
                        </label>

                        <select name="proveedor_id"
                                id="proveedor_id"
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

                    <div class="grupo-cliente">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Cliente
                        </label>

                        <select name="cliente_id"
                                id="cliente_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                            <option value="">Seleccione un cliente</option>

                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Método de pago
                        </label>

                        <select name="metodo_pago_id"
                                id="metodo_pago_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                            <option value="">Seleccione un método de pago</option>

                            @foreach($metodosPago as $metodo)
                                <option value="{{ $metodo->id }}" {{ old('metodo_pago_id') == $metodo->id ? 'selected' : '' }}>
                                    {{ $metodo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grupo-cliente">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Tipo de comprobante
                        </label>

                        <select name="tipo_comprobante_id"
                                id="tipo_comprobante_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                            <option value="">Seleccione un comprobante</option>

                            @foreach($tiposComprobante as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_comprobante_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grupo-cliente">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Descuento
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="descuento"
                               id="descuento"
                               value="{{ old('descuento', 0) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
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
                                        <option value="{{ $producto->id }}"
                                                data-precio-mayorista="{{ $producto->precio }}"
                                                data-precio-venta="{{ $producto->precio_venta_sin_impuesto }}"
                                                data-ganancia="{{ $producto->porcentaje_ganancia }}">
                                            {{ $producto->nombre }} | Stock actual: {{ $producto->stock }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Cantidad</label>
                                <input type="number" min="1" value="1" data-campo="cantidad"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white focus:border-[#b71c1c] outline-none transition" required>
                            </div>
                            <div class="md:col-span-3">
                                <label data-etiqueta-precio class="block text-xs font-semibold text-gray-500 mb-1">Costo mayorista</label>
                                <input type="number" step="0.01" min="0" data-campo="precio"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-100 cursor-not-allowed outline-none transition"
                                       readonly>
                                <p data-detalle-precio class="mt-1 text-xs text-gray-500"></p>
                            </div>
                            <div class="md:col-span-1 flex justify-center">
                                <button type="button"
                                        class="quitar-producto px-3 py-3 rounded-xl bg-red-100 text-red-700 font-bold hover:bg-red-200 transition"
                                        title="Quitar producto">✕</button>
                            </div>
                        </div>
                    </template>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Condición de pago
                        </label>

                        <select name="tipo_compra"
                                id="tipo_compra"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                            <option value="contado" {{ old('tipo_compra', 'contado') === 'contado' ? 'selected' : '' }}>Contado</option>
                            <option value="credito" {{ old('tipo_compra') === 'credito' ? 'selected' : '' }}>Crédito</option>
                        </select>

                        <p class="mt-2 text-sm text-gray-500 grupo-cliente">
                            En crédito se genera automáticamente una cuenta por cobrar; en contado la factura queda pagada.
                        </p>
                    </div>

                    {{-- Cuenta bancaria (compras a proveedor) --}}
                    <div class="md:col-span-2 grupo-proveedor" id="grupo-banco">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Cuenta bancaria
                        </label>

                        <select name="cuenta_bancaria_id"
                                id="cuenta_bancaria_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                            <option value="">Seleccione la cuenta bancaria</option>
                            @foreach($cuentasBancarias as $cuentaBancaria)
                                <option value="{{ $cuentaBancaria->id }}"
                                        data-saldo="{{ $cuentaBancaria->saldo }}"
                                        {{ old('cuenta_bancaria_id') == $cuentaBancaria->id ? 'selected' : '' }}>
                                    {{ $cuentaBancaria->banco_nombre }} — {{ $cuentaBancaria->numero_cuenta }} — Saldo: ₡{{ number_format($cuentaBancaria->saldo, 2) }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-2 text-sm text-gray-500" id="banco-nota-contado">
                            Se descontará el total de la compra del saldo de esta cuenta y se generará el asiento contable automáticamente.
                        </p>
                        <p class="mt-2 text-sm text-gray-500 hidden" id="banco-nota-credito">
                            En crédito el saldo bancario no se ve afectado ahora; el banco se descontará cuando se registre el pago de la cuenta por pagar.
                        </p>
                    </div>

                </div>

                {{-- Sección de plazos (solo para compras a crédito) --}}
                <div id="seccion-credito" class="mt-8 hidden">
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6">
                        <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-[#1f2937]">Plazos de pago</h3>
                                <p class="text-sm text-gray-600">
                                    Total de la operación:
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
            const numCuotas = document.getElementById('num_cuotas');
            const generarBtn = document.getElementById('generar-cuotas');
            const listaCuotas = document.getElementById('lista-cuotas');
            const totalCompraEl = document.getElementById('total-compra');
            const sumaCuotasEl = document.getElementById('suma-cuotas');
            const mensajeEl = document.getElementById('mensaje-cuotas');
            const form = document.getElementById('form-compra');

            // --- Cuenta bancaria (pago de contado a proveedor) ---
            const cuentaBancariaSel = document.getElementById('cuenta_bancaria_id');
            const grupoBanco = document.getElementById('grupo-banco');
            const bancoNotaContado = document.getElementById('banco-nota-contado');
            const bancoNotaCredito = document.getElementById('banco-nota-credito');

            // --- Tipo de operación (proveedor / cliente externo) ---
            const tipoOperacion = document.getElementById('tipo_operacion');
            const gruposProveedor = document.querySelectorAll('.grupo-proveedor');
            const gruposCliente = document.querySelectorAll('.grupo-cliente');
            const proveedorSel = document.getElementById('proveedor_id');
            const clienteSel = document.getElementById('cliente_id');
            const metodoPagoSel = document.getElementById('metodo_pago_id');
            const tipoComprobanteSel = document.getElementById('tipo_comprobante_id');
            const descuentoInput = document.getElementById('descuento');

            // --- Productos ---
            const plantilla = document.getElementById('plantilla-producto');
            const listaProductos = document.getElementById('lista-productos');
            const agregarBtn = document.getElementById('agregar-producto');
            const subtotalEl = document.getElementById('subtotal-productos');
            const impuestoEl = document.getElementById('impuesto-productos');
            const totalProductosEl = document.getElementById('total-productos');

            function reindexarProductos() {
                const lineas = listaProductos.querySelectorAll('.linea-producto');
                lineas.forEach(function (linea, i) {
                    linea.querySelector('[data-campo="producto"]').setAttribute('name', `productos[${i}][producto_id]`);
                    linea.querySelector('[data-campo="cantidad"]').setAttribute('name', `productos[${i}][cantidad]`);
                    linea.querySelector('[data-campo="precio"]').setAttribute('name', `productos[${i}][precio_unitario]`);
                });
            }

            function agregarProducto() {
                const nodo = plantilla.content.firstElementChild.cloneNode(true);
                listaProductos.appendChild(nodo);
                reindexarProductos();
                aplicarPrecioLinea(nodo);
                recalcular();
            }

            function calcularSubtotal() {
                let subtotal = 0;
                listaProductos.querySelectorAll('.linea-producto').forEach(function (linea) {
                    const cant = parseFloat(linea.querySelector('[data-campo="cantidad"]').value) || 0;
                    const pre = parseFloat(linea.querySelector('[data-campo="precio"]').value) || 0;
                    subtotal += cant * pre;
                });
                return Math.round(subtotal * 100) / 100;
            }

            function calcularTotal() {
                const subtotal = calcularSubtotal();
                let total = Math.round((subtotal + subtotal * IMPUESTO) * 100) / 100;

                // En venta a cliente el total considera el descuento aplicado.
                if (esCliente()) {
                    const desc = parseFloat(descuentoInput.value) || 0;
                    total = Math.max(0, Math.round((total - desc) * 100) / 100);
                }

                return total;
            }

            function recalcular() {
                const subtotal = calcularSubtotal();
                const impuesto = Math.round(subtotal * IMPUESTO * 100) / 100;
                let total = subtotal + impuesto;

                // En modo cliente externo (venta) se aplica el descuento.
                if (esCliente()) {
                    const desc = parseFloat(descuentoInput.value) || 0;
                    total = Math.max(0, total - desc);
                }

                subtotalEl.textContent = formatear(subtotal);
                impuestoEl.textContent = formatear(impuesto);
                totalProductosEl.textContent = formatear(total);
                if (esCredito()) actualizarSuma();
            }

            function esCliente() {
                return tipoOperacion.value === 'cliente';
            }

            function aplicarPrecioLinea(linea) {
                const select = linea.querySelector('[data-campo="producto"]');
                const precioInput = linea.querySelector('[data-campo="precio"]');
                const etiqueta = linea.querySelector('[data-etiqueta-precio]');
                const detalle = linea.querySelector('[data-detalle-precio]');
                const opcion = select.options[select.selectedIndex];
                const mayorista = opcion ? parseFloat(opcion.dataset.precioMayorista) || 0 : 0;
                const precioVenta = opcion ? parseFloat(opcion.dataset.precioVenta) || 0 : 0;
                const ganancia = opcion ? parseFloat(opcion.dataset.ganancia) || 0 : 0;

                precioInput.value = opcion && opcion.value
                    ? (esCliente() ? precioVenta : mayorista).toFixed(2)
                    : '';
                etiqueta.textContent = esCliente()
                    ? 'Venta antes de impuesto'
                    : 'Costo mayorista';
                detalle.textContent = opcion && opcion.value && esCliente()
                    ? `${formatear(mayorista)} + ${ganancia.toFixed(2)}% de ganancia`
                    : 'El costo se toma del producto guardado.';
            }

            function aplicarModoPrecios() {
                listaProductos.querySelectorAll('.linea-producto').forEach(aplicarPrecioLinea);
            }

            function toggleOperacion() {
                const cliente = esCliente();

                gruposProveedor.forEach(function (el) { el.classList.toggle('hidden', cliente); });
                gruposCliente.forEach(function (el) { el.classList.toggle('hidden', !cliente); });

                // Habilitar/deshabilitar campos según el modo (los deshabilitados
                // no se envían ni disparan validación del navegador).
                proveedorSel.disabled = cliente;
                proveedorSel.required = !cliente;

                clienteSel.disabled = !cliente;
                clienteSel.required = cliente;
                // El método de pago aplica a ambos tipos de operación.
                metodoPagoSel.disabled = false;
                metodoPagoSel.required = true;
                tipoComprobanteSel.disabled = !cliente;
                tipoComprobanteSel.required = cliente;
                descuentoInput.disabled = !cliente;

                // Los plazos aplican a cualquier operación a crédito.
                toggleSeccion();

                toggleBanco();

                aplicarModoPrecios();
                recalcular();
            }

            function toggleBanco() {
                // La cuenta bancaria se muestra para compras a proveedor
                // (contado y crédito). Solo es obligatoria y afecta el saldo
                // en compras de contado; en crédito es informativa.
                const esProveedor = !esCliente();
                const contado = !esCredito();

                if (grupoBanco) {
                    grupoBanco.classList.toggle('hidden', !esProveedor);
                }
                if (cuentaBancariaSel) {
                    cuentaBancariaSel.disabled = !esProveedor;
                    cuentaBancariaSel.required = esProveedor && contado;
                    if (!esProveedor) {
                        cuentaBancariaSel.value = '';
                    }
                }
                if (bancoNotaContado) {
                    bancoNotaContado.classList.toggle('hidden', !contado);
                }
                if (bancoNotaCredito) {
                    bancoNotaCredito.classList.toggle('hidden', contado);
                }
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
                // Los plazos de pago aplican a cualquier operación a crédito
                // (compra a proveedor o venta a cliente).
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

                toggleBanco();
            }

            // Eventos de productos
            agregarBtn.addEventListener('click', agregarProducto);
            listaProductos.addEventListener('click', function (e) {
                if (e.target.classList.contains('quitar-producto')) {
                    const lineas = listaProductos.querySelectorAll('.linea-producto');
                    if (lineas.length <= 1) {
                        alert('La compra debe tener al menos un producto.');
                        return;
                    }
                    e.target.closest('.linea-producto').remove();
                    reindexarProductos();
                    recalcular();
                }
            });
            listaProductos.addEventListener('input', recalcular);
            listaProductos.addEventListener('change', function (e) {
                if (e.target.dataset.campo === 'producto') {
                    aplicarPrecioLinea(e.target.closest('.linea-producto'));
                    recalcular();
                }
            });

            // Eventos de crédito
            tipoCompra.addEventListener('change', toggleSeccion);
            generarBtn.addEventListener('click', generarCuotas);
            listaCuotas.addEventListener('input', function (e) {
                if (e.target.classList.contains('cuota-monto')) actualizarSuma();
            });

            // Eventos de tipo de operación
            tipoOperacion.addEventListener('change', toggleOperacion);
            descuentoInput.addEventListener('input', recalcular);

            form.addEventListener('submit', function (e) {
                if (listaProductos.querySelectorAll('.linea-producto').length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos un producto a la compra.');
                    return;
                }
                if (esCredito()) {
                    if (listaCuotas.children.length === 0) {
                        e.preventDefault();
                        alert('Debe registrar al menos un plazo de pago para una operación a crédito.');
                        return;
                    }
                    if (!actualizarSuma()) {
                        e.preventDefault();
                        alert('La suma de las cuotas debe ser igual al total.');
                        return;
                    }
                }

                // Compra de contado a proveedor: exige cuenta bancaria con fondos suficientes.
                if (!esCliente() && !esCredito()) {
                    if (!cuentaBancariaSel.value) {
                        e.preventDefault();
                        alert('Seleccione la cuenta bancaria desde la cual se pagará la compra de contado.');
                        return;
                    }

                    const opcion = cuentaBancariaSel.options[cuentaBancariaSel.selectedIndex];
                    const saldo = parseFloat(opcion.dataset.saldo || '0');
                    const total = calcularTotal();

                    if (total > saldo + 0.0001) {
                        e.preventDefault();
                        alert('Fondos insuficientes en la cuenta seleccionada.\n'
                            + 'Saldo disponible: ' + formatear(saldo) + '\n'
                            + 'Total de la compra: ' + formatear(total));
                        return;
                    }
                }
            });

            // Estado inicial: una línea de producto y el modo de operación.
            agregarProducto();
            toggleOperacion();
        })();
    </script>
</x-app-layout>