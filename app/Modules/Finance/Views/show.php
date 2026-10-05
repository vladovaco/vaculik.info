<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <p class="text-sm text-slate-500"><?= esc($payment->categoryLabel()) ?><?= $payment->person_id && isset($persons[$payment->person_id]) ? ' · ' . esc($persons[$payment->person_id]->displayName()) : '' ?><?= $payment->isRecurring() ? ' · ' . mb_strtolower($payment->recurrenceLabel()) : '' ?></p>
    <h1 class="text-xl font-semibold"><?= esc($payment->title) ?></h1>
</div>

<div class="card text-center mb-4">
    <div class="text-3xl font-bold"><?= $payment->amountFormatted() ?></div>
    <div class="mt-1"><span class="badge badge-<?= $payment->statusBadge() ?>">splatnosť <?= $payment->due_at->format('j.n.Y') ?> · <?= esc($payment->statusLabel()) ?></span></div>

    <?php if (! $payment->isPaid() && $hasQr): ?>
        <div class="mx-auto mt-4 w-56 max-w-full rounded-xl bg-white p-2">
            <img src="<?= url_to('payments.qr', $payment->id) ?>" alt="PAY by square QR kód" class="w-full">
        </div>
        <p class="text-xs text-slate-500 mt-2">Naskenujte v bankovej aplikácii (PAY by square).</p>
    <?php elseif (! $payment->isPaid() && $payment->iban): ?>
        <p class="text-xs text-slate-500 mt-3">QR kód nie je k dispozícii, použite údaje nižšie.</p>
    <?php endif ?>
</div>

<dl class="card divide-y divide-slate-200 dark:divide-slate-700 p-0 mb-4">
    <?php $rows = [
        ['Príjemca', $payment->payee],
        ['IBAN', $payment->ibanFormatted()],
        ['Variabilný symbol', $payment->variable_symbol],
        ['Špecifický symbol', $payment->specific_symbol],
        ['Konštantný symbol', $payment->constant_symbol],
        ['Zaplatil/a', $payment->paid_by_person_id && isset($persons[$payment->paid_by_person_id]) ? $persons[$payment->paid_by_person_id]->displayName() . ' ' . $payment->paid_at?->format('j.n.Y') : null],
        ['Poznámka', $payment->note],
    ]; ?>
    <?php foreach ($rows as [$label, $value]): if (! $value) continue; ?>
        <div class="p-3 flex items-center justify-between gap-3">
            <div class="min-w-0"><dt class="text-xs uppercase tracking-wide text-slate-500"><?= $label ?></dt><dd class="font-medium select-all break-all"><?= esc($value) ?></dd></div>
            <?php if (in_array($label, ['IBAN', 'Variabilný symbol'], true)): ?>
                <button type="button" class="btn-ghost text-sm" x-data @click="navigator.clipboard.writeText('<?= esc(str_replace(' ', '', $value), 'js') ?>'); $el.textContent = 'Skopírované'">Kopírovať</button>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</dl>

<?php if (! $payment->isPaid()): ?>
    <form action="<?= url_to('payments.pay', $payment->id) ?>" method="post" class="mb-3">
        <?= csrf_field() ?>
        <button class="btn-primary w-full bg-emerald-600 hover:bg-emerald-700"><?= nav_icon('check', 'h-5 w-5') ?> Označiť ako zaplatené</button>
    </form>
<?php else: ?>
    <form action="<?= url_to('payments.unpay', $payment->id) ?>" method="post" class="mb-3">
        <?= csrf_field() ?>
        <button class="btn-ghost w-full border border-slate-300 dark:border-slate-600">Vrátiť medzi čakajúce</button>
    </form>
<?php endif ?>

<div class="flex gap-2">
    <a href="<?= url_to('payments.edit', $payment->id) ?>" class="btn-ghost border border-slate-300 dark:border-slate-600 flex-1">Upraviť</a>
    <form action="<?= url_to('payments.delete', $payment->id) ?>" method="post" onsubmit="return confirm('Odstrániť platbu?')">
        <?= csrf_field() ?>
        <button class="btn-ghost text-red-600"><?= nav_icon('trash', 'h-5 w-5') ?></button>
    </form>
</div>
<?= $this->endSection() ?>
