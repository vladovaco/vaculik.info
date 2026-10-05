<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<header class="mb-5">
    <p class="text-sm text-slate-500 capitalize"><?= esc(sk_date($today)) ?></p>
    <h1 class="text-2xl font-semibold">
        <?php $hour = (int) $today->getHour(); ?>
        <?= $hour < 10 ? 'Dobré ráno' : ($hour < 18 ? 'Dobrý deň' : 'Dobrý večer') ?><?= $person ? ', ' . esc($person->displayName()) : '' ?>
    </h1>
</header>

<?php if ($cards === []): ?>
    <div class="card text-center">
        <?= nav_icon('check', 'h-10 w-10 mx-auto text-emerald-500 mb-2') ?>
        <p class="font-medium">Dnes nič nehorí.</p>
        <p class="text-sm text-slate-500">Karty sa tu objavia, keď moduly budú mať čo povedať.</p>
    </div>
<?php else: ?>
    <ul class="space-y-3">
        <?php foreach ($cards as $card): ?>
            <li>
                <a href="<?= $card->url ?? '#' ?>" class="card card-<?= $card->urgency ?> flex items-start gap-3">
                    <span class="shrink-0 mt-0.5"><?= nav_icon($card->icon, 'h-6 w-6') ?></span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-medium"><?= esc($card->title) ?></span>
                        <?php if ($card->body !== ''): ?><span class="block text-sm opacity-80"><?= esc($card->body) ?></span><?php endif ?>
                        <?php if ($card->actionLabel): ?><span class="inline-block mt-2 text-sm font-semibold underline"><?= esc($card->actionLabel) ?> →</span><?php endif ?>
                    </span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<?= $this->endSection() ?>
