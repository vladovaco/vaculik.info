<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php $person = $event->person_id && isset($persons[$event->person_id]) ? $persons[$event->person_id] : null; $driver = $event->driver_person_id && isset($persons[$event->driver_person_id]) ? $persons[$event->driver_person_id] : null; ?>
<div class="mb-4">
    <p class="text-sm text-slate-500 capitalize"><?= esc(sk_date($event->starts_at, 'EEEE d. MMMM yyyy')) ?><?= $event->isMultiDay() ? ' – ' . esc(sk_date($event->all_day ? $event->ends_at->subMinutes(1) : $event->ends_at, 'd. MMMM yyyy')) : '' ?> · <?= esc($event->timeLabel()) ?></p>
    <h1 class="text-xl font-semibold"><?= esc($event->title) ?></h1>
</div>

<dl class="card divide-y divide-slate-200 dark:divide-slate-700 p-0 mb-4">
    <?php $rows = [
        ['Kto', $person?->displayName() ?? 'Celá rodina', null],
        ['Vezie / sprevádza', $driver?->displayName(), null],
        ['Miesto', $event->location, $event->location ? 'https://maps.google.com/?q=' . urlencode($event->location) : null],
        ['Popis', $event->description, null],
        ['Zdroj', $source?->name, null],
    ]; ?>
    <?php foreach ($rows as [$label, $value, $href]): if (! $value) continue; ?>
        <div class="p-3">
            <dt class="text-xs uppercase tracking-wide text-slate-500"><?= $label ?></dt>
            <dd class="mt-0.5 whitespace-pre-line break-words"><?php if ($href): ?><a class="text-blue-600" href="<?= esc($href, 'attr') ?>" target="_blank" rel="noopener"><?= esc($value) ?></a><?php else: ?><?= esc($value) ?><?php endif ?></dd>
        </div>
    <?php endforeach ?>
</dl>

<?php if (! $event->isExternal() && auth()->user()?->can('calendar.manage')): ?>
    <div class="flex gap-2">
        <a href="<?= url_to('events.edit', $event->id) ?>" class="btn-ghost border border-slate-300 dark:border-slate-600 flex-1">Upraviť</a>
        <form action="<?= url_to('events.delete', $event->id) ?>" method="post" onsubmit="return confirm('Odstrániť udalosť?')">
            <?= csrf_field() ?>
            <button class="btn-ghost text-red-600"><?= nav_icon('trash', 'h-5 w-5') ?></button>
        </form>
    </div>
<?php elseif ($event->isExternal()): ?>
    <p class="text-xs text-slate-500 text-center">Udalosť pochádza z externého kalendára a upravuje sa tam.</p>
<?php endif ?>
<?= $this->endSection() ?>
