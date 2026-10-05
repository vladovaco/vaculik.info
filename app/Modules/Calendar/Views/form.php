<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4"><?= esc($title) ?></h1>

<form action="<?= $action ?>" method="post" class="card space-y-4" x-data="{ allDay: <?= old('all_day', $event->all_day) ? 'true' : 'false' ?> }">
    <?= csrf_field() ?>
    <div><label class="label" for="title">Názov</label><input class="input" id="title" name="title" value="<?= old('title', $event->title) ?>" required autofocus placeholder="napr. Zubár Ema"></div>

    <label class="flex items-center gap-2"><input type="checkbox" name="all_day" value="1" x-model="allDay" class="h-5 w-5 rounded border-slate-300"> Celý deň</label>

    <div class="grid grid-cols-2 gap-3">
        <div><label class="label" for="date">Dátum</label><input class="input" type="date" id="date" name="date" value="<?= old('date', $event->starts_at?->format('Y-m-d')) ?>" required></div>
        <div x-show="!allDay"><label class="label" for="start_time">Od</label><input class="input" type="time" id="start_time" name="start_time" value="<?= old('start_time', $event->starts_at?->format('H:i')) ?>" step="300"></div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div><label class="label" for="end_date">Koniec (dátum)</label><input class="input" type="date" id="end_date" name="end_date" value="<?= old('end_date', $event->ends_at ? ($event->all_day ? $event->ends_at->subMinutes(1) : $event->ends_at)->format('Y-m-d') : '') ?>"></div>
        <div x-show="!allDay"><label class="label" for="end_time">Do</label><input class="input" type="time" id="end_time" name="end_time" value="<?= old('end_time', $event->ends_at?->format('H:i')) ?>" step="300"></div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="person_id">Kto</label>
            <select class="input" id="person_id" name="person_id">
                <option value="">Celá rodina</option>
                <?php foreach ($persons as $p): ?>
                    <option value="<?= $p->id ?>" <?= (string) old('person_id', $event->person_id) === (string) $p->id ? 'selected' : '' ?>><?= esc($p->displayName()) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label class="label" for="driver_person_id">Vezie / sprevádza</label>
            <select class="input" id="driver_person_id" name="driver_person_id">
                <option value="">—</option>
                <?php foreach ($persons as $p): if ($p->isChild()) continue; ?>
                    <option value="<?= $p->id ?>" <?= (string) old('driver_person_id', $event->driver_person_id) === (string) $p->id ? 'selected' : '' ?>><?= esc($p->displayName()) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div><label class="label" for="location">Miesto</label><input class="input" id="location" name="location" value="<?= old('location', $event->location) ?>"></div>
    <div><label class="label" for="description">Popis</label><textarea class="input" id="description" name="description" rows="3"><?= old('description', $event->description) ?></textarea></div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn-primary flex-1">Uložiť</button>
        <a href="<?= $event->id ? url_to('events.show', $event->id) : url_to('calendar') ?>" class="btn-ghost">Zrušiť</a>
    </div>
</form>
<?= $this->endSection() ?>
