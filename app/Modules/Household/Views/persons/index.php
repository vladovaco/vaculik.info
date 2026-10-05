<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Osoby</h1>
    <?php if (auth()->user()?->can('household.manage')): ?>
        <a href="<?= url_to('persons.new') ?>" class="btn-primary"><?= nav_icon('plus', 'h-5 w-5') ?> Pridať</a>
    <?php endif ?>
</div>

<?php if ($persons === []): ?>
    <div class="card text-center text-slate-500">
        Zatiaľ tu nikto nie je. Začnite dospelými, potom pridajte deti.
    </div>
<?php else: ?>
    <ul class="divide-y divide-slate-200 dark:divide-slate-700 card p-0">
        <?php foreach ($persons as $person): ?>
            <li class="flex items-center gap-3 p-3">
                <span class="avatar" style="background: <?= esc($person->color, 'attr') ?>"><?= esc($person->initials()) ?></span>
                <div class="flex-1 min-w-0">
                    <div class="font-medium truncate"><?= esc($person->fullName()) ?><?php if ($person->nickname): ?> <span class="text-slate-500">(<?= esc($person->nickname) ?>)</span><?php endif ?></div>
                    <div class="text-sm text-slate-500"><?= esc($person->roleLabel()) ?><?php if ($person->age() !== null): ?> · <?= $person->age() ?> r.<?php endif ?></div>
                </div>
                <?php if (auth()->user()?->can('household.manage')): ?>
                    <a href="<?= url_to('persons.edit', $person->id) ?>" class="btn-ghost">Upraviť</a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<?= $this->endSection() ?>
