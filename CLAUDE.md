# vaculik.info – pokyny pre prácu s repozitárom

Rodinný informačný systém. Návrh: `docs/ARCHITEKTURA.md`. Vývoj a nasadenie: `docs/VYVOJ.md`.

## Stack
- PHP 8.2+, CodeIgniter 4.7, Shield (auth), Settings, MySQL 8 (produkcia) / SQLite (lokálne a testy)
- Server-rendered views + HTMX + Alpine.js + Tailwind 3; PWA (manifest + service worker). Žiadny SPA framework.
- Nasadenie: Websupport cez `.github/workflows/deploy.yml`.

## Konvencie
- Kód a komentáre v angličtine, UI texty a dokumentácia v slovenčine (s diakritikou). URL segmenty po slovensky (`/osoby`, `/kalendar`).
- Každá doména je modul v `app/Modules/<Name>` s vlastným PSR-4 namespace v `app/Config/Autoload.php`. Routes modulu sa auto-discoverujú.
- Oprávnenia `<modul>.view` / `<modul>.manage` v `app/Config/AuthGroups.php`; routy chrániť filtrom `permission:`.
- Každá tabuľka má `household_id`, modely používajú `useTimestamps`, entity namiesto polí. Soft delete tam, kde ide o dáta rodiny.
- Dashboard karty cez `Modules\Core\Contracts\DashboardCardProvider` + registrácia v `app/Config/Family.php`.
- Mobile first: touch ciele min. 44 px, komponentové triedy v `resources/css/app.css`, po zmene CSS spustiť `npm run build` a commitnúť `public/assets/app.css`.
- Citlivé polia (rodné číslo, IBAN, čísla kartičiek) šifrovať cez CI4 `Encryption`; súbory mimo `public/`.

## Príkazy
```bash
composer install && npm install && npm run build
php spark migrate --all && php spark app:install
php spark serve
vendor/bin/phpunit
```

## Pred commitom
- `vendor/bin/phpunit` prechádza.
- Nové migrácie bežia aj na SQLite (testy) aj na MySQL (produkcia): bez MySQL-only syntaxe.
- Nikdy necommitovať `.env`, `writable/`, `node_modules/`.
