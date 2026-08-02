<x-app-layout>
    @php
        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    @endphp

    <div class="max-w-5xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Finanzas
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Nuevo Presupuesto
            </h1>

            <p class="mt-2 text-gray-700 text-lg">
                Asigna un monto a cada categoría para el período seleccionado
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo guardar el presupuesto:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($copiadoDe)
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-6 py-4 rounded-2xl font-semibold">
                Copiando los montos de <span class="font-bold">{{ $meses[$copiadoDe['mes']] ?? $copiadoDe['mes'] }} {{ $copiadoDe['anio'] }}</span>.
                Ajusta el período de destino y los montos antes de guardar.
            </div>
        @endif

        {{-- Copiar desde un período existente --}}
        @if($periodosExistentes->isNotEmpty())
            <div class="bg-white rounded-2xl shadow border border-gray-200 p-5 mb-6">
                <label class="block mb-2 text-sm font-bold text-gray-700">Copiar montos desde un período anterior</label>
                <div class="flex gap-3">
                    <select id="copiar-desde"
                            class="flex-1 px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] outline-none transition">
                        <option value="">— Seleccione un período —</option>
                        @foreach($periodosExistentes as $p)
                            <option value="{{ route('presupuesto.create', ['desde_anio' => $p->anio, 'desde_mes' => $p->mes]) }}"
                                {{ ($copiadoDe && $copiadoDe['anio'] == $p->anio && $copiadoDe['mes'] == $p->mes) ? 'selected' : '' }}>
                                {{ $meses[$p->mes] ?? $p->mes }} {{ $p->anio }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" id="btn-copiar"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition">
                        Copiar
                    </button>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">Información del Presupuesto</h2>
            </div>

            <form action="{{ route('presupuesto.store') }}" method="POST" class="p-8" data-submit-on-click>
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">Año</label>
                        <input type="number" name="anio"
                               value="{{ old('anio', $copiadoDe['anio'] ?? now()->year) }}"
                               min="2020" max="2100"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">Mes</label>
                        <select name="mes"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione un mes</option>
                            @foreach($meses as $num => $nombre)
                                <option value="{{ $num }}" {{ old('mes', $copiadoDe['mes'] ?? now()->month) == $num ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-4">Montos por categoría</h3>

                <div class="space-y-3">
                    @foreach($categorias as $categoria)
                        @php
                            $valor = old("montos.{$categoria->id}", $montos[$categoria->id] ?? '');
                        @endphp
                        <div class="flex items-center gap-4 bg-gray-50 rounded-2xl px-5 py-3 border border-gray-200">
                            <div class="flex-1">
                                <p class="font-bold text-gray-700">{{ $categoria->nombre }}</p>
                                <p class="text-xs text-gray-500">{{ $categoria->descripcion }}</p>
                            </div>
                            <div class="w-56">
                                <div class="flex items-center rounded-xl border border-gray-300 bg-white overflow-hidden focus-within:border-[#b71c1c]">
                                    <span class="px-3 text-gray-500 font-bold">₡</span>
                                    <input type="number" step="0.01" min="0"
                                           name="montos[{{ $categoria->id }}]"
                                           value="{{ $valor }}"
                                           placeholder="0.00"
                                           class="w-full px-2 py-3 outline-none">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                    <span class="text-sm font-semibold text-gray-600">Total del presupuesto</span>
                    <span id="total-presupuesto" class="text-2xl font-extrabold text-[#b71c1c]">₡0.00</span>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('presupuesto.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Presupuesto
                    </button>
                </div>
            </form>
        </div>

    </div>

    <script>
        (function () {
            // Copiar desde período anterior.
            const select = document.getElementById('copiar-desde');
            const btn = document.getElementById('btn-copiar');
            if (btn && select) {
                btn.addEventListener('click', function () {
                    if (select.value) window.location.href = select.value;
                });
            }

            // Total en vivo.
            const inputs = document.querySelectorAll('input[name^="montos["]');
            const totalEl = document.getElementById('total-presupuesto');

            function fmt(n) {
                return '₡' + n.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalcular() {
                let total = 0;
                inputs.forEach(i => { total += parseFloat(i.value) || 0; });
                totalEl.textContent = fmt(total);
            }

            inputs.forEach(i => i.addEventListener('input', recalcular));
            recalcular();
        })();
    </script>
</x-app-layout>
