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
    <div class="max-w-5xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#c62828] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Cuentas por Cobrar
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Registrar Pago
            </h1>

            <p class="mt-2 text-gray-500 text-lg">
                Factura <?php echo e($cuenta->numero_factura); ?> - <?php echo e($cuenta->cliente->nombre ?? 'Sin cliente'); ?>

            </p>
        </div>

        <?php if($errors->any()): ?>
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo registrar el pago:</p>

                <ul class="list-disc list-inside">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Pago
                </h2>
            </div>

            <form action="<?php echo e(route('cuentas-cobrar.pago.store', [$cuenta->numero_factura, $cuenta->cliente_id])); ?>"
                  method="POST"
                  class="p-8">
                <?php echo csrf_field(); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Saldo pendiente
                        </label>

                        <input type="text"
                               value="₡<?php echo e(number_format($cuenta->saldo_pendiente, 2)); ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-100"
                               readonly>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Monto pagado
                        </label>

                        <input type="number"
                               step="0.01"
                               name="monto_pagado"
                               value="<?php echo e(old('monto_pagado')); ?>"
                               min="1"
                               max="<?php echo e($cuenta->saldo_pendiente); ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Método de pago
                        </label>

                        <select name="metodo_pago_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                                required>
                            <option value="">Seleccione método de pago</option>

                            <?php $__currentLoopData = $metodosPago; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $metodo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($metodo->id); ?>" <?php echo e(old('metodo_pago_id') == $metodo->id ? 'selected' : ''); ?>>
                                    <?php echo e($metodo->nombre); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Observación
                        </label>

                        <textarea name="observacion"
                                  rows="3"
                                  class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"><?php echo e(old('observacion')); ?></textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="<?php echo e(route('cuentas-cobrar.index')); ?>"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#c62828] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Pago
                    </button>
                </div>
            </form>
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
<?php endif; ?><?php /**PATH C:\Users\victoriaco\sistema-contable\resources\views/cuentas-cobrar/pago.blade.php ENDPATH**/ ?>