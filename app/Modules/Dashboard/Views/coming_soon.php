<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<h1 class="text-xl font-semibold mb-4"><?= esc($title) ?></h1>
<div class="card text-center text-slate-500">
    Tento modul príde v ďalšej fáze. Pozri <code>docs/ARCHITEKTURA.md</code>, sekcia 11.
</div>
<?= $this->endSection() ?>
