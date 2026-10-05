<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Kartičky poistencov</h1>
    <?php if (auth()->user()?->can('documents.manage')): ?>
        <a href="<?= url_to('documents.new') ?>?druh=karticka" class="btn-primary"><?= nav_icon('plus', 'h-5 w-5') ?> Pridať</a>
    <?php endif ?>
</div>

<?php if ($grouped === []): ?>
    <div class="card text-center text-slate-500">Zatiaľ žiadne kartičky. Odfoťte prednú a zadnú stranu každej kartičky a priraďte ju osobe.</div>
<?php endif ?>

<?php foreach ($grouped as $personId => $cards): $person = $persons[$personId] ?? null; ?>
    <section class="mb-6">
        <h2 class="flex items-center gap-2 font-semibold mb-2">
            <?php if ($person): ?><span class="avatar h-8 w-8 text-xs" style="background: <?= esc($person->color, 'attr') ?>"><?= esc($person->initials()) ?></span><?= esc($person->fullName()) ?><?php else: ?>Nepriradené<?php endif ?>
        </h2>
        <?php foreach ($cards as $card): ?>
            <div class="card mb-3">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="font-medium"><?= esc($card->metaValue('insurer') ?: $card->title) ?></div>
                        <?php if ($card->metaValue('number')): ?><div class="text-lg tracking-widest font-mono select-all"><?= esc($card->metaValue('number')) ?></div><?php endif ?>
                    </div>
                    <a href="<?= url_to('documents.show', $card->id) ?>" class="btn-ghost">Detail</a>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($card->files as $file): if (! $file->isImage()) continue; ?>
                        <a href="<?= url_to('documents.file', $card->id, $file->id) ?>" target="_blank">
                            <img src="<?= url_to('documents.file', $card->id, $file->id) ?>" alt="<?= esc($file->original_name, 'attr') ?>" class="w-full rounded-lg object-cover aspect-[1.6] bg-slate-200">
                        </a>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endforeach ?>
    </section>
<?php endforeach ?>

<p class="text-xs text-slate-500 text-center">Po prvom otvorení zostáva táto stránka aj s obrázkami uložená v telefóne pre použitie bez signálu.</p>
<?= $this->endSection() ?>
