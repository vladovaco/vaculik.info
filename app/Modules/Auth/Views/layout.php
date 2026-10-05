<!doctype html>
<html lang="sk" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title><?= $this->renderSection('title') ?> · vaculik.info</title>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="icon" href="<?= base_url('assets/icons/icon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= filemtime(FCPATH . 'assets/app.css') ?>">
</head>
<body class="min-h-full bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
    <main class="min-h-screen flex flex-col justify-center px-4 py-10 mx-auto w-full max-w-sm">
        <?= $this->renderSection('main') ?>
    </main>
</body>
</html>
