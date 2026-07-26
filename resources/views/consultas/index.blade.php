<x-app-layout>
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="rounded-[2rem] bg-gradient-to-r from-gray-900 to-gray-800 p-8 text-white shadow-lg">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-red-100">
                        Consulta segura · Solo lectura
                    </span>
                    <h1 class="mt-4 text-4xl font-extrabold">Centro de consultas</h1>
                    <p class="mt-3 max-w-3xl text-lg leading-relaxed text-gray-200">
                        Busque información de forma rápida para análisis o verificación.
                        Este módulo nunca modifica los datos y solo muestra los módulos permitidos para su usuario.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 px-6 py-5 text-sm text-gray-200">
                    <p class="font-bold text-white">¿Cómo funciona?</p>
                    <p class="mt-2">1. Elija un módulo.</p>
                    <p>2. Escriba un dato conocido o use fechas.</p>
                    <p>3. Revise los resultados sin alterar registros.</p>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-300 bg-red-50 px-6 py-4 text-red-800">
                <p class="font-bold">Revise los criterios de búsqueda:</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section>
            <div class="mb-4">
                <h2 class="text-2xl font-extrabold text-gray-900">¿Qué información desea consultar?</h2>
                <p class="mt-1 text-gray-600">Seleccione una opción para preparar automáticamente los filtros adecuados.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($modulos as $clave => $modulo)
                    <button type="button"
                            data-modulo="{{ $clave }}"
                            class="modulo-card rounded-3xl border p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md
                                {{ $moduloSeleccionado === $clave ? 'border-red-700 bg-red-50 ring-2 ring-red-100' : 'border-gray-200 bg-white hover:border-red-300' }}">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-11 min-w-11 items-center justify-center rounded-2xl bg-gray-900 px-2 text-sm font-extrabold text-white">
                                {{ $modulo['sigla'] }}
                            </span>
                            <div>
                                <p class="font-extrabold text-gray-900">{{ $modulo['nombre'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-gray-500">{{ $modulo['descripcion'] }}</p>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-800">
                        No tiene módulos de información habilitados para consultar.
                    </div>
                @endforelse
            </div>
        </section>

        @if($modulos)
            <form method="GET"
                  action="{{ route('consultas.buscar') }}"
                  id="form-consulta"
                  class="rounded-[2rem] border border-gray-200 bg-white p-7 shadow-lg">
                <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Criterios de búsqueda</h2>
                        <p class="mt-1 text-gray-500">Puede buscar solo por módulo para ver los registros más recientes.</p>
                    </div>
                    @if($resultados)
                        <a href="{{ route('consultas.index') }}"
                           class="font-bold text-red-700 transition hover:text-red-900">
                            Limpiar consulta
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-gray-700">Módulo</label>
                        <select name="modulo"
                                id="modulo"
                                class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-red-700 focus:bg-white focus:ring-2 focus:ring-red-100"
                                required>
                            <option value="">Seleccione la información</option>
                            @foreach($modulos as $clave => $modulo)
                                <option value="{{ $clave }}" {{ $moduloSeleccionado === $clave ? 'selected' : '' }}>
                                    {{ $modulo['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="{{ $configuracion && $configuracion['campo_fecha'] ? 'xl:col-span-1' : 'xl:col-span-3' }}">
                        <label class="mb-2 block text-sm font-bold text-gray-700">Dato conocido</label>
                        <input type="text"
                               name="termino"
                               id="termino"
                               value="{{ $termino }}"
                               placeholder="{{ $configuracion['placeholder'] ?? 'Seleccione primero un módulo' }}"
                               class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-red-700 focus:bg-white focus:ring-2 focus:ring-red-100">
                        <p id="ayuda-busqueda" class="mt-2 text-xs text-gray-500">
                            {{ $configuracion['descripcion'] ?? 'La ayuda cambiará según el módulo seleccionado.' }}
                        </p>
                    </div>

                    <div id="grupo-fecha-desde" class="{{ $configuracion && $configuracion['campo_fecha'] ? '' : 'hidden' }}">
                        <label class="mb-2 block text-sm font-bold text-gray-700">Desde</label>
                        <input type="date"
                               name="fecha_desde"
                               value="{{ $fechaDesde }}"
                               class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-red-700 focus:bg-white focus:ring-2 focus:ring-red-100">
                    </div>

                    <div id="grupo-fecha-hasta" class="{{ $configuracion && $configuracion['campo_fecha'] ? '' : 'hidden' }}">
                        <label class="mb-2 block text-sm font-bold text-gray-700">Hasta</label>
                        <input type="date"
                               name="fecha_hasta"
                               value="{{ $fechaHasta }}"
                               class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-5 py-4 outline-none transition focus:border-red-700 focus:bg-white focus:ring-2 focus:ring-red-100">
                    </div>
                </div>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="submit"
                            class="rounded-2xl bg-[#b71c1c] px-8 py-4 font-bold text-white shadow-md transition hover:bg-red-800">
                        Consultar información
                    </button>
                </div>
            </form>
        @endif

        @if($resultados)
            <section class="space-y-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="text-sm font-bold uppercase tracking-wide text-red-700">Resultados</span>
                        <h2 class="mt-1 text-3xl font-extrabold text-gray-900">{{ $configuracion['nombre'] }}</h2>
                        <p class="mt-2 text-gray-600">
                            {{ number_format($resultados->total()) }} registro(s) encontrado(s)
                            @if($termino)
                                para “{{ $termino }}”
                            @endif
                        </p>
                    </div>

                    @if($totalMonetario !== null)
                        <div class="rounded-2xl border border-green-200 bg-green-50 px-6 py-4 text-right">
                            <p class="text-sm font-bold text-green-700">Total de los resultados</p>
                            <p class="mt-1 text-2xl font-extrabold text-green-900">₡{{ number_format($totalMonetario, 2) }}</p>
                        </div>
                    @endif
                </div>

                <div class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-lg">
                    @if($resultados->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-max">
                                <thead class="bg-gray-900 text-white">
                                    <tr>
                                        @foreach($configuracion['columnas'] as $columna)
                                            <th class="px-5 py-4 text-left text-sm font-bold">{{ $columna['etiqueta'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($resultados as $fila)
                                        <tr class="border-t border-gray-100 transition hover:bg-gray-50">
                                            @foreach($configuracion['columnas'] as $campo => $columna)
                                                @php
                                                    $valor = $fila->{$campo} ?? null;
                                                    $tipo = $columna['tipo'];
                                                    $estadoNormalizado = strtolower((string) $valor);
                                                @endphp
                                                <td class="max-w-sm px-5 py-4 text-gray-700">
                                                    @if($tipo === 'money')
                                                        <span class="font-bold text-gray-900">₡{{ number_format((float) $valor, 2) }}</span>
                                                    @elseif($tipo === 'date')
                                                        {{ $valor ? \Carbon\Carbon::parse($valor)->format('d/m/Y') : '—' }}
                                                    @elseif($tipo === 'percent')
                                                        {{ number_format((float) $valor, 2) }}%
                                                    @elseif($tipo === 'number')
                                                        {{ number_format((float) $valor, 0) }}
                                                    @elseif($tipo === 'boolean')
                                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $valor ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                                            {{ $valor ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    @elseif($tipo === 'status')
                                                        @php
                                                            $claseEstado = str_contains($estadoNormalizado, 'pagado')
                                                                || str_contains($estadoNormalizado, 'aprobado')
                                                                || str_contains($estadoNormalizado, 'entrada')
                                                                || str_contains($estadoNormalizado, 'positivo')
                                                                    ? 'bg-green-100 text-green-700'
                                                                    : (str_contains($estadoNormalizado, 'anulado')
                                                                        || str_contains($estadoNormalizado, 'vencido')
                                                                        || str_contains($estadoNormalizado, 'salida')
                                                                        || str_contains($estadoNormalizado, 'negativo')
                                                                            ? 'bg-red-100 text-red-700'
                                                                            : 'bg-amber-100 text-amber-800');
                                                        @endphp
                                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $claseEstado }}">
                                                            {{ $valor ?: 'Sin estado' }}
                                                        </span>
                                                    @else
                                                        <span class="block truncate" title="{{ $valor }}">{{ $valor ?: '—' }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-xl font-extrabold text-gray-500">
                                0
                            </div>
                            <h3 class="mt-4 text-xl font-extrabold text-gray-900">No se encontraron registros</h3>
                            <p class="mt-2 text-gray-500">Pruebe con menos texto, cambie las fechas o consulte todo el módulo.</p>
                        </div>
                    @endif
                </div>

                @if($resultados->hasPages())
                    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                        {{ $resultados->links() }}
                    </div>
                @endif
            </section>
        @else
            <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <span class="text-3xl font-extrabold text-red-700">1</span>
                    <h3 class="mt-3 text-lg font-extrabold text-gray-900">Seleccione el módulo</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500">Use las tarjetas para indicar qué información necesita revisar.</p>
                </div>
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <span class="text-3xl font-extrabold text-red-700">2</span>
                    <h3 class="mt-3 text-lg font-extrabold text-gray-900">Aplique filtros opcionales</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500">Puede escribir una referencia, nombre o estado y limitar por fechas.</p>
                </div>
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <span class="text-3xl font-extrabold text-red-700">3</span>
                    <h3 class="mt-3 text-lg font-extrabold text-gray-900">Analice sin modificar</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500">Los resultados son únicamente informativos y respetan sus permisos.</p>
                </div>
            </section>
        @endif
    </div>

    <script>
        (function () {
            const configuraciones = @json(collect($modulos)->map(fn ($modulo) => [
                'placeholder' => $modulo['placeholder'],
                'descripcion' => $modulo['descripcion'],
                'usaFechas' => (bool) $modulo['campo_fecha'],
            ]));
            const selectModulo = document.getElementById('modulo');
            const termino = document.getElementById('termino');
            const ayuda = document.getElementById('ayuda-busqueda');
            const fechaDesde = document.getElementById('grupo-fecha-desde');
            const fechaHasta = document.getElementById('grupo-fecha-hasta');
            const tarjetas = document.querySelectorAll('.modulo-card');

            function seleccionarModulo(modulo) {
                if (!selectModulo || !configuraciones[modulo]) {
                    return;
                }

                selectModulo.value = modulo;
                termino.placeholder = configuraciones[modulo].placeholder;
                ayuda.textContent = configuraciones[modulo].descripcion;
                fechaDesde.classList.toggle('hidden', !configuraciones[modulo].usaFechas);
                fechaHasta.classList.toggle('hidden', !configuraciones[modulo].usaFechas);

                tarjetas.forEach(function (tarjeta) {
                    const activa = tarjeta.dataset.modulo === modulo;
                    tarjeta.classList.toggle('border-red-700', activa);
                    tarjeta.classList.toggle('bg-red-50', activa);
                    tarjeta.classList.toggle('ring-2', activa);
                    tarjeta.classList.toggle('ring-red-100', activa);
                    tarjeta.classList.toggle('border-gray-200', !activa);
                    tarjeta.classList.toggle('bg-white', !activa);
                });

                document.getElementById('form-consulta')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
                termino.focus();
            }

            tarjetas.forEach(function (tarjeta) {
                tarjeta.addEventListener('click', function () {
                    seleccionarModulo(tarjeta.dataset.modulo);
                });
            });

            selectModulo?.addEventListener('change', function () {
                seleccionarModulo(selectModulo.value);
            });
        })();
    </script>
</x-app-layout>
