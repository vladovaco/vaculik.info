<?php if (count($persons) > 1): ?>
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 mb-3" hx-boost="false">
        <a href="<?= $baseUrl ?>" class="chip <?= $personId === null ? 'chip-active' : '' ?>">Všetci</a>
        <?php foreach ($persons as $p): ?>
            <a href="<?= $baseUrl ?><?= str_contains($baseUrl, '?') ? '&' : '?' ?>osoba=<?= $p->id ?>" class="chip <?= $personId === $p->id ? 'chip-active' : '' ?>" style="<?= $personId === $p->id ? 'background:' . esc($p->color, 'attr') . ';border-color:' . esc($p->color, 'attr') : '' ?>"><?= esc($p->displayName()) ?></a>
        <?php endforeach ?>
    </div>
<?php endif ?>
