<!doctype html>
<html lang="sk" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= isset($title) ? esc($title) . ' · ' : '' ?>vaculik.info</title>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="icon" href="<?= base_url('assets/icons/icon.svg') ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/icon-192.png') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= filemtime(FCPATH . 'assets/app.css') ?>">
    <script defer src="<?= base_url('assets/vendor/htmx.min.js') ?>"></script>
    <script defer src="<?= base_url('assets/vendor/alpine.min.js') ?>"></script>
    <?= $this->renderSection('head') ?>
</head>
<body class="h-full bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100" hx-boost="true">
<?php $user = auth()->user(); $nav = config('Family')->bottomNav; $current = service('router')->getMatchedRoute()[0] ?? ''; ?>

<div class="min-h-full flex flex-col">
    <header class="sticky top-0 z-20 bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-800 safe-top">
        <div class="mx-auto max-w-screen-md flex items-center justify-between px-4 h-14">
            <a href="<?= url_to('dashboard') ?>" class="font-semibold tracking-tight">vaculik<span class="text-blue-600">.info</span></a>
            <div class="flex items-center gap-1">
                <?php if (session('message')): ?><span class="sr-only"><?= esc(session('message')) ?></span><?php endif ?>
                <a href="#" class="icon-btn" aria-label="Notifikácie"><?= nav_icon('bell') ?></a>
                <a href="<?= url_to('more') ?>" class="avatar h-8 w-8 text-xs" style="background: <?= esc(service('householdContext')->person()?->color ?? '#64748b', 'attr') ?>">
                    <?= esc(service('householdContext')->person()?->initials() ?? mb_strtoupper(mb_substr((string) ($user?->email ?? '?'), 0, 1))) ?>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 mx-auto w-full max-w-screen-md px-4 py-4 pb-24">
        <?php if (session('message')): ?>
            <div class="flash flash-ok" x-data x-init="setTimeout(() => $el.remove(), 4000)"><?= esc(session('message')) ?></div>
        <?php endif ?>
        <?php if (session('error')): ?>
            <div class="flash flash-error"><?= esc(session('error')) ?></div>
        <?php endif ?>
        <?php if (session('errors')): ?>
            <div class="flash flash-error">
                <?php foreach ((array) session('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach ?>
            </div>
        <?php endif ?>

        <?= $this->renderSection('content') ?>
    </main>

    <nav class="fixed bottom-0 inset-x-0 z-20 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 safe-bottom" aria-label="Hlavná navigácia">
        <ul class="mx-auto max-w-screen-md grid grid-cols-5">
            <?php foreach ($nav as $item): ?>
                <?php $active = $current === $item['route'] || ($item['route'] === 'dashboard' && $current === '/'); ?>
                <li>
                    <a href="<?= url_to($item['route']) ?>" class="nav-item <?= $active ? 'nav-item-active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <?= nav_icon($item['icon']) ?>
                        <span><?= esc($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </nav>

    <a href="#" class="fab" aria-label="Rýchle vloženie" title="Rýchle vloženie (fáza 3)"><?= nav_icon('plus', 'h-7 w-7') ?></a>
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('<?= base_url('sw.js') ?>'));
    }
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
