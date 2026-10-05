# Vývoj – rýchly štart

## Požiadavky

- PHP 8.2+ s rozšíreniami `intl`, `mbstring`, `gd`, `sqlite3` (lokálne) alebo `mysqli` (produkcia)
- Composer 2
- Node 22 (len na build CSS/JS, na serveri nie je potrebný)

## Prvé spustenie

```bash
composer install
npm install && npm run build          # Tailwind → public/assets/app.css, htmx + Alpine → public/assets/vendor/
cp .env.example .env                   # lokálne stačí SQLite, viď komentár v súbore
php spark key:generate                 # encryption.key do .env
php spark migrate --all                # Shield + Settings + moduly
php spark app:install                  # domácnosť, prvá osoba a admin účet (interaktívne alebo cez --email ...)
php spark serve                        # http://localhost:8080
```

Počas vývoja CSS: `npm run watch`.

## Testy

```bash
vendor/bin/phpunit
```

Testy bežia nad SQLite v pamäti (skupina `tests` v `app/Config/Database.php`) a spúšťajú migrácie zo všetkých namespace-ov.

## Štruktúra

```
app/
  Config/Family.php        registrácia modulov (dashboard karty, termíny, nástroje asistenta, spodná navigácia)
  Config/AuthGroups.php    roly admin / adult / child / guest a oprávnenia <modul>.<akcia>
  Helpers/family_helper.php sk_date(), nav_icon()
  Views/layouts/app.php    mobilný shell: hlavička, obsah, spodná navigácia, FAB
  Modules/
    Core/                  kontrakty (DashboardCardProvider, DeadlineProvider, AssistantToolProvider), Card, HouseholdContext
    Auth/                  vlastné Shield views (login)
    Household/             domácnosť a osoby – vzorový modul
    Dashboard/             „Dnes“ a „Viac“
public/
  manifest.webmanifest, sw.js, assets/
resources/css/app.css      zdroj Tailwindu (komponentové triedy .card, .btn-primary, .input, ...)
```

## Nový modul v 5 krokoch

1. Vytvor `app/Modules/<Nazov>/` s podpriečinkami `Config`, `Controllers`, `Models`, `Entities`, `Views`, `Database/Migrations`.
2. Zaregistruj namespace v `app/Config/Autoload.php` (`'Modules\<Nazov>' => APPPATH . 'Modules/<Nazov>'`). Routes v `Config/Routes.php` modulu sa načítajú automaticky.
3. Pridaj oprávnenia `<modul>.view` / `<modul>.manage` do `app/Config/AuthGroups.php` a použi filter `permission:<modul>.manage` na routách.
4. Ak má modul čo povedať na „Dnes“, implementuj `DashboardCardProvider` a pridaj triedu do `Config\Family::$dashboardProviders`.
5. Každá tabuľka má `household_id`; kontrolér overuje, že záznam patrí do `service('householdContext')->householdId()`.

## Nasadenie na Websupport

Workflow `.github/workflows/deploy.yml` pri pushi do `main`:

1. `composer install --no-dev`, `npm run build`
2. `rsync` do `WS_DEPLOY_PATH` (vynecháva `.env`, `writable/`, `node_modules`, testy)
3. `php spark migrate --all` cez SSH

Na serveri raz ručne: vytvoriť `.env` z `.env.example` (MySQL údaje z panela, `encryption.key`, SMTP), nasmerovať web root na `public/`, nastaviť práva na `writable/`, spustiť `php spark app:install`. Cron (fáza 1): `*/5 * * * * php <WS_DEPLOY_PATH>/spark app:tick`.

Potrebné GitHub secrets: `WS_SSH_HOST`, `WS_SSH_PORT`, `WS_SSH_USER`, `WS_SSH_KEY`, `WS_DEPLOY_PATH`.
