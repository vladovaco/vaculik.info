<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4"><?= esc($title) ?></h1>

<form action="<?= $action ?>" method="post" class="card space-y-4">
    <?= csrf_field() ?>

    <div>
        <label class="label" for="first_name">Meno</label>
        <input class="input" id="first_name" name="first_name" value="<?= old('first_name', $person->first_name) ?>" required autocomplete="given-name">
    </div>
    <div>
        <label class="label" for="last_name">Priezvisko</label>
        <input class="input" id="last_name" name="last_name" value="<?= old('last_name', $person->last_name) ?>" autocomplete="family-name">
    </div>
    <div>
        <label class="label" for="nickname">Prezývka <span class="text-slate-400">(zobrazuje sa v appke)</span></label>
        <input class="input" id="nickname" name="nickname" value="<?= old('nickname', $person->nickname) ?>">
    </div>
    <div>
        <span class="label">Rola</span>
        <div class="grid grid-cols-3 gap-2">
            <?php foreach (\Modules\Household\Entities\Person::ROLES as $value => $label): ?>
                <label class="choice">
                    <input type="radio" name="role" value="<?= $value ?>" <?= old('role', $person->role) === $value ? 'checked' : '' ?> class="peer sr-only">
                    <span class="choice-box"><?= $label ?></span>
                </label>
            <?php endforeach ?>
        </div>
    </div>
    <div>
        <label class="label" for="birth_date">Dátum narodenia</label>
        <input class="input" type="date" id="birth_date" name="birth_date" value="<?= old('birth_date', $person->birth_date?->format('Y-m-d')) ?>">
    </div>
    <div class="flex items-center gap-4">
        <div>
            <label class="label" for="color">Farba</label>
            <input type="color" id="color" name="color" value="<?= old('color', $person->color ?? '#2563eb') ?>" class="h-12 w-16 rounded-lg border border-slate-300 bg-transparent">
        </div>
        <div class="flex-1">
            <label class="label" for="sort_order">Poradie</label>
            <input class="input" type="number" id="sort_order" name="sort_order" value="<?= old('sort_order', $person->sort_order ?? 100) ?>" inputmode="numeric">
        </div>
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn-primary flex-1">Uložiť</button>
        <a href="<?= url_to('persons') ?>" class="btn-ghost">Zrušiť</a>
    </div>
</form>

<?php if ($person->id): ?>
    <form action="<?= url_to('persons.delete', $person->id) ?>" method="post" class="mt-6 text-center" onsubmit="return confirm('Naozaj odstrániť osobu <?= esc($person->displayName(), 'js') ?>?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn-ghost text-red-600">Odstrániť osobu</button>
    </form>
<?php endif ?>
<?= $this->endSection() ?>
