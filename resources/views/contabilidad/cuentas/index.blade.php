<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Catálogo de Cuentas</h1>
                <p class="mt-2 text-gray-600 text-lg">Gestión del catálogo de cuentas contables</p>
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

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden p-6" x-data="{ expanded: {} }">

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
            @endphp

            @forelse($cuentasAgrupadas as $grupo => $cuentasGrupo)
                @php
                    $cuentaPrincipal = $cuentasGrupo->firstWhere('codigo_cuenta', $grupo);
                    $subcuentas = $cuentasGrupo->where('codigo_cuenta', '!=', $grupo);
                    $nombreGrupo = $cuentaPrincipal ? $cuentaPrincipal->nombre : ($nombresTipo[$grupo] ?? 'Grupo ' . $grupo);
                @endphp

                <div class="mb-2">
                    <!-- Cuenta de primer nivel -->
                    <button @click="expanded['{{ $grupo }}'] = !expanded['{{ $grupo }}']"
                            class="w-full flex items-center justify-between px-6 py-4 rounded-xl font-bold text-lg transition
                            {{ $cuentaPrincipal && $cuentaPrincipal->estado ? 'bg-gray-100 text-[#1f2937] hover:bg-gray-200' : 'bg-red-50 text-red-400' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 transition-transform" :class="expanded['{{ $grupo }}'] ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                   @click.stop>
                                    Editar
                                </a>
                            @endif
                            <span class="text-xs text-gray-500">({{ $subcuentas->count() }} subcuentas)</span>
                        </div>
                    </button>

                    <!-- Subcuentas -->
                    <div x-show="expanded['{{ $grupo }}']" x-collapse class="ml-8 mt-1">
                        @foreach($subcuentas->sortBy('codigo_cuenta') as $subcuenta)
                            @php
                                $nivel = substr_count($subcuenta->codigo_cuenta, '.');
                                $marginLeft = ($nivel - 1) * 1.5;
                                $esBanco = stripos($subcuenta->nombre, 'banco') !== false || stripos($subcuenta->nombre, 'bancos') !== false;
                            @endphp

                            <div class="flex items-center justify-between px-4 py-3 border-l-2 border-gray-300 hover:bg-gray-50 rounded-r-lg transition"
                                 style="margin-left: {{ $marginLeft }}rem">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-sm text-[#b71c1c] font-semibold">{{ $subcuenta->codigo_cuenta }}</span>
                                    <span class="text-gray-800">{{ $subcuenta->nombre }}</span>

                                    @if($esBanco)
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-xs font-medium">
                                            BAC · BCR · BN (₡ / $)
                                        </span>
                                    @endif
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
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-gray-500">No hay cuentas registradas.</div>
            @endforelse

        </div>
    </div>
</x-app-layout>
