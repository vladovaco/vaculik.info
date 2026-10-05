<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4"><?= esc($title) ?></h1>

<form action="<?= $action ?>" method="post" class="card space-y-4">
    <?= csrf_field() ?>
    <div><label class="label" for="title">Názov</label><input class="input" id="title" name="title" value="<?= old('title', $payment->title) ?>" required placeholder="napr. Školné škôlka – október" autofocus></div>
    <div class="grid grid-cols-2 gap-3">
        <div><label class="label" for="amount">Suma (€)</label><input class="input" id="amount" name="amount" value="<?= old('amount', $payment->amount !== null ? number_format((float) $payment->amount, 2, ',', '') : '') ?>" inputmode="decimal" required placeholder="0,00"></div>
        <div><label class="label" for="due_at">Splatnosť</label><input class="input" type="date" id="due_at" name="due_at" value="<?= old('due_at', $payment->due_at?->format('Y-m-d')) ?>" required></div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="category">Kategória</label>
            <select class="input" id="category" name="category">
                <?php foreach (\Modules\Finance\Entities\Payment::CATEGORIES as $value => $label): ?>
                    <option value="<?= $value ?>" <?= old('category', $payment->category) === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label class="label" for="recurrence">Opakovanie</label>
            <select class="input" id="recurrence" name="recurrence">
                <?php foreach (\Modules\Finance\Entities\Payment::RECURRENCES as $value => $label): ?>
                    <option value="<?= $value ?>" <?= old('recurrence', $payment->recurrence) === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <div>
        <label class="label" for="person_id">Za koho</label>
        <select class="input" id="person_id" name="person_id">
            <option value="">Celá rodina</option>
            <?php foreach ($persons as $p): ?>
                <option value="<?= $p->id ?>" <?= (string) old('person_id', $payment->person_id) === (string) $p->id ? 'selected' : '' ?>><?= esc($p->displayName()) ?></option>
            <?php endforeach ?>
        </select>
    </div>

    <details class="rounded-xl border border-slate-200 dark:border-slate-700 p-3" <?= old('iban', $payment->iban) ? 'open' : '' ?>>
        <summary class="font-medium cursor-pointer">Bankové údaje (pre QR kód)</summary>
        <div class="space-y-3 mt-3">
            <div><label class="label" for="payee">Príjemca</label><input class="input" id="payee" name="payee" value="<?= old('payee', $payment->payee) ?>"></div>
            <div><label class="label" for="iban">IBAN</label><input class="input font-mono" id="iban" name="iban" value="<?= old('iban', $payment->iban) ?>" placeholder="SK31 1200 0000 1987 4263 7541" autocapitalize="characters"></div>
            <div class="grid grid-cols-3 gap-2">
                <div><label class="label" for="variable_symbol">VS</label><input class="input" id="variable_symbol" name="variable_symbol" value="<?= old('variable_symbol', $payment->variable_symbol) ?>" inputmode="numeric"></div>
                <div><label class="label" for="specific_symbol">ŠS</label><input class="input" id="specific_symbol" name="specific_symbol" value="<?= old('specific_symbol', $payment->specific_symbol) ?>" inputmode="numeric"></div>
                <div><label class="label" for="constant_symbol">KS</label><input class="input" id="constant_symbol" name="constant_symbol" value="<?= old('constant_symbol', $payment->constant_symbol) ?>" inputmode="numeric"></div>
            </div>
        </div>
    </details>

    <div><label class="label" for="note">Poznámka</label><textarea class="input" id="note" name="note" rows="2"><?= old('note', $payment->note) ?></textarea></div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn-primary flex-1">Uložiť</button>
        <a href="<?= $payment->id ? url_to('payments.show', $payment->id) : url_to('finance') ?>" class="btn-ghost">Zrušiť</a>
    </div>
</form>
<?= $this->endSection() ?>
