<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <h1 class="text-4xl font-extrabold text-[#1f2937] mb-8">Nuevo Asiento Contable</h1>

        @if($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 p-10">
            <form method="POST" action="{{ route('contabilidad.asientos.store') }}" id="formAsiento">
                @csrf

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Fecha</label>
                        <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Estado</label>
                        <select name="estado_id" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                            @foreach($estados as $estado)
                                <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                        <input type="text" name="descripcion" value="{{ old('descripcion') }}"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent"
                               placeholder="Descripción del asiento contable">
                    </div>
                </div>

                <h2 class="text-xl font-bold text-[#1f2937] mb-4">Líneas del Asiento</h2>

                <div id="lineas-container">
                    <div class="grid grid-cols-12 gap-3 mb-2 text-sm font-bold text-gray-600">
                        <div class="col-span-5">Cuenta</div>
                        <div class="col-span-2">Debe</div>
                        <div class="col-span-2">Haber</div>
                        <div class="col-span-2">Descripción</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div class="linea-asiento grid grid-cols-12 gap-3 mb-3">
                        <div class="col-span-5">
                            <select name="lineas[0][codigo_cuenta]" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Seleccione cuenta...</option>
                                @foreach($cuentas as $cuenta)
                                    <option value="{{ $cuenta->codigo_cuenta }}">{{ $cuenta->codigo_cuenta }} - {{ $cuenta->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="lineas[0][debe]" value="0" step="0.01" min="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm campo-debe">
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="lineas[0][haber]" value="0" step="0.01" min="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm campo-haber">
                        </div>
                        <div class="col-span-2">
                            <input type="text" name="lineas[0][descripcion]"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-1"></div>
                    </div>

                    <div class="linea-asiento grid grid-cols-12 gap-3 mb-3">
                        <div class="col-span-5">
                            <select name="lineas[1][codigo_cuenta]" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Seleccione cuenta...</option>
                                @foreach($cuentas as $cuenta)
                                    <option value="{{ $cuenta->codigo_cuenta }}">{{ $cuenta->codigo_cuenta }} - {{ $cuenta->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="lineas[1][debe]" value="0" step="0.01" min="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm campo-debe">
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="lineas[1][haber]" value="0" step="0.01" min="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm campo-haber">
                        </div>
                        <div class="col-span-2">
                            <input type="text" name="lineas[1][descripcion]"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-1"></div>
                    </div>
                </div>

                <button type="button" onclick="agregarLinea()"
                        class="mt-3 text-[#b71c1c] hover:text-red-700 font-semibold text-sm">
                    + Agregar línea
                </button>

                <div class="mt-6 p-4 bg-gray-50 rounded-xl flex justify-between items-center">
                    <div>
                        <span class="font-bold text-gray-700">Total Debe:</span>
                        <span id="totalDebe" class="font-mono ml-2">₡0.00</span>
                    </div>
                    <div>
                        <span class="font-bold text-gray-700">Total Haber:</span>
                        <span id="totalHaber" class="font-mono ml-2">₡0.00</span>
                    </div>
                    <div id="balance-status" class="font-bold text-red-600">Desbalanceado</div>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('contabilidad.asientos.index') }}"
                       class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-3 rounded-xl font-bold shadow-md transition">
                        Registrar Asiento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let lineaIndex = 2;

        function agregarLinea() {
            const container = document.getElementById('lineas-container');
            const cuentasOptions = document.querySelector('.linea-asiento select').innerHTML;

            const html = `
                <div class="linea-asiento grid grid-cols-12 gap-3 mb-3">
                    <div class="col-span-5">
                        <select name="lineas[${lineaIndex}][codigo_cuenta]" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            ${cuentasOptions}
                        </select>
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="lineas[${lineaIndex}][debe]" value="0" step="0.01" min="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm campo-debe" oninput="calcularTotales()">
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="lineas[${lineaIndex}][haber]" value="0" step="0.01" min="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm campo-haber" oninput="calcularTotales()">
                    </div>
                    <div class="col-span-2">
                        <input type="text" name="lineas[${lineaIndex}][descripcion]"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-1">
                        <button type="button" onclick="this.closest('.linea-asiento').remove(); calcularTotales();"
                                class="text-red-500 hover:text-red-700 font-bold">✕</button>
                    </div>
                </div>`;

            container.insertAdjacentHTML('beforeend', html);
            lineaIndex++;
        }

        function calcularTotales() {
            let totalDebe = 0;
            let totalHaber = 0;

            document.querySelectorAll('.campo-debe').forEach(el => {
                totalDebe += parseFloat(el.value) || 0;
            });

            document.querySelectorAll('.campo-haber').forEach(el => {
                totalHaber += parseFloat(el.value) || 0;
            });

            document.getElementById('totalDebe').textContent = '₡' + totalDebe.toFixed(2);
            document.getElementById('totalHaber').textContent = '₡' + totalHaber.toFixed(2);

            const status = document.getElementById('balance-status');
            if (Math.abs(totalDebe - totalHaber) < 0.01 && totalDebe > 0) {
                status.textContent = 'Balanceado ✓';
                status.className = 'font-bold text-green-600';
            } else {
                status.textContent = 'Desbalanceado';
                status.className = 'font-bold text-red-600';
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('campo-debe') || e.target.classList.contains('campo-haber')) {
                calcularTotales();
            }
        });
    </script>
</x-app-layout>
