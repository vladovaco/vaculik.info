<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="flex items-center gap-3 mb-4">
    <span class="avatar h-14 w-14 text-lg bg-slate-500"><?= esc($contact->initials()) ?></span>
    <div class="min-w-0">
        <h1 class="text-xl font-semibold truncate"><?= esc($contact->name) ?></h1>
        <p class="text-sm text-slate-500"><?= esc($contact->kindLabel()) ?><?= $contact->organization ? ' · ' . esc($contact->organization) : '' ?><?= $person ? ' · ' . esc($person->displayName()) : '' ?></p>
    </div>
</div>

<div class="grid grid-cols-2 gap-2 mb-4">
    <?php if ($contact->phone): ?><a href="<?= $contact->telHref() ?>" class="btn-primary"><?= nav_icon('phone', 'h-5 w-5') ?> Zavolať</a><?php endif ?>
    <?php if ($contact->email): ?><a href="mailto:<?= esc($contact->email, 'attr') ?>" class="btn-ghost border border-slate-300 dark:border-slate-600">E-mail</a><?php endif ?>
</div>

<dl class="card divide-y divide-slate-200 dark:divide-slate-700 p-0">
    <?php $rows = [
        ['Telefón', $contact->phone, $contact->phone ? $contact->telHref() : null],
        ['Telefón 2', $contact->phone2, $contact->phone2 ? $contact->telHref($contact->phone2) : null],
        ['E-mail', $contact->email, $contact->email ? 'mailto:' . $contact->email : null],
        ['Adresa', $contact->address, $contact->address ? 'https://maps.google.com/?q=' . urlencode($contact->address) : null],
        ['Web', $contact->web, $contact->web ? (str_starts_with($contact->web, 'http') ? $contact->web : 'https://' . $contact->web) : null],
        ['Poznámka', $contact->note, null],
    ]; ?>
    <?php foreach ($rows as [$label, $value, $href]): if (! $value) continue; ?>
        <div class="p-3">
            <dt class="text-xs uppercase tracking-wide text-slate-500"><?= $label ?></dt>
            <dd class="mt-0.5 whitespace-pre-line"><?php if ($href): ?><a class="text-blue-600" href="<?= esc($href, 'attr') ?>"><?= esc($value) ?></a><?php else: ?><?= esc($value) ?><?php endif ?></dd>
        </div>
    <?php endforeach ?>
</dl>

<?php if (auth()->user()?->can('contacts.manage')): ?>
    <div class="flex gap-2 mt-4">
        <a href="<?= url_to('contacts.edit', $contact->id) ?>" class="btn-ghost border border-slate-300 dark:border-slate-600 flex-1">Upraviť</a>
        <form action="<?= url_to('contacts.delete', $contact->id) ?>" method="post" onsubmit="return confirm('Odstrániť kontakt?')" class="flex-1">
            <?= csrf_field() ?>
            <button class="btn-ghost text-red-600 w-full">Odstrániť</button>
        </form>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
