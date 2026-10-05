<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4"><?= esc($title) ?></h1>

<form action="<?= $action ?>" method="post" enctype="multipart/form-data" class="card space-y-4" x-data="{ kind: '<?= esc(old('kind', $document->kind), 'js') ?>' }">
    <?= csrf_field() ?>

    <div>
        <label class="label" for="files">Fotky alebo PDF <span class="text-slate-400">(viac naraz)</span></label>
        <input class="block w-full text-sm" type="file" id="files" name="files[]" multiple accept="image/*,application/pdf" capture="environment">
        <p class="text-xs text-slate-500 mt-1">Max. 12 MB na súbor. Fotky sa automaticky zmenšia.</p>
    </div>

    <div>
        <label class="label" for="kind">Druh</label>
        <select class="input" id="kind" name="kind" x-model="kind">
            <?php foreach (\Modules\Documents\Entities\Document::KINDS as $value => $label): ?>
                <option value="<?= $value ?>"><?= $label ?></option>
            <?php endforeach ?>
        </select>
    </div>

    <div><label class="label" for="title">Názov</label><input class="input" id="title" name="title" value="<?= old('title', $document->title) ?>" required placeholder="napr. Zmluva škôlka Lienka 2026/27"></div>

    <div>
        <label class="label" for="person_id">Osoba</label>
        <select class="input" id="person_id" name="person_id">
            <option value="">Celá rodina</option>
            <?php foreach ($persons as $p): ?>
                <option value="<?= $p->id ?>" <?= (string) old('person_id', $document->person_id) === (string) $p->id ? 'selected' : '' ?>><?= esc($p->displayName()) ?></option>
            <?php endforeach ?>
        </select>
    </div>

    <div x-show="kind === 'karticka'" x-cloak class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="meta_insurer">Poisťovňa</label>
            <select class="input" id="meta_insurer" name="meta_insurer">
                <?php foreach (\Modules\Documents\Entities\Document::INSURERS as $i): ?>
                    <option <?= old('meta_insurer', $document->metaValue('insurer')) === $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div><label class="label" for="meta_number">Číslo poistenca</label><input class="input" id="meta_number" name="meta_number" value="<?= old('meta_number', $document->metaValue('number')) ?>" inputmode="numeric"></div>
    </div>
    <div x-show="kind === 'zmluva'" x-cloak class="grid grid-cols-2 gap-3">
        <div><label class="label" for="meta_counterparty">Druhá strana</label><input class="input" id="meta_counterparty" name="meta_counterparty" value="<?= old('meta_counterparty', $document->metaValue('counterparty')) ?>"></div>
        <div><label class="label" for="meta_notice_period">Výpovedná lehota</label><input class="input" id="meta_notice_period" name="meta_notice_period" value="<?= old('meta_notice_period', $document->metaValue('notice_period')) ?>" placeholder="napr. 2 mesiace"></div>
    </div>
    <div x-show="kind === 'blocek' || kind === 'zarucny_list'" x-cloak class="grid grid-cols-2 gap-3">
        <div><label class="label" for="meta_vendor">Predajca</label><input class="input" id="meta_vendor" name="meta_vendor" value="<?= old('meta_vendor', $document->metaValue('vendor')) ?>"></div>
        <div><label class="label" for="meta_amount">Suma</label><input class="input" id="meta_amount" name="meta_amount" value="<?= old('meta_amount', $document->metaValue('amount')) ?>" inputmode="decimal" placeholder="€"></div>
    </div>

    <div>
        <label class="label" for="expires_at">Platí do <span class="text-slate-400">(záruka, zmluva, kartička…)</span></label>
        <input class="input" type="date" id="expires_at" name="expires_at" value="<?= old('expires_at', $document->expires_at?->format('Y-m-d')) ?>">
    </div>

    <div><label class="label" for="note">Poznámka</label><textarea class="input" id="note" name="note" rows="3"><?= old('note', $document->note) ?></textarea></div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn-primary flex-1">Uložiť</button>
        <a href="<?= $document->id ? url_to('documents.show', $document->id) : url_to('documents') ?>" class="btn-ghost">Zrušiť</a>
    </div>
</form>

<?php if ($document->id && $document->files !== []): ?>
    <h2 class="font-semibold mt-6 mb-2">Súbory</h2>
    <ul class="card p-0 divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($document->files as $file): ?>
            <li class="flex items-center gap-3 p-3">
                <?php if ($file->isImage()): ?><img src="<?= url_to('documents.thumb', $document->id, $file->id) ?>" alt="" class="h-10 w-10 rounded object-cover"><?php else: ?><?= nav_icon('document') ?><?php endif ?>
                <span class="flex-1 text-sm truncate"><?= esc($file->original_name) ?></span>
                <form action="<?= url_to('documents.file.delete', $document->id, $file->id) ?>" method="post" onsubmit="return confirm('Odstrániť súbor?')">
                    <?= csrf_field() ?>
                    <button class="icon-btn text-red-600" aria-label="Odstrániť"><?= nav_icon('trash', 'h-5 w-5') ?></button>
                </form>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<?= $this->endSection() ?>
