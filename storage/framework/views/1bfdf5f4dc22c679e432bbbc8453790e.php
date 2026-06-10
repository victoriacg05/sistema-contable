<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>Ipacaraí - Sistema Contable</title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
</head>

<body class="font-sans antialiased bg-[#f4f5f7]">

    <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="ml-[320px] min-h-screen bg-[#f4f5f7]">

        <?php if(isset($header)): ?>
            <header class="bg-white border-b border-gray-200 shadow-sm px-10 py-6">
                <?php echo e($header); ?>

            </header>
        <?php endif; ?>

        <main class="p-10">
            <?php echo e($slot); ?>

        </main>

    </div>

</body>

</html><?php /**PATH C:\Users\victoriaco\sistema-contable\resources\views/layouts/app.blade.php ENDPATH**/ ?>