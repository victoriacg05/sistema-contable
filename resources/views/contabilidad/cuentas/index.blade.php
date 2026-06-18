<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Catalogo de Cuentas</h1>
                <p class="mt-2 text-gray-600 text-lg">Gestion del catalogo de cuentas contables</p>
            </div>

            <a href="{{ route('contabilidad.cuentas.create') }}"
               class="bg-[#b71c1c] hover:bg-red-800 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nueva Cuenta
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <style>
            .cuenta-details > summary { list-style: none; cursor: pointer; }
            .cuenta-details > summary::-webkit-details-marker { display: none; }
            .cuenta-details > summary::marker { display: none; }
            .cuenta-details[open] > summary .cuenta-arrow { transform: rotate(90deg); }
            .cuenta-arrow { transition: transform 0.2s ease; }
        </style>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden p-6">

            @php
                $cuentasAgrupadas = $cuentas->groupBy(function($cuenta) {
                    $partes = explode('.', $cuenta->codigo_cuenta);
                    return $partes[0];
                });

                $nombresTipo = [
                    '1' => 'Activos',
                    '2' => 'Pasivos',
                    '3' => 'Capital / Patrimonio',
                    '4' => 'Ingresos',
                    '5' => 'Gastos',
                    '6' => 'Costos',
                ];

                $bancosCR = [
                    ['nombre' => 'BAC San Jose', 'monedas' => ['Colones', 'Dolares']],
                    ['nombre' => 'Banco de Costa Rica (BCR)', 'monedas' => ['Colones', 'Dolares']],
                    ['nombre' => 'Banco Nacional (BN)', 'monedas' => ['Colones', 'Dolares']],
                    ['nombre' => 'Scotiabank', 'monedas' => ['Colones', 'Dolares']],
                    ['nombre' => 'Davivienda', 'monedas' => ['Colones', 'Dolares']],
                    ['nombre' => 'Banco Promerica', 'monedas' => ['Colones', 'Dolares']],
                ];
            @endphp

            @forelse($cuentasAgrupadas as $grupo => $cuentasGrupo)
                @php
                    $cuentaPrincipal = $cuentasGrupo->firstWhere('codigo_cuenta', $grupo);
                    $subcuentas = $cuentasGrupo->where('codigo_cuenta', '!=', $grupo);
                    $nombreGrupo = $cuentaPrincipal ? $cuentaPrincipal->nombre : ($nombresTipo[$grupo] ?? 'Grupo ' . $grupo);
                @endphp

                <details class="cuenta-details mb-2">
                    <summary class="flex items-center justify-between px-6 py-4 rounded-xl font-bold text-lg transition
                        {{ $cuentaPrincipal && $cuentaPrincipal->estado ? 'bg-gray-100 text-[#1f2937] hover:bg-gray-200' : 'bg-red-50 text-red-400' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 cuenta-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="font-mono text-[#b71c1c]">{{ $grupo }}</span>
                            <span>{{ $nombreGrupo }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($cuentaPrincipal)
                                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                    {{ $cuentaPrincipal->tipo_nombre }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $cuentaPrincipal->estado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $cuentaPrincipal->estado ? 'Activa' : 'Inactiva' }}
                                </span>
                                <a href="{{ route('contabilidad.cuentas.edit', $cuentaPrincipal->codigo_cuenta) }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm font-semibold"
                                   onclick="event.stopPropagation()">
                                    Editar
                                </a>
                            @endif
                            <span class="text-xs text-gray-700">({{ $subcuentas->count() }} subcuentas)</span>
                        </div>
                    </summary>

                    <div class="ml-8 mt-1">
                        @foreach($subcuentas->sortBy('codigo_cuenta') as $subcuenta)
                            @php
                                $nivel = substr_count($subcuenta->codigo_cuenta, '.');
                                $marginLeft = ($nivel - 1) * 1.5;
                                $esBanco = stripos($subcuenta->nombre, 'banco') !== false || stripos($subcuenta->nombre, 'bancos') !== false;
                            @endphp

                            @if($esBanco)
                                {{-- Cuenta de Bancos con submenu de entidades bancarias --}}
                                <details class="cuenta-details" style="margin-left: {{ $marginLeft }}rem">
                                    <summary class="flex items-center justify-between px-4 py-3 border-l-2 border-blue-300 hover:bg-blue-50 rounded-r-lg transition bg-blue-50">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-4 h-4 cuenta-arrow text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            <span class="font-mono text-sm text-[#b71c1c] font-semibold">{{ $subcuenta->codigo_cuenta }}</span>
                                            <span class="text-gray-800 font-semibold">{{ $subcuenta->nombre }}</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                                {{ $subcuenta->tipo_nombre }}
                                            </span>
                                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $subcuenta->estado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $subcuenta->estado ? 'Activa' : 'Inactiva' }}
                                            </span>
                                            <a href="{{ route('contabilidad.cuentas.edit', $subcuenta->codigo_cuenta) }}"
                                               class="text-blue-600 hover:text-blue-800 text-sm font-semibold"
                                               onclick="event.stopPropagation()">
                                                Editar
                                            </a>
                                        </div>
                                    </summary>

                                    <div class="ml-6 mt-1 mb-2 space-y-1">
                                        @foreach($bancosCR as $banco)
                                            <div class="border-l-2 border-blue-200 pl-4 py-2 hover:bg-blue-50 rounded-r-lg transition">
                                                <p class="font-semibold text-gray-800 text-sm">{{ $banco['nombre'] }}</p>
                                                <div class="flex gap-3 mt-1">
                                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs font-medium">Colones (&#8353;)</span>
                                                    <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-xs font-medium">Dolares ($)</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                {{-- Subcuenta normal --}}
                                <div class="flex items-center justify-between px-4 py-3 border-l-2 border-gray-300 hover:bg-gray-50 rounded-r-lg transition"
                                     style="margin-left: {{ $marginLeft }}rem">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-sm text-[#b71c1c] font-semibold">{{ $subcuenta->codigo_cuenta }}</span>
                                        <span class="text-gray-800">{{ $subcuenta->nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                            {{ $subcuenta->tipo_nombre }}
                                        </span>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $subcuenta->estado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $subcuenta->estado ? 'Activa' : 'Inactiva' }}
                                        </span>
                                        <a href="{{ route('contabilidad.cuentas.edit', $subcuenta->codigo_cuenta) }}"
                                           class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                            Editar
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </details>
            @empty
                <div class="px-6 py-10 text-center text-gray-700">No hay cuentas registradas.</div>
            @endforelse

        </div>
    </div>
</x-app-layout>