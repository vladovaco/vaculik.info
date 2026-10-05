<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Kontakty</h1>
    <?php if (auth()->user()?->can('contacts.manage')): ?>
        <a href="<?= url_to('contacts.new') ?>" class="btn-primary"><?= nav_icon('plus', 'h-5 w-5') ?> Pridať</a>
    <?php endif ?>
</div>

<form method="get" class="mb-4 space-y-2" hx-boost="false">
    <input class="input" type="search" name="q" value="<?= esc($query) ?>" placeholder="Hľadať meno, telefón, poznámku…" autocomplete="off">
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4">
        <a href="<?= url_to('contacts') ?>" class="chip <?= $kind === '' ? 'chip-active' : '' ?>">Všetky</a>
        <?php foreach (\Modules\Contacts\Entities\Contact::KINDS as $value => $label): ?>
            <a href="<?= url_to('contacts') ?>?druh=<?= $value ?>&q=<?= urlencode($query) ?>" class="chip <?= $kind === $value ? 'chip-active' : '' ?>"><?= $label ?></a>
        <?php endforeach ?>
    </div>
</form>

<?php if ($contacts === []): ?>
    <div class="card text-center text-slate-500">Žiadne kontakty. Začnite pediatrom, školou a servisom na auto.</div>
<?php else: ?>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($contacts as $c): ?>
            <li class="flex items-center gap-3 p-3">
                <a href="<?= url_to('contacts.show', $c->id) ?>" class="flex items-center gap-3 flex-1 min-w-0">
                    <span class="avatar bg-slate-500"><?= esc($c->initials()) ?></span>
                    <span class="min-w-0">
                        <span class="block font-medium truncate"><?= esc($c->name) ?></span>
                        <span class="block text-sm text-slate-500 truncate"><?= esc($c->kindLabel()) ?><?= $c->organization ? ' · ' . esc($c->organization) : '' ?></span>
                    </span>
                </a>
                <?php if ($c->phone): ?>
                    <a href="<?= $c->telHref() ?>" class="icon-btn text-blue-600" aria-label="Zavolať"><?= nav_icon('phone') ?></a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<?= $this->endSection() ?>
