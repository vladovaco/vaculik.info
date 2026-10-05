<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <p class="text-sm text-slate-500"><?= esc($document->kindLabel()) ?><?= $person ? ' · ' . esc($person->displayName()) : '' ?></p>
    <h1 class="text-xl font-semibold"><?= esc($document->title) ?></h1>
</div>

<?php if ($document->expires_at): ?>
    <div class="flash flash-<?= $document->expiryBadge() === 'danger' ? 'error' : 'ok' ?>">
        <?= $document->daysToExpiry() < 0 ? 'Platnosť skončila' : 'Platí do' ?> <?= sk_date($document->expires_at, 'd. MMMM yyyy') ?>
        <?php if ($document->daysToExpiry() >= 0): ?>(o <?= $document->daysToExpiry() ?> dní)<?php endif ?>
    </div>
<?php endif ?>

<?php if ($document->meta): ?>
    <dl class="card grid grid-cols-2 gap-2 mb-4 text-sm">
        <?php $labels = ['insurer' => 'Poisťovňa', 'number' => 'Číslo', 'counterparty' => 'Druhá strana', 'notice_period' => 'Výpovedná lehota', 'vendor' => 'Predajca', 'amount' => 'Suma']; ?>
        <?php foreach ($document->meta as $key => $value): ?>
            <dt class="text-slate-500"><?= esc($labels[$key] ?? $key) ?></dt><dd class="font-medium select-all"><?= esc((string) $value) ?></dd>
        <?php endforeach ?>
    </dl>
<?php endif ?>

<?php if ($document->note): ?><p class="card mb-4 whitespace-pre-line"><?= esc($document->note) ?></p><?php endif ?>

<?php if ($document->files === []): ?>
    <div class="card text-center text-slate-500 mb-4">Bez súborov.</div>
<?php else: ?>
    <div class="grid grid-cols-2 gap-2 mb-4">
        <?php foreach ($document->files as $file): ?>
            <a href="<?= url_to('documents.file', $document->id, $file->id) ?>" target="_blank" class="card p-2 flex flex-col gap-1">
                <?php if ($file->isImage()): ?>
                    <img src="<?= url_to('documents.thumb', $document->id, $file->id) ?>" alt="" class="w-full aspect-square object-cover rounded-lg bg-slate-200">
                <?php else: ?>
                    <span class="w-full aspect-square rounded-lg bg-slate-100 dark:bg-slate-700 inline-flex items-center justify-center text-slate-500"><?= nav_icon('document', 'h-10 w-10') ?></span>
                <?php endif ?>
                <span class="text-xs text-slate-500 truncate"><?= esc($file->original_name) ?> · <?= $file->humanSize() ?></span>
            </a>
        <?php endforeach ?>
    </div>
<?php endif ?>

<?php if (auth()->user()?->can('documents.manage')): ?>
    <div class="flex gap-2">
        <a href="<?= url_to('documents.edit', $document->id) ?>" class="btn-ghost border border-slate-300 dark:border-slate-600 flex-1">Upraviť / pridať súbor</a>
        <form action="<?= url_to('documents.delete', $document->id) ?>" method="post" onsubmit="return confirm('Odstrániť dokument aj so súbormi?')">
            <?= csrf_field() ?>
            <button class="btn-ghost text-red-600"><?= nav_icon('trash', 'h-5 w-5') ?></button>
        </form>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
