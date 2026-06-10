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
                    Cuentas por Cobrar
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Control de saldos pendientes y pagos de clientes
                </p>
            </div>
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
                        <th class="px-6 py-5 text-left">Vencimiento</th>
                        <th class="px-6 py-5 text-left">Monto</th>
                        <th class="px-6 py-5 text-left">Saldo</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $cuentas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cuenta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold text-gray-700">
                                <?php echo e($cuenta->numero_factura); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-700">
                                <?php echo e($cuenta->cliente->nombre ?? 'Sin cliente'); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-600">
                                <?php echo e(\Carbon\Carbon::parse($cuenta->fecha_vencimiento)->format('d/m/Y')); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡<?php echo e(number_format($cuenta->monto_original, 2)); ?>

                            </td>

                            <td class="px-6 py-5 text-gray-700 font-bold">
                                ₡<?php echo e(number_format($cuenta->saldo_pendiente, 2)); ?>

                            </td>

                            <td class="px-6 py-5 text-center">
                                <?php if(optional($cuenta->estado)->nombre === 'pagado'): ?>
                                    <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                                        Pagada
                                    </span>
                                <?php elseif(\Carbon\Carbon::parse($cuenta->fecha_vencimiento)->isPast()): ?>
                                    <span class="px-4 py-2 rounded-full bg-red-100 text-red-700 text-sm font-bold">
                                        Vencida
                                    </span>
                                <?php else: ?>
                                    <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-700 text-sm font-bold">
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <?php if(optional($cuenta->estado)->nombre !== 'pagado'): ?>
                                    <a href="<?php echo e(route('cuentas-cobrar.pago.create', [$cuenta->numero_factura, $cuenta->cliente_id])); ?>"
                                       class="inline-block bg-[#c62828] hover:bg-red-700 text-white px-5 py-2 rounded-xl font-bold transition">
                                        Registrar Pago
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400 font-semibold">
                                        Sin acciones
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 text-lg">
                                No hay cuentas por cobrar registradas
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
<?php endif; ?><?php /**PATH C:\Users\victoriaco\sistema-contable\resources\views/cuentas-cobrar/index.blade.php ENDPATH**/ ?>