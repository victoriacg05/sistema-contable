<nav style="width: 320px; height: 100vh; position: fixed; left: 0; top: 0; z-index: 50;"
     class="bg-white border-r border-gray-200 flex flex-col shadow-lg">

    <!-- Logo -->
    <div class="px-8 py-6 text-center border-b border-gray-100 shrink-0">
        <img
            src="{{ asset('logo.png') }}"
            alt="Ipacaraí"
            class="h-14 mx-auto mb-3">

        <h2 class="text-3xl font-extrabold text-[#252525] tracking-wide">
            IPACARAÍ
        </h2>

        <p class="mt-1 text-[11px] tracking-[0.38em] text-gray-500">
            SISTEMA CONTABLE
        </p>
    </div>

    <!-- Menú con scroll -->
    <div class="flex-1 overflow-y-auto px-5 py-5">

        <p class="px-5 mb-4 text-xs font-bold tracking-widest text-gray-400 uppercase">
            Menú principal
        </p>

        <div class="space-y-1.5">

            <a href="{{ route('dashboard') }}"
               class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
               {{ request()->routeIs('dashboard') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                Inicio
            </a>

            @if(Auth::user()->tienePermiso('ver_clientes'))
                <a href="{{ route('clientes.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('clientes.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Clientes
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_proveedores'))
                <a href="{{ route('proveedores.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('proveedores.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Proveedores
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_productos'))
                <a href="{{ route('productos.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('productos.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Productos
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_facturas'))
                <a href="{{ route('facturas.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('facturas.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Facturas
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_cuentas_cobrar'))
                <a href="{{ route('cuentas-cobrar.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('cuentas-cobrar.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Cuentas por Cobrar
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_cuentas_pagar'))
                <a href="{{ route('cuentas-pagar.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('cuentas-pagar.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Cuentas por Pagar
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_ingresos'))
                <a href="{{ route('ingresos.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('ingresos.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Ingresos
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_gastos'))
                <a href="{{ route('gastos.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('gastos.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Gastos
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_presupuesto'))
                <a href="{{ route('presupuesto.index') }}"
                class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                {{ request()->routeIs('presupuesto.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Presupuesto
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_reportes'))
                <a href="{{ route('reportes.index') }}"
                class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                {{ request()->routeIs('reportes.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Reportes
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_compras'))
                <a href="{{ route('compras.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('compras.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Compras
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_contabilidad'))
                <a href="{{ route('contabilidad.cuentas.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('contabilidad.cuentas.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Catálogo de Cuentas
                </a>

                <a href="{{ route('contabilidad.asientos.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('contabilidad.asientos.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Asientos Contables
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_inventario'))
                <a href="{{ route('inventario.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('inventario.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Inventario
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_consultas'))
                <a href="{{ route('consultas.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('consultas.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Consultas
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_usuarios'))
                <a href="{{ route('usuarios.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('usuarios.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Usuarios
                </a>
            @endif

            @if(Auth::user()->tienePermiso('ver_bitacora'))
                <a href="{{ route('bitacora.index') }}"
                   class="block px-5 py-2.5 rounded-2xl font-semibold transition duration-300
                   {{ request()->routeIs('bitacora.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]' }}">
                    Bitácora
                </a>
            @endif

        </div>
    </div>

    <!-- Usuario fijo abajo -->
    <div class="border-t border-gray-200 p-4 bg-[#fafafa] shrink-0">

        @auth
            <div class="mb-3">
                <p class="font-bold text-[#252525] text-sm">
                    {{ Auth::user()->name }}
                </p>

                <p class="text-xs text-gray-500 mt-1 break-all">
                    {{ Auth::user()->email }}
                </p>

                <span class="inline-block mt-2 px-3 py-1 rounded-full bg-[#f8e8e8] text-[#c62828] text-xs font-bold">
                    {{ Auth::user()->role->nombre ?? 'Sin rol' }}
                </span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="w-full bg-[#c62828] hover:bg-red-700 text-white py-2.5 rounded-2xl font-semibold transition duration-300 shadow-md">
                    Cerrar Sesión
                </button>
            </form>
        @endauth

    </div>

</nav>