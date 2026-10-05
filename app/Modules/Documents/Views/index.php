<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Dokumenty</h1>
    <?php if (auth()->user()?->can('documents.manage')): ?>
        <a href="<?= url_to('documents.new') ?>" class="btn-primary"><?= nav_icon('camera', 'h-5 w-5') ?> Pridať</a>
    <?php endif ?>
</div>

<a href="<?= url_to('documents.cards') ?>" class="card card-info flex items-center gap-3 mb-4">
    <?= nav_icon('card', 'h-7 w-7 text-blue-600') ?>
    <span class="flex-1"><span class="block font-medium">Kartičky poistencov</span><span class="block text-sm text-slate-500">Dostupné aj offline v čakárni</span></span>
    <?= nav_icon('chevron-right', 'h-5 w-5 text-slate-400') ?>
</a>

<form method="get" class="mb-4 space-y-2" hx-boost="false">
    <input class="input" type="search" name="q" value="<?= esc($query) ?>" placeholder="Hľadať v názvoch a poznámkach…" autocomplete="off">
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4">
        <a href="<?= url_to('documents') ?>" class="chip <?= $kind === '' && ! $expiring ? 'chip-active' : '' ?>">Všetky</a>
        <a href="<?= url_to('documents') ?>?expiruje=1" class="chip <?= $expiring ? 'chip-active' : '' ?>">Končí platnosť</a>
        <?php foreach (\Modules\Documents\Entities\Document::KINDS as $value => $label): ?>
            <a href="<?= url_to('documents') ?>?druh=<?= $value ?>" class="chip <?= $kind === $value ? 'chip-active' : '' ?>"><?= $label ?></a>
        <?php endforeach ?>
    </div>
    <?php if (count($persons) > 1): ?>
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4">
            <?php foreach ($persons as $p): ?>
                <a href="<?= url_to('documents') ?>?osoba=<?= $p->id ?>&druh=<?= $kind ?>" class="chip <?= $personId === $p->id ? 'chip-active' : '' ?>"><?= esc($p->displayName()) ?></a>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</form>

<?php if ($documents === []): ?>
    <div class="card text-center text-slate-500">Žiadne dokumenty. Odfoťte zmluvu so škôlkou alebo záručný list na práčku.</div>
<?php else: ?>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($documents as $d): ?>
            <li>
                <a href="<?= url_to('documents.show', $d->id) ?>" class="flex items-center gap-3 p-3">
                    <?php $img = $d->firstImage(); ?>
                    <?php if ($img): ?>
                        <img src="<?= url_to('documents.thumb', $d->id, $img->id) ?>" alt="" class="h-12 w-12 rounded-lg object-cover bg-slate-200" loading="lazy">
                    <?php else: ?>
                        <span class="h-12 w-12 rounded-lg bg-slate-200 dark:bg-slate-700 inline-flex items-center justify-center text-slate-500"><?= nav_icon('document') ?></span>
                    <?php endif ?>
                    <span class="flex-1 min-w-0">
                        <span class="block font-medium truncate"><?= esc($d->title) ?></span>
                        <span class="block text-sm text-slate-500 truncate"><?= esc($d->kindLabel()) ?><?= $d->person_id && isset($persons[$d->person_id]) ? ' · ' . esc($persons[$d->person_id]->displayName()) : '' ?></span>
                    </span>
                    <?php if ($badge = $d->expiryBadge()): ?>
                        <span class="badge badge-<?= $badge ?>"><?= $d->daysToExpiry() < 0 ? 'po platnosti' : $d->expires_at->format('j.n.Y') ?></span>
                    <?php endif ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<?= $this->endSection() ?>
