<nav style="width: 320px; height: 100vh; position: fixed; left: 0; top: 0; z-index: 50;"
     class="bg-white border-r border-gray-300 flex flex-col shadow-lg">

    <!-- Logo -->
    <div class="px-8 py-6 text-center border-b border-gray-200 shrink-0">
        <img
            src="{{ asset('logo.png') }}"
            alt="Ipacaraí"
            class="h-14 mx-auto mb-3">

        <h2 class="text-3xl font-extrabold text-[#252525] tracking-wide">
            IPACARAÍ
        </h2>

        <p class="mt-1 text-[11px] tracking-[0.38em] text-gray-600">
            SISTEMA CONTABLE
        </p>
    </div>

    <!-- Menú con scroll -->
    <div class="flex-1 overflow-y-auto px-5 py-5">

        <p class="px-5 mb-4 text-xs font-bold tracking-widest text-gray-600 uppercase">
            Menú principal
        </p>

        <div class="space-y-1" x-data="{ openMenu: '{{ request()->routeIs('facturas.*', 'compras.*', 'clientes.*', 'proveedores.*', 'productos.*') ? 'facturacion' : (request()->routeIs('contabilidad.*') ? 'contabilidad' : (request()->routeIs('usuarios.*', 'bitacora.*') ? 'administracion' : '')) }}' ">

            <!-- Inicio -->
            <a href="{{ route('dashboard') }}"
               class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
               {{ request()->routeIs('dashboard') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                Inicio
            </a>

            <!-- Facturación -->
            @if(Auth::user()->tienePermiso('ver_facturas') || Auth::user()->tienePermiso('ver_clientes') || Auth::user()->tienePermiso('ver_proveedores') || Auth::user()->tienePermiso('ver_productos') || Auth::user()->tienePermiso('ver_compras'))
            <div>
                <button type="button" x-on:click="openMenu = openMenu === 'facturacion' ? '' : 'facturacion'"
                        class="w-full flex items-center justify-between px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                        {{ request()->routeIs('facturas.*', 'compras.*', 'clientes.*', 'proveedores.*', 'productos.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    <span>Facturación</span>
                    <svg class="w-4 h-4 transition-transform duration-200" x-bind:class="openMenu === 'facturacion' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openMenu === 'facturacion'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="ml-4 mt-1 space-y-1 border-l-2 border-red-200 pl-3">
                    @if(Auth::user()->tienePermiso('ver_facturas'))
                    <a href="{{ route('facturas.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('facturas.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Facturas
                    </a>
                    @endif

                    @if(Auth::user()->tienePermiso('ver_compras'))
                    <a href="{{ route('compras.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('compras.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Compras
                    </a>
                    @endif

                    @if(Auth::user()->tienePermiso('ver_clientes'))
                    <a href="{{ route('clientes.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('clientes.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Clientes
                    </a>
                    @endif

                    @if(Auth::user()->tienePermiso('ver_proveedores'))
                    <a href="{{ route('proveedores.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('proveedores.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Proveedores
                    </a>
                    @endif

                    @if(Auth::user()->tienePermiso('ver_productos'))
                    <a href="{{ route('productos.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('productos.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Productos
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Contabilidad -->
            @if(Auth::user()->tienePermiso('ver_contabilidad'))
            <div>
                <button type="button" x-on:click="openMenu = openMenu === 'contabilidad' ? '' : 'contabilidad'"
                        class="w-full flex items-center justify-between px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                        {{ request()->routeIs('contabilidad.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    <span>Contabilidad</span>
                    <svg class="w-4 h-4 transition-transform duration-200" x-bind:class="openMenu === 'contabilidad' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openMenu === 'contabilidad'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="ml-4 mt-1 space-y-1 border-l-2 border-red-200 pl-3">
                    <a href="{{ route('contabilidad.cuentas.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('contabilidad.cuentas.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Catálogo de Cuentas
                    </a>

                    <a href="{{ route('contabilidad.asientos.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('contabilidad.asientos.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Asientos Contables
                    </a>
                </div>
            </div>
            @endif

            <!-- Cuentas por Cobrar -->
            @if(Auth::user()->tienePermiso('ver_cuentas_cobrar'))
                <a href="{{ route('cuentas-cobrar.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('cuentas-cobrar.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Cuentas por Cobrar
                </a>
            @endif

            <!-- Cuentas por Pagar -->
            @if(Auth::user()->tienePermiso('ver_cuentas_pagar'))
                <a href="{{ route('cuentas-pagar.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('cuentas-pagar.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Cuentas por Pagar
                </a>
            @endif

            <!-- Ingresos -->
            @if(Auth::user()->tienePermiso('ver_ingresos'))
                <a href="{{ route('ingresos.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('ingresos.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Ingresos
                </a>
            @endif

            <!-- Gastos -->
            @if(Auth::user()->tienePermiso('ver_gastos'))
                <a href="{{ route('gastos.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('gastos.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Gastos
                </a>
            @endif

            <!-- Presupuesto -->
            @if(Auth::user()->tienePermiso('ver_presupuesto'))
                <a href="{{ route('presupuesto.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('presupuesto.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Presupuesto
                </a>
            @endif

            <!-- Inventario -->
            @if(Auth::user()->tienePermiso('ver_inventario'))
                <a href="{{ route('inventario.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('inventario.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Inventario
                </a>
            @endif

            <!-- Reportes -->
            @if(Auth::user()->tienePermiso('ver_reportes'))
                <a href="{{ route('reportes.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('reportes.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Reportes
                </a>
            @endif

            <!-- Consultas -->
            @if(Auth::user()->tienePermiso('ver_consultas'))
                <a href="{{ route('consultas.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('consultas.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    Consultas
                </a>
            @endif

            <!-- Administración -->
            @if(Auth::user()->tienePermiso('ver_usuarios') || Auth::user()->tienePermiso('ver_bitacora'))
            <div>
                <button type="button" x-on:click="openMenu = openMenu === 'administracion' ? '' : 'administracion'"
                        class="w-full flex items-center justify-between px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                        {{ request()->routeIs('usuarios.*', 'bitacora.*') ? 'bg-[#b71c1c] text-white shadow-md' : 'text-gray-800 hover:bg-red-100 hover:text-[#b71c1c]' }}">
                    <span>Administración</span>
                    <svg class="w-4 h-4 transition-transform duration-200" x-bind:class="openMenu === 'administracion' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openMenu === 'administracion'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="ml-4 mt-1 space-y-1 border-l-2 border-red-200 pl-3">
                    @if(Auth::user()->tienePermiso('ver_usuarios'))
                    <a href="{{ route('usuarios.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('usuarios.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Usuarios
                    </a>
                    @endif

                    @if(Auth::user()->tienePermiso('ver_bitacora'))
                    <a href="{{ route('bitacora.index') }}"
                       class="block px-4 py-2 rounded-xl text-sm font-medium transition
                       {{ request()->routeIs('bitacora.*') ? 'bg-red-100 text-[#b71c1c] font-bold' : 'text-gray-700 hover:bg-red-50 hover:text-[#b71c1c]' }}">
                        Bitácora
                    </a>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>

    <!-- Usuario fijo abajo -->
    <div class="border-t border-gray-300 p-4 bg-gray-50 shrink-0">

        @auth
            <div class="mb-3">
                <p class="font-bold text-[#252525] text-sm">
                    {{ Auth::user()->name }}
                </p>

                <p class="text-xs text-gray-600 mt-1 break-all">
                    {{ Auth::user()->email }}
                </p>

                <span class="inline-block mt-2 px-3 py-1 rounded-full bg-red-100 text-[#b71c1c] text-xs font-bold">
                    {{ Auth::user()->role->nombre ?? 'Sin rol' }}
                </span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="w-full bg-[#b71c1c] hover:bg-red-800 text-white py-2.5 rounded-2xl font-semibold transition duration-300 shadow-md">
                    Cerrar Sesión
                </button>
            </form>
        @endauth

    </div>

</nav>