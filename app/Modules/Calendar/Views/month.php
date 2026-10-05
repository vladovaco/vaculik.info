<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php $q = $personId ? '?osoba=' . $personId : ''; ?>
<div class="flex items-center justify-between mb-3" hx-boost="false">
    <a href="<?= url_to('calendar.month', (int) $first->subMonths(1)->getYear(), (int) $first->subMonths(1)->getMonth()) . $q ?>" class="icon-btn" aria-label="Predchádzajúci mesiac"><?= nav_icon('chevron-left') ?></a>
    <h1 class="text-lg font-semibold capitalize"><?= esc(sk_date($first, 'LLLL yyyy')) ?></h1>
    <a href="<?= url_to('calendar.month', (int) $first->addMonths(1)->getYear(), (int) $first->addMonths(1)->getMonth()) . $q ?>" class="icon-btn" aria-label="Ďalší mesiac"><?= nav_icon('chevron-right') ?></a>
</div>

<?= view('Modules\Calendar\Views\_person_filter', ['persons' => $persons, 'personId' => $personId, 'baseUrl' => url_to('calendar.month', (int) $first->getYear(), (int) $first->getMonth())]) ?>

<div class="grid grid-cols-7 text-center text-xs text-slate-500 mb-1">
    <?php foreach (['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'] as $d): ?><div><?= $d ?></div><?php endforeach ?>
</div>
<div class="grid grid-cols-7 gap-1" hx-boost="false">
    <?php for ($i = 0; $i < 42; $i++): $day = $gridFrom->addDays($i); $key = $day->format('Y-m-d'); $inMonth = (int) $day->getMonth() === (int) $first->getMonth(); $isToday = same_day($day, $today); ?>
        <a href="<?= url_to('calendar') ?>?od=<?= $key ?><?= $personId ? '&osoba=' . $personId : '' ?>" class="aspect-square rounded-lg flex flex-col items-center justify-start pt-1 text-sm <?= $inMonth ? 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700' : 'text-slate-400' ?> <?= $isToday ? 'ring-2 ring-blue-600 font-bold' : '' ?>">
            <span><?= $day->getDay() ?></span>
            <?php if (! empty($dots[$key])): ?>
                <span class="flex gap-0.5 mt-1 flex-wrap justify-center px-0.5">
                    <?php foreach (array_slice($dots[$key], 0, 4) as $e): $color = $e->person_id && isset($persons[$e->person_id]) ? $persons[$e->person_id]->color : ($e->source_id && isset($sources[$e->source_id]) ? $sources[$e->source_id]->color : '#64748b'); ?>
                        <span class="h-1.5 w-1.5 rounded-full" style="background: <?= esc($color, 'attr') ?>"></span>
                    <?php endforeach ?>
                </span>
            <?php endif ?>
        </a>
    <?php endfor ?>
</div>

<p class="mt-4 text-center"><a href="<?= url_to('calendar') . $q ?>" class="text-sm text-blue-600 underline">Späť na zoznam</a></p>
<?= $this->endSection() ?>
