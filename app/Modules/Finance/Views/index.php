<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Peniaze</h1>
    <a href="<?= url_to('payments.new') ?>" class="btn-primary"><?= nav_icon('plus', 'h-5 w-5') ?> Platba</a>
</div>

<nav class="tabs" hx-boost="false">
    <a href="<?= url_to('finance') ?>" class="tab <?= $tab === 'cakajuce' ? 'tab-active' : '' ?>">Čakajúce</a>
    <a href="<?= url_to('finance') ?>?zalozka=zaplatene" class="tab <?= $tab === 'zaplatene' ? 'tab-active' : '' ?>">Zaplatené</a>
</nav>

<?php
$row = static function ($p) use ($persons, $today): string {
    ob_start(); ?>
    <li>
        <a href="<?= url_to('payments.show', $p->id) ?>" class="flex items-center gap-3 p-3">
            <span class="flex-1 min-w-0">
                <span class="block font-medium truncate"><?= esc($p->title) ?></span>
                <span class="block text-sm text-slate-500 truncate">
                    <?= esc($p->categoryLabel()) ?><?= $p->person_id && isset($persons[$p->person_id]) ? ' · ' . esc($persons[$p->person_id]->displayName()) : '' ?><?= $p->isRecurring() ? ' · ' . mb_strtolower($p->recurrenceLabel()) : '' ?>
                </span>
            </span>
            <span class="text-right shrink-0">
                <span class="block font-semibold"><?= $p->amountFormatted() ?></span>
                <span class="badge badge-<?= $p->statusBadge($today) ?>"><?= esc($p->statusLabel($today)) ?></span>
            </span>
        </a>
    </li>
    <?php return (string) ob_get_clean();
};
?>

<?php if ($tab === 'cakajuce'): ?>
    <?php if ($groups['overdue'] === [] && $groups['week'] === [] && $groups['later'] === []): ?>
        <div class="card text-center text-slate-500">Žiadne čakajúce platby. Pridajte školné, obedy a krúžky ako opakované platby.</div>
    <?php endif ?>
    <?php foreach ([['overdue', 'Po splatnosti', 'text-red-600'], ['week', 'Do 7 dní', 'text-amber-600'], ['later', 'Neskôr', 'text-slate-500']] as [$key, $label, $cls]): if ($groups[$key] === []) continue; ?>
        <div class="flex items-baseline justify-between mb-1 mt-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide <?= $cls ?>"><?= $label ?></h2>
            <span class="text-sm font-semibold"><?= number_format($sums[$key], 2, ',', ' ') ?> €</span>
        </div>
        <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($groups[$key] as $p) echo $row($p); ?>
        </ul>
    <?php endforeach ?>
<?php else: ?>
    <?php if ($paid === []): ?>
        <div class="card text-center text-slate-500">Za posledných 90 dní nič zaplatené.</div>
    <?php else: ?>
        <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($paid as $p) echo $row($p); ?>
        </ul>
    <?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
