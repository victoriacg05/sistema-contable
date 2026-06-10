<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Facturas
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Gestión de ventas y facturación
                </p>
            </div>

            <a href="<?php echo e(route('facturas.create')); ?>"
               class="bg-[#c62828] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nueva Factura
            </a>
        </div>

        <?php if(session('success')): ?>
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Factura</th>
                        <th class="px-6 py-5 text-left">Cliente</th>
                        <th class="px-6 py-5 text-left">Método Pago</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Total</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $facturas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $factura): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                <?php echo e($factura->numero_factura); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                <?php echo e($factura->cliente->nombre ?? 'Sin cliente'); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                <?php echo e($factura->metodoPago->nombre ?? 'N/A'); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                <?php echo e(\Carbon\Carbon::parse($factura->fecha)->format('d/m/Y')); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡<?php echo e(number_format($factura->total, 2)); ?>

                            </td>

                            <td class="px-6 py-5 text-center">
                                <?php if(optional($factura->estado)->nombre === 'pagado'): ?>
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Pagado
                                    </span>
                                <?php else: ?>
                                    <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-700 text-sm font-bold">
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-5 text-center whitespace-nowrap">

                                <?php if(optional($factura->estado)->nombre !== 'pagado'): ?>
                                    <form action="<?php echo e(route('facturas.pagar', $factura)); ?>"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('¿Deseas marcar esta factura como pagada?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>

                                        <button type="submit"
                                                class="bg-green-100 hover:bg-green-200 text-green-700 px-5 py-2 rounded-xl font-bold transition">
                                            Pagar
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="<?php echo e(route('facturas.edit', $factura)); ?>"
                                   class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl font-bold transition ml-2">
                                    Editar
                                </a>

                                <form action="<?php echo e(route('facturas.destroy', $factura)); ?>"
                                      method="POST"
                                      class="inline-block"
                                      onsubmit="return confirm('¿Está segura de eliminar esta factura? Esta acción no se puede deshacer.');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>

                                    <button type="submit"
                                            class="bg-[#c62828] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition ml-2">
                                        Eliminar
                                    </button>
                                </form>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 text-lg">
                                No hay facturas registradas
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\Users\victoriaco\sistema-contable\resources\views/facturas/index.blade.php ENDPATH**/ ?>