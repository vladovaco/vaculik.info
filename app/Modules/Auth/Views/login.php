<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="text-center mb-8">
    <img src="<?= base_url('assets/icons/icon.svg') ?>" alt="" class="h-16 w-16 mx-auto mb-3">
    <h1 class="text-2xl font-semibold tracking-tight">vaculik<span class="text-blue-600">.info</span></h1>
    <p class="text-sm text-slate-500">Rodinný systém</p>
</div>

<?php if (session('error') !== null): ?>
    <div class="flash flash-error"><?= esc(session('error')) ?></div>
<?php elseif (session('errors') !== null): ?>
    <div class="flash flash-error">
        <?php foreach ((array) session('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach ?>
    </div>
<?php endif ?>
<?php if (session('message') !== null): ?>
    <div class="flash flash-ok"><?= esc(session('message')) ?></div>
<?php endif ?>

<form action="<?= url_to('login') ?>" method="post" class="card space-y-4">
    <?= csrf_field() ?>
    <div>
        <label class="label" for="email"><?= lang('Auth.email') ?></label>
        <input class="input" type="email" id="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required autofocus>
    </div>
    <div>
        <label class="label" for="password"><?= lang('Auth.password') ?></label>
        <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="h-5 w-5 rounded border-slate-300" <?= old('remember') ? 'checked' : '' ?>>
            <?= lang('Auth.rememberMe') ?>
        </label>
    <?php endif ?>
    <button type="submit" class="btn-primary w-full"><?= lang('Auth.login') ?></button>
</form>
<?= $this->endSection() ?>
