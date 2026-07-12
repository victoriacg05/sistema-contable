<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Facturación
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Nueva Factura
                </h1>

                <p class="mt-2 text-gray-700 text-lg">
                    Registra una nueva venta del sistema
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo crear la factura:</p>

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
                    Información de la Factura
                </h2>
            </div>

            <form action="{{ route('facturas.store') }}" method="POST" class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Cliente
                        </label>

                        <select name="cliente_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione un cliente</option>

                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('cliente_id')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Método de pago
                        </label>

                        <select name="metodo_pago_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione método de pago</option>

                            @foreach($metodosPago as $metodo)
                                <option value="{{ $metodo->id }}" {{ old('metodo_pago_id') == $metodo->id ? 'selected' : '' }}>
                                    {{ $metodo->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('metodo_pago_id')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Tipo de comprobante
                        </label>

                        <select name="tipo_comprobante_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione tipo de comprobante</option>

                            @foreach($tiposComprobante as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_comprobante_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('tipo_comprobante_id')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Condición de pago
                        </label>

                        <select name="tipo_compra"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="contado" {{ old('tipo_compra', 'contado') === 'contado' ? 'selected' : '' }}>Contado (queda pagada)</option>
                            <option value="credito" {{ old('tipo_compra') === 'credito' ? 'selected' : '' }}>Crédito (genera cuenta por cobrar)</option>
                        </select>

                        @error('tipo_compra')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="bloque-banco">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Cuenta bancaria (recibe el pago)
                        </label>

                        <select name="cuenta_bancaria_id"
                                id="cuenta_bancaria_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">
                            <option value="">Seleccione la cuenta bancaria</option>

                            @foreach($cuentasBancarias as $cuentaBanco)
                                <option value="{{ $cuentaBanco->id }}" {{ old('cuenta_bancaria_id') == $cuentaBanco->id ? 'selected' : '' }}>
                                    {{ $cuentaBanco->banco_nombre }} — {{ $cuentaBanco->numero_cuenta }} — Saldo: ₡{{ number_format($cuentaBanco->saldo, 2) }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-1 text-xs text-gray-500">
                            Solo aplica para ventas de contado; el monto se sumará a esta cuenta.
                        </p>

                        @error('cuenta_bancaria_id')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
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
                                    {{ $producto->nombre }} - ₡{{ number_format($producto->precio, 2) }} | Stock: {{ $producto->stock }}
                                </option>
                            @endforeach
                        </select>

                        @error('producto_id')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Cantidad
                        </label>

                        <input type="number"
                               name="cantidad"
                               value="{{ old('cantidad', 1) }}"
                               min="1"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>

                        @error('cantidad')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Descuento
                        </label>

                        <input type="number"
                               step="0.01"
                               name="descuento"
                               value="{{ old('descuento', 0) }}"
                               min="0"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">

                        @error('descuento')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('facturas.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Crear Factura
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const tipoCompra = document.querySelector('select[name="tipo_compra"]');
            const bloqueBanco = document.getElementById('bloque-banco');
            const selectBanco = document.getElementById('cuenta_bancaria_id');

            function actualizarBanco() {
                const esContado = tipoCompra.value === 'contado';
                bloqueBanco.style.display = esContado ? '' : 'none';
                if (esContado) {
                    selectBanco.setAttribute('required', 'required');
                } else {
                    selectBanco.removeAttribute('required');
                    selectBanco.value = '';
                }
            }

            tipoCompra.addEventListener('change', actualizarBanco);
            actualizarBanco();
        })();
    </script>
</x-app-layout>