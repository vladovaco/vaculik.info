<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-1">Termíny</h1>
<p class="text-sm text-slate-500 mb-4">Splatnosti, končiace zmluvy a doklady z celého systému na jednom mieste.</p>

<?php $empty = true; ?>
<?php foreach ([['overdue', 'Po termíne', 'text-red-600'], ['week', 'Tento týždeň', 'text-amber-600'], ['month', 'Do 30 dní', 'text-slate-600 dark:text-slate-300'], ['later', 'Neskôr', 'text-slate-500']] as [$key, $label, $cls]): if ($groups[$key] === []) continue; $empty = false; ?>
    <h2 class="text-sm font-semibold uppercase tracking-wide mt-4 mb-1 <?= $cls ?>"><?= $label ?></h2>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($groups[$key] as $d): $days = $d->daysLeft($today); ?>
            <li>
                <a href="<?= $d->url ?? '#' ?>" class="flex items-center gap-3 p-3">
                    <span class="text-slate-500"><?= nav_icon($d->icon, 'h-6 w-6') ?></span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-medium truncate"><?= esc($d->title) ?></span>
                        <span class="block text-sm text-slate-500 truncate"><?= esc($d->detail) ?><?= $d->personId && isset($persons[$d->personId]) ? ' · ' . esc($persons[$d->personId]->displayName()) : '' ?></span>
                    </span>
                    <span class="text-right shrink-0">
                        <span class="block text-sm"><?= $d->dueAt->format('j.n.Y') ?></span>
                        <span class="badge badge-<?= $days < 0 ? 'danger' : ($days <= 7 ? 'warning' : 'muted') ?>"><?= $days < 0 ? 'pred ' . abs($days) . ' d.' : ($days === 0 ? 'dnes' : ($days === 1 ? 'zajtra' : 'o ' . $days . ' d.')) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php endforeach ?>

<?php if ($empty): ?>
    <div class="card text-center text-slate-500">Nič nehorí. Termíny sem pribudnú z platieb a dokumentov s dátumom platnosti.</div>
<?php endif ?>
<?= $this->endSection() ?>
