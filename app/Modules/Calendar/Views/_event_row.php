<?php
/** @var \Modules\Calendar\Entities\CalendarEvent $event */
$person = $event->person_id && isset($persons[$event->person_id]) ? $persons[$event->person_id] : null;
$driver = $event->driver_person_id && isset($persons[$event->driver_person_id]) ? $persons[$event->driver_person_id] : null;
$color  = $person?->color ?? ($event->source_id && isset($sources[$event->source_id]) ? $sources[$event->source_id]->color : '#64748b');
?>
<li>
    <a href="<?= url_to('events.show', $event->id) ?>" class="flex items-stretch gap-3 p-3">
        <span class="w-1 rounded-full shrink-0" style="background: <?= esc($color, 'attr') ?>"></span>
        <span class="w-16 shrink-0 text-sm text-slate-500 pt-0.5"><?= $event->all_day ? 'celý deň' : $event->starts_at->format('H:i') ?></span>
        <span class="flex-1 min-w-0">
            <span class="block font-medium truncate"><?= esc($event->title) ?></span>
            <span class="block text-sm text-slate-500 truncate">
                <?= $person ? esc($person->displayName()) : '' ?><?= $event->location ? ($person ? ' · ' : '') . esc($event->location) : '' ?><?= $driver ? ' · vezie ' . esc($driver->displayName()) : '' ?>
            </span>
        </span>
    </a>
</li>
