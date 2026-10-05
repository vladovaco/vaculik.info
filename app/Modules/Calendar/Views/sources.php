<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-1">Externé kalendáre</h1>
<p class="text-sm text-slate-500 mb-4">Jednosmerný import z Google kalendára, školského kalendára či krúžku. Obnovuje sa každých 15 minút cez cron, alebo ručne.</p>

<?php if ($sources === []): ?>
    <div class="card text-center text-slate-500 mb-4">Zatiaľ žiadny externý kalendár.</div>
<?php else: ?>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700 mb-4">
        <?php foreach ($sources as $s): ?>
            <li class="p-3">
                <div class="flex items-center gap-3">
                    <span class="h-4 w-4 rounded-full shrink-0" style="background: <?= esc($s->color, 'attr') ?>"></span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-medium truncate"><?= esc($s->name) ?></span>
                        <span class="block text-xs text-slate-500 truncate">
                            <?php if ($s->last_error): ?><span class="text-red-600">Chyba: <?= esc($s->last_error) ?></span>
                            <?php elseif ($s->last_synced_at): ?>Naposledy <?= $s->last_synced_at->format('j.n. H:i') ?>
                            <?php else: ?>Ešte nesynchronizované<?php endif ?>
                        </span>
                    </span>
                    <form action="<?= url_to('calendar.sources.sync', $s->id) ?>" method="post"><?= csrf_field() ?><button class="icon-btn" aria-label="Synchronizovať"><?= nav_icon('refresh', 'h-5 w-5') ?></button></form>
                    <form action="<?= url_to('calendar.sources.delete', $s->id) ?>" method="post" onsubmit="return confirm('Odpojiť kalendár a zmazať jeho udalosti?')"><?= csrf_field() ?><button class="icon-btn text-red-600" aria-label="Odpojiť"><?= nav_icon('trash', 'h-5 w-5') ?></button></form>
                </div>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>

<form action="<?= url_to('calendar.sources.create') ?>" method="post" class="card space-y-3">
    <?= csrf_field() ?>
    <h2 class="font-semibold">Pridať kalendár</h2>
    <div><label class="label" for="name">Názov</label><input class="input" id="name" name="name" value="<?= old('name') ?>" required placeholder="napr. Google – Vlado"></div>
    <div><label class="label" for="ics_url">Adresa ICS</label><input class="input" id="ics_url" name="ics_url" value="<?= old('ics_url') ?>" required inputmode="url" placeholder="https://calendar.google.com/calendar/ical/…/basic.ics"></div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="person_id">Patrí osobe</label>
            <select class="input" id="person_id" name="person_id">
                <option value="">Celá rodina</option>
                <?php foreach ($persons as $p): ?><option value="<?= $p->id ?>"><?= esc($p->displayName()) ?></option><?php endforeach ?>
            </select>
        </div>
        <div><label class="label" for="color">Farba</label><input type="color" id="color" name="color" value="<?= old('color', '#0ea5e9') ?>" class="h-12 w-full rounded-lg border border-slate-300 bg-transparent"></div>
    </div>
    <button class="btn-primary w-full">Pridať a synchronizovať</button>
    <details class="text-sm text-slate-500">
        <summary class="cursor-pointer">Kde nájdem adresu v Google kalendári?</summary>
        <ol class="list-decimal pl-5 mt-2 space-y-1">
            <li>Google Kalendár na počítači → ozubené koliesko → Nastavenia.</li>
            <li>Vľavo vyberte kalendár → „Integrovať kalendár“.</li>
            <li>Skopírujte „Tajná adresa vo formáte iCal“. Nezdieľajte ju, dáva prístup na čítanie.</li>
        </ol>
    </details>
</form>
<?= $this->endSection() ?>
