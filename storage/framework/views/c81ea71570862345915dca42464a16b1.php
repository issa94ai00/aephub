<?php
    $locale = app()->getLocale();
    $isEn = $locale === 'en';
    $htmlLang = str_replace('_', '-', $locale);
    $htmlDir = $isEn ? 'ltr' : 'rtl';
?>
<!DOCTYPE html>
<html lang="<?php echo e($htmlLang); ?>" dir="<?php echo e($htmlDir); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title inertia><?php echo e(__('admin.layout.default_title')); ?> — <?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <?php if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))): ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/admin-spa.js']); ?>
    <?php else: ?>
        <style>
            body { font-family: "Instrument Sans", system-ui, sans-serif; background: #0f1412; color: #e7eee9; }
        </style>
    <?php endif; ?>
    <?php $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>
<body data-admin-shell data-admin-layout-dir="<?php echo e($htmlDir); ?>" class="admin-shell min-h-screen text-[#e7eee9] antialiased">
    <?php $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><script data-page="app" type="application/json"><?php echo json_encode($page); ?></script><div id="app"></div><?php } ?>
</body>
</html>
<?php /**PATH /var/www/aephub.com/html/resources/views/admin-app.blade.php ENDPATH**/ ?>