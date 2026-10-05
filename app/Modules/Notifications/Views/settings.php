<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4">Nastavenia upozornení</h1>

<form action="<?= url_to('notifications.settings.save') ?>" method="post" class="card space-y-4">
    <?= csrf_field() ?>
    <label class="flex items-center justify-between gap-3">
        <span><span class="block font-medium">Push na tento telefón</span><span class="block text-sm text-slate-500">Pripomienky splatností a ranný súhrn dňa.</span></span>
        <input type="checkbox" name="push" value="1" class="h-6 w-6 rounded border-slate-300" <?= $push ? 'checked' : '' ?>>
    </label>
    <label class="flex items-center justify-between gap-3">
        <span><span class="block font-medium">E-mail</span><span class="block text-sm text-slate-500">Len dôležité: po termíne, splatnosť dnes/zajtra.</span></span>
        <input type="checkbox" name="email" value="1" class="h-6 w-6 rounded border-slate-300" <?= $email ? 'checked' : '' ?>>
    </label>
    <button class="btn-primary w-full">Uložiť</button>
</form>

<div class="card mt-4" x-data="pushSetup()" x-init="init()">
    <h2 class="font-semibold mb-1">Toto zariadenie</h2>
    <?php if (! $pushEnabled): ?>
        <p class="text-sm text-slate-500">Push nie je na serveri nakonfigurovaný (chýbajú VAPID kľúče v .env, vygenerujte ich cez <code>php spark app:vapid</code>).</p>
    <?php else: ?>
        <p class="text-sm text-slate-500 mb-3" x-text="status"></p>
        <template x-if="supported && !subscribed"><button type="button" class="btn-primary w-full" @click="subscribe()">Povoliť upozornenia na tomto zariadení</button></template>
        <template x-if="supported && subscribed"><button type="button" class="btn-ghost w-full border border-slate-300 dark:border-slate-600" @click="unsubscribe()">Zrušiť na tomto zariadení</button></template>
        <p class="text-xs text-slate-500 mt-3">Na iPhone musí byť aplikácia pridaná na plochu (Zdieľať → Pridať na plochu) a otvorená z nej.</p>
    <?php endif ?>
    <?php if ($devices !== []): ?>
        <p class="text-xs text-slate-500 mt-3">Prihlásené zariadenia: <?= count($devices) ?></p>
    <?php endif ?>
</div>

<form action="<?= url_to('notifications.test') ?>" method="post" class="mt-4">
    <?= csrf_field() ?>
    <button class="btn-ghost w-full border border-slate-300 dark:border-slate-600">Poslať skúšobné upozornenie</button>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/push.js') ?>"></script>
<?= $this->endSection() ?>
