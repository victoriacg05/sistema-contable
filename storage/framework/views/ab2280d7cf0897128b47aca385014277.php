<nav style="width: 320px; height: 100vh; position: fixed; left: 0; top: 0; z-index: 50;"
     class="bg-white border-r border-gray-200 flex flex-col shadow-lg">

    <!-- Logo -->
    <div class="px-8 py-7 text-center border-b border-gray-100">
        <img
            src="<?php echo e(asset('logo.png')); ?>"
            alt="Ipacaraí"
            class="h-16 mx-auto mb-3">

        <h2 class="text-3xl font-extrabold text-[#252525] tracking-wide">
            IPACARAÍ
        </h2>

        <p class="mt-1 text-[11px] tracking-[0.38em] text-gray-500">
            SISTEMA CONTABLE
        </p>
    </div>

    <!-- Menú -->
    <div class="flex-1 px-5 py-6">

        <p class="px-5 mb-4 text-xs font-bold tracking-widest text-gray-400 uppercase">
            Menú principal
        </p>

        <div class="space-y-2">

            <a href="<?php echo e(route('dashboard')); ?>"
               class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
               <?php echo e(request()->routeIs('dashboard') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                Inicio
            </a>

            <?php if(Auth::user()->tienePermiso('ver_clientes')): ?>
                <a href="<?php echo e(route('clientes.index')); ?>"
                   class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                   <?php echo e(request()->routeIs('clientes.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Clientes
                </a>
            <?php endif; ?>

            <?php if(Auth::user()->tienePermiso('ver_proveedores')): ?>
                <a href="<?php echo e(route('proveedores.index')); ?>"
                   class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                   <?php echo e(request()->routeIs('proveedores.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Proveedores
                </a>
            <?php endif; ?>

            <?php if(Auth::user()->tienePermiso('ver_productos')): ?>
                <a href="<?php echo e(route('productos.index')); ?>"
                   class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                   <?php echo e(request()->routeIs('productos.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Productos
                </a>
            <?php endif; ?>

            <?php if(Auth::user()->tienePermiso('ver_facturas')): ?>
                <a href="<?php echo e(route('facturas.index')); ?>"
                   class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                   <?php echo e(request()->routeIs('facturas.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Facturas
                </a>
            <?php endif; ?>

            <?php if(Auth::user()->tienePermiso('ver_cuentas_cobrar')): ?>
                <a href="<?php echo e(route('cuentas-cobrar.index')); ?>"
                    class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                    <?php echo e(request()->routeIs('cuentas-cobrar.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Cuentas por Cobrar
                </a>
            <?php endif; ?>

            <?php if(Auth::user()->tienePermiso('ver_compras')): ?>
                <a href="<?php echo e(route('compras.index')); ?>"
                   class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                   <?php echo e(request()->routeIs('compras.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Compras
                </a>
            <?php endif; ?>

            <?php if(Auth::user()->tienePermiso('ver_usuarios')): ?>
                <a href="<?php echo e(route('usuarios.index')); ?>"
                   class="block px-5 py-3 rounded-2xl font-semibold transition duration-300
                   <?php echo e(request()->routeIs('usuarios.*') ? 'bg-[#c62828] text-white shadow-md' : 'text-gray-700 hover:bg-[#f8e8e8] hover:text-[#c62828]'); ?>">
                    Usuarios
                </a>
            <?php endif; ?>

        </div>

    </div>

    <!-- Usuario -->
    <div class="border-t border-gray-200 p-5 bg-[#fafafa]">

        <?php if(auth()->guard()->check()): ?>
            <div class="mb-4">
                <p class="font-bold text-[#252525] text-base">
                    <?php echo e(Auth::user()->name); ?>

                </p>

                <p class="text-sm text-gray-500 mt-1 break-all">
                    <?php echo e(Auth::user()->email); ?>

                </p>

                <span class="inline-block mt-3 px-3 py-1 rounded-full bg-[#f8e8e8] text-[#c62828] text-xs font-bold">
                    <?php echo e(Auth::user()->role->nombre ?? 'Sin rol'); ?>

                </span>
            </div>

            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>

                <button
                    type="submit"
                    class="w-full bg-[#c62828] hover:bg-red-700 text-white py-3 rounded-2xl font-semibold transition duration-300 shadow-md">
                    Cerrar Sesión
                </button>
            </form>
        <?php endif; ?>

    </div>

</nav><?php /**PATH C:\Users\victoriaco\sistema-contable\resources\views/layouts/navigation.blade.php ENDPATH**/ ?>