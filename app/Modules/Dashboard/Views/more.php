<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4">Viac</h1>

<?php
$modules = [
    ['label' => 'Osoby', 'icon' => 'users', 'url' => url_to('persons'), 'ready' => true],
    ['label' => 'Škola', 'icon' => 'document', 'url' => '#', 'ready' => false],
    ['label' => 'Rozvrhy', 'icon' => 'calendar', 'url' => '#', 'ready' => false],
    ['label' => 'Zmluvy', 'icon' => 'document', 'url' => '#', 'ready' => false],
    ['label' => 'Úlohy', 'icon' => 'check', 'url' => '#', 'ready' => false],
    ['label' => 'Rutiny', 'icon' => 'check', 'url' => '#', 'ready' => false],
    ['label' => 'Krúžky', 'icon' => 'calendar', 'url' => '#', 'ready' => false],
    ['label' => 'Zásoby', 'icon' => 'grid', 'url' => '#', 'ready' => false],
    ['label' => 'Zariadenia', 'icon' => 'grid', 'url' => '#', 'ready' => false],
    ['label' => 'Jedálniček', 'icon' => 'grid', 'url' => '#', 'ready' => false],
    ['label' => 'Lieky', 'icon' => 'alert', 'url' => '#', 'ready' => false],
    ['label' => 'Lekári', 'icon' => 'users', 'url' => '#', 'ready' => false],
    ['label' => 'Kontakty', 'icon' => 'users', 'url' => '#', 'ready' => false],
    ['label' => 'Asistent', 'icon' => 'info', 'url' => '#', 'ready' => false],
];
?>
<div class="grid grid-cols-3 gap-3">
    <?php foreach ($modules as $m): ?>
        <a href="<?= $m['url'] ?>" class="card flex flex-col items-center gap-2 py-4 text-center <?= $m['ready'] ? '' : 'opacity-40 pointer-events-none' ?>">
            <?= nav_icon($m['icon'], 'h-7 w-7') ?>
            <span class="text-sm font-medium"><?= esc($m['label']) ?></span>
        </a>
    <?php endforeach ?>
</div>

<div class="mt-8 card">
    <div class="flex items-center justify-between">
        <div>
            <div class="font-medium"><?= esc(auth()->user()?->email ?? '') ?></div>
            <div class="text-sm text-slate-500">Rola: <?= esc(implode(', ', auth()->user()?->getGroups() ?? [])) ?></div>
        </div>
        <a href="<?= url_to('logout') ?>" class="btn-ghost"><?= nav_icon('logout', 'h-5 w-5') ?> Odhlásiť</a>
    </div>
</div>
<?= $this->endSection() ?>
