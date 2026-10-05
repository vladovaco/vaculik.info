<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Upozornenia</h1>
    <a href="<?= url_to('notifications.settings') ?>" class="btn-ghost">Nastavenia</a>
</div>

<?php if ($notifications === []): ?>
    <div class="card text-center text-slate-500">Zatiaľ žiadne upozornenia. Pribudnú pred splatnosťami a končiacimi dokumentmi.</div>
<?php else: ?>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($notifications as $n): ?>
            <li>
                <a href="<?= url_to('notifications.open', $n->id) ?>" class="flex items-start gap-3 p-3 <?= $n->isRead() ? '' : 'bg-blue-50 dark:bg-slate-700/40' ?>">
                    <span class="mt-1 h-2.5 w-2.5 rounded-full shrink-0 <?= $n->level === 'danger' ? 'bg-red-500' : ($n->level === 'warning' ? 'bg-amber-500' : 'bg-blue-500') ?>"></span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-medium"><?= esc($n->title) ?></span>
                        <?php if ($n->body): ?><span class="block text-sm text-slate-500 whitespace-pre-line"><?= esc($n->body) ?></span><?php endif ?>
                        <span class="block text-xs text-slate-400 mt-1"><?= $n->created_at?->format('j.n.Y H:i') ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<?= $this->endSection() ?>
