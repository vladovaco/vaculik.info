<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4"><?= esc($title) ?></h1>

<form action="<?= $action ?>" method="post" class="card space-y-4">
    <?= csrf_field() ?>
    <div><label class="label" for="name">Meno / názov</label><input class="input" id="name" name="name" value="<?= old('name', $contact->name) ?>" required autofocus></div>
    <div><label class="label" for="organization">Organizácia</label><input class="input" id="organization" name="organization" value="<?= old('organization', $contact->organization) ?>" placeholder="napr. ZŠ Hviezdoslavova, Autoservis Novák"></div>
    <div>
        <label class="label" for="kind">Druh</label>
        <select class="input" id="kind" name="kind">
            <?php foreach (\Modules\Contacts\Entities\Contact::KINDS as $value => $label): ?>
                <option value="<?= $value ?>" <?= old('kind', $contact->kind) === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div>
        <label class="label" for="person_id">Týka sa osoby <span class="text-slate-400">(voliteľné)</span></label>
        <select class="input" id="person_id" name="person_id">
            <option value="">—</option>
            <?php foreach ($persons as $p): ?>
                <option value="<?= $p->id ?>" <?= (string) old('person_id', $contact->person_id) === (string) $p->id ? 'selected' : '' ?>><?= esc($p->displayName()) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div><label class="label" for="phone">Telefón</label><input class="input" type="tel" id="phone" name="phone" value="<?= old('phone', $contact->phone) ?>" inputmode="tel"></div>
        <div><label class="label" for="phone2">Telefón 2</label><input class="input" type="tel" id="phone2" name="phone2" value="<?= old('phone2', $contact->phone2) ?>" inputmode="tel"></div>
    </div>
    <div><label class="label" for="email">E-mail</label><input class="input" type="email" id="email" name="email" value="<?= old('email', $contact->email) ?>" inputmode="email"></div>
    <div><label class="label" for="address">Adresa</label><input class="input" id="address" name="address" value="<?= old('address', $contact->address) ?>"></div>
    <div><label class="label" for="web">Web</label><input class="input" id="web" name="web" value="<?= old('web', $contact->web) ?>" inputmode="url"></div>
    <div><label class="label" for="note">Poznámka</label><textarea class="input" id="note" name="note" rows="3"><?= old('note', $contact->note) ?></textarea></div>
    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn-primary flex-1">Uložiť</button>
        <a href="<?= $contact->id ? url_to('contacts.show', $contact->id) : url_to('contacts') ?>" class="btn-ghost">Zrušiť</a>
    </div>
</form>
<?= $this->endSection() ?>
