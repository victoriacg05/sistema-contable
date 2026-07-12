<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Finanzas
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Editar Gasto
            </h1>

            <p class="mt-2 text-gray-700 text-lg">
                Actualiza la información del gasto {{ $gasto->numero_comprobante }}
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo actualizar el gasto:</p>

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
                    Información del Gasto
                </h2>
            </div>

            <form action="{{ route('gastos.update', [$gasto->numero_comprobante, $gasto->categoria_gasto_id, $gasto->fecha]) }}"
                  method="POST"
                  class="p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Categoría
                        </label>

                        <select name="categoria_gasto_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione una categoría</option>

                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}"
                                        data-clasificacion="{{ $categoria->clasificacion ?? 'Indirecto' }}"
                                        {{ old('categoria_gasto_id', $gasto->categoria_gasto_id) == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Clasificación
                        </label>

                        <div class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 flex items-center min-h-[60px]">
                            <span id="clasificacion-badge"
                                  class="hidden px-4 py-1.5 rounded-full text-sm font-bold"></span>
                            <span id="clasificacion-vacia" class="text-gray-400">
                                Seleccione una categoría
                            </span>
                        </div>

                        <p class="mt-1 text-xs text-gray-500">
                            Se determina automáticamente según la categoría del gasto.
                        </p>
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
                                <option value="{{ $metodo->id }}" {{ old('metodo_pago_id', $gasto->metodo_pago_id) == $metodo->id ? 'selected' : '' }}>
                                    {{ $metodo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Monto
                        </label>

                        <input type="number"
                               step="0.01"
                               name="monto"
                               value="{{ old('monto', $gasto->monto) }}"
                               min="1"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Fecha
                        </label>

                        <input type="date"
                               name="fecha"
                               value="{{ old('fecha', \Carbon\Carbon::parse($gasto->fecha)->format('Y-m-d')) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div class="md:col-span-2">
                        <div id="panel-presupuesto"
                             class="hidden rounded-2xl border px-6 py-5 bg-gray-50 border-gray-200">
                            <p class="text-sm font-bold text-gray-700 mb-3">
                                Control presupuestario · <span id="pp-periodo"></span>
                            </p>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Presupuesto aprobado</p>
                                    <p id="pp-aprobado" class="text-lg font-extrabold text-gray-800">₡0.00</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Consumido</p>
                                    <p id="pp-consumido" class="text-lg font-extrabold text-gray-800">₡0.00</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Disponible</p>
                                    <p id="pp-disponible" class="text-lg font-extrabold text-green-700">₡0.00</p>
                                </div>
                            </div>
                            <p id="pp-alerta" class="hidden mt-3 text-sm font-bold text-red-700"></p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Descripción
                        </label>

                        <textarea name="descripcion"
                                  rows="3"
                                  class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">{{ old('descripcion', $gasto->descripcion) }}</textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('gastos.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Actualizar Gasto
                    </button>
                </div>
            </form>
        </div>

    </div>

    <script>
        (function () {
            const categoriaSel = document.querySelector('select[name="categoria_gasto_id"]');
            const badge = document.getElementById('clasificacion-badge');
            const vacia = document.getElementById('clasificacion-vacia');

            function actualizarClasificacion() {
                const opcion = categoriaSel.options[categoriaSel.selectedIndex];
                const valor = opcion ? opcion.getAttribute('data-clasificacion') : '';

                if (!categoriaSel.value || !valor) {
                    badge.classList.add('hidden');
                    vacia.classList.remove('hidden');
                    return;
                }

                badge.textContent = valor;
                badge.classList.remove('hidden', 'bg-blue-100', 'text-blue-800', 'bg-purple-100', 'text-purple-800');

                if (valor === 'Directo') {
                    badge.classList.add('bg-blue-100', 'text-blue-800');
                } else {
                    badge.classList.add('bg-purple-100', 'text-purple-800');
                }

                vacia.classList.add('hidden');
            }

            categoriaSel.addEventListener('change', actualizarClasificacion);
            actualizarClasificacion();

            // --- Control presupuestario en tiempo real ---
            const fechaInput = document.querySelector('input[name="fecha"]');
            const montoInput = document.querySelector('input[name="monto"]');
            const panel = document.getElementById('panel-presupuesto');
            const ppPeriodo = document.getElementById('pp-periodo');
            const ppAprobado = document.getElementById('pp-aprobado');
            const ppConsumido = document.getElementById('pp-consumido');
            const ppDisponible = document.getElementById('pp-disponible');
            const ppAlerta = document.getElementById('pp-alerta');
            const disponibleUrl = "{{ route('presupuesto.disponible') }}";
            const montoOriginal = {{ (float) $gasto->monto }};

            const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            let dispActual = null;

            function fmt(n) {
                return '₡' + Number(n).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function evaluarMonto() {
                if (dispActual === null) return;
                const monto = parseFloat(montoInput.value) || 0;
                if (monto > dispActual) {
                    ppAlerta.textContent = 'El monto ingresado supera el presupuesto disponible para esta categoría.';
                    ppAlerta.classList.remove('hidden');
                    panel.classList.remove('bg-gray-50', 'border-gray-200');
                    panel.classList.add('bg-red-50', 'border-red-300');
                } else {
                    ppAlerta.classList.add('hidden');
                    panel.classList.remove('bg-red-50', 'border-red-300');
                    panel.classList.add('bg-gray-50', 'border-gray-200');
                }
            }

            function cargarPresupuesto() {
                const categoria = categoriaSel.value;
                const fecha = fechaInput.value;
                if (!categoria || !fecha) {
                    panel.classList.add('hidden');
                    dispActual = null;
                    return;
                }

                const d = new Date(fecha + 'T00:00:00');
                const anio = d.getFullYear();
                const mes = d.getMonth() + 1;

                const url = disponibleUrl + '?categoria_gasto_id=' + encodeURIComponent(categoria)
                    + '&anio=' + anio + '&mes=' + mes;

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.tiene_presupuesto) {
                            panel.classList.add('hidden');
                            dispActual = null;
                            return;
                        }
                        // Excluir el propio gasto que se está editando
                        const ejecutado = Math.max(0, data.ejecutado - montoOriginal);
                        const disponible = data.presupuestado - ejecutado;
                        ppPeriodo.textContent = meses[mes - 1] + ' ' + anio;
                        ppAprobado.textContent = fmt(data.presupuestado);
                        ppConsumido.textContent = fmt(ejecutado);
                        ppDisponible.textContent = fmt(disponible);
                        ppDisponible.classList.toggle('text-red-700', disponible < 0);
                        ppDisponible.classList.toggle('text-green-700', disponible >= 0);
                        dispActual = disponible;
                        panel.classList.remove('hidden');
                        evaluarMonto();
                    })
                    .catch(() => {
                        panel.classList.add('hidden');
                        dispActual = null;
                    });
            }

            categoriaSel.addEventListener('change', cargarPresupuesto);
            fechaInput.addEventListener('change', cargarPresupuesto);
            montoInput.addEventListener('input', evaluarMonto);
            cargarPresupuesto();
        })();
    </script>
</x-app-layout>