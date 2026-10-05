<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-3">
    <h1 class="text-xl font-semibold">Kalendár</h1>
    <div class="flex items-center gap-1">
        <a href="<?= url_to('calendar.month', (int) $from->getYear(), (int) $from->getMonth()) ?><?= $personId ? '?osoba=' . $personId : '' ?>" class="btn-ghost" aria-label="Mesiac"><?= nav_icon('calendar', 'h-5 w-5') ?></a>
        <?php if (auth()->user()?->can('calendar.manage')): ?>
            <a href="<?= url_to('events.new') ?>?den=<?= $from->format('Y-m-d') ?>" class="btn-primary"><?= nav_icon('plus', 'h-5 w-5') ?> Udalosť</a>
        <?php endif ?>
    </div>
</div>

<?= view('Modules\Calendar\Views\_person_filter', ['persons' => $persons, 'personId' => $personId, 'baseUrl' => url_to('calendar') . '?od=' . $from->format('Y-m-d')]) ?>

<div class="flex items-center justify-between mb-3" hx-boost="false">
    <a href="<?= url_to('calendar') ?>?od=<?= $from->subDays(14)->format('Y-m-d') ?><?= $personId ? '&osoba=' . $personId : '' ?>" class="icon-btn" aria-label="Predchádzajúce"><?= nav_icon('chevron-left') ?></a>
    <a href="<?= url_to('calendar') ?><?= $personId ? '?osoba=' . $personId : '' ?>" class="text-sm font-medium"><?= same_day($from, $today) ? 'Nasledujúce 2 týždne' : esc(sk_date($from, 'd. M.')) . ' – ' . esc(sk_date($to->subDays(1), 'd. M.')) ?></a>
    <a href="<?= url_to('calendar') ?>?od=<?= $from->addDays(14)->format('Y-m-d') ?><?= $personId ? '&osoba=' . $personId : '' ?>" class="icon-btn" aria-label="Ďalšie"><?= nav_icon('chevron-right') ?></a>
</div>

<?php $any = false; ?>
<?php foreach ($byDay as $key => $events): if ($events === []) continue; $any = true; $day = \CodeIgniter\I18n\Time::parse($key); ?>
    <h2 class="day-heading mt-4 <?= same_day($day, $today) ? 'text-blue-600' : '' ?>"><?= same_day($day, $today) ? 'Dnes · ' : (same_day($day, $today->addDays(1)) ? 'Zajtra · ' : '') ?><?= esc(sk_date($day, 'EEEE d. M.')) ?></h2>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($events as $event) echo view('Modules\Calendar\Views\_event_row', ['event' => $event, 'persons' => $persons, 'sources' => $sources]); ?>
    </ul>
<?php endforeach ?>

<?php if (! $any): ?>
    <div class="card text-center text-slate-500">
        V tomto období nie sú žiadne udalosti.
        <?php if (auth()->user()?->can('calendar.manage')): ?><br><a href="<?= url_to('calendar.sources') ?>" class="text-blue-600 underline">Pripojte Google kalendár</a> alebo pridajte udalosť.<?php endif ?>
    </div>
<?php endif ?>

<?php if (auth()->user()?->can('calendar.manage')): ?>
    <p class="mt-6 text-center"><a href="<?= url_to('calendar.sources') ?>" class="text-sm text-slate-500 underline">Externé kalendáre (ICS)</a></p>
<?php endif ?>
<?= $this->endSection() ?>
