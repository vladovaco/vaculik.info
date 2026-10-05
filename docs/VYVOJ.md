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
  Config/Family.php        registrácia modulov (dashboard karty, termíny, nástroje asistenta, spodná navigácia, pripomienky)
  Config/Push.php          VAPID kľúče pre Web Push (hodnoty z .env)
  Models/UserModel.php     Shield používateľ rozšírený o person_id
  Config/AuthGroups.php    roly admin / adult / child / guest a oprávnenia <modul>.<akcia>
  Helpers/family_helper.php sk_date(), nav_icon()
  Views/layouts/app.php    mobilný shell: hlavička, obsah, spodná navigácia, FAB
  Modules/
    Core/                  kontrakty (DashboardCardProvider, DeadlineProvider, AssistantToolProvider), Card, HouseholdContext
    Auth/                  vlastné Shield views (login)
    Household/             domácnosť a osoby – vzorový modul
    Dashboard/             „Dnes“ a „Viac“
    Contacts/              zdieľané kontakty (lekári, škola, servisy)
    Documents/             dokumenty so súbormi, kartičky poistencov, expirácie
    Finance/               platby, opakovanie, PAY by square QR
    Calendar/              udalosti, agenda a mesiac, import ICS (Google Calendar)
    Deadlines/             stránka Termíny (agreguje DeadlineProvider-ov)
    Notifications/         in-app upozornenia, Web Push, e-mail, pripomienky a ranný súhrn
  Commands/                app:install, app:deploy, app:tick (cron), app:vapid
public/
  manifest.webmanifest, sw.js, assets/
resources/css/app.css      zdroj Tailwindu (komponentové triedy .card, .btn-primary, .input, ...)
```

## Nový modul v 5 krokoch

1. Vytvor `app/Modules/<Nazov>/` s podpriečinkami `Config`, `Controllers`, `Models`, `Entities`, `Views`, `Database/Migrations`.
2. Zaregistruj namespace v `app/Config/Autoload.php` (`'Modules\<Nazov>' => APPPATH . 'Modules/<Nazov>'`). Routes v `Config/Routes.php` modulu sa načítajú automaticky.
3. Pridaj oprávnenia `<modul>.view` / `<modul>.manage` do `app/Config/AuthGroups.php` a použi filter `permission:<modul>.manage` na routách.
4. Ak má modul čo povedať na „Dnes“, implementuj `DashboardCardProvider` a pridaj triedu do `Config\Family::$dashboardProviders`. Ak produkuje termíny (splatnosti, expirácie), implementuj `DeadlineProvider` a pridaj ho do `$deadlineProviders`; stránka Termíny, pripomienky aj ranný súhrn ho zoberú automaticky.
5. Každá tabuľka má `household_id`; kontrolér overuje, že záznam patrí do `service('householdContext')->householdId()`.

## Nasadenie na Websupport

Nasadenie je **pull-based**: server si sám stiahne commity z GitHubu. Nepotrebuje trvalé SSH ani GitHub secrets. Prvá inštalácia sa robí raz cez dočasnú webovú konzolu Websupportu, každá ďalšia aktualizácia je jeden príkaz, ktorý môže spúšťať aj cron.

### Prvá inštalácia (raz, cez konzolu)

1. V administrácii Websupportu aktivujte konzolu (Shell) pre doménu a prihláste sa.
2. Overte nástroje. Potrebný je `git` a PHP 8.2+. Composer nie je nutný, `app:deploy` si stiahne `composer.phar`, ak chýba.
   ```bash
   which git php composer; php -v
   ```
3. Naklonujte repozitár vedľa dnešného web rootu domény (nie doň). Ak je web root napr. `/.../vaculik.info/web`, aplikácia pôjde do `/.../vaculik.info/app`.
   ```bash
   cd /.../vaculik.info
   git clone --branch main https://github.com/vladovaco/vaculik.info.git app
   cd app
   ```
   Pre privátny repozitár použite GitHub token s právom len na čítanie (Settings → Developer settings → Fine-grained token, Contents: Read) v URL: `https://<token>@github.com/vladovaco/vaculik.info.git`. Git si ho uloží do `.git/config`, ktorý je mimo web rootu.
4. Vytvorte `.env` z `.env.example`: MySQL údaje z administrácie, `CI_ENVIRONMENT = production`, `app.baseURL = 'https://vaculik.info/'`, `encryption.key` (vygenerujte `php spark key:generate --show`), SMTP.
5. Prvé nasadenie, migrácie a admin účet:
   ```bash
   php spark app:deploy --force
   php spark app:install
   ```
6. V administrácii Websupportu zmeňte adresár domény (document root) na `/.../vaculik.info/app/public`. Verejný je len tento priečinok, zvyšok aplikácie (vrátane `.env` a `writable/`) zostáva mimo webu.
7. Overte `https://vaculik.info/up` (JSON so stavom `ok`) a prihlásenie.

### Aktualizácia

Po pushi do `main` spustite v konzole:

```bash
cd /.../vaculik.info/app && php spark app:deploy
```

Príkaz stiahne nové commity (`git pull --ff-only`), spustí `composer install --no-dev`, migrácie a vyčistí cache. Ak nie sú nové commity, nič nerobí. Zámok v `writable/deploy.lock` bráni súbežným behom.

### Cron: pripomienky a synchronizácia kalendárov (povinné pre fázu 1)

`php spark app:tick` synchronizuje externé kalendáre (staršie než 15 minút) a posiela pripomienky splatností, končiacich dokumentov a ranný súhrn dňa. V administrácii Websupportu pridajte cron každých 5 až 15 minút:

```
*/10 * * * * cd /.../vaculik.info/app && php spark app:tick >> writable/logs/tick.log 2>&1
```

Príkaz je idempotentný (každé upozornenie vznikne raz) a má zámok proti súbežnému behu.

### Web Push a e-mail

1. Raz vygenerujte VAPID kľúče: `php spark app:vapid` a vložte výstup do `.env` (`push.vapidPublicKey`, `push.vapidPrivateKey`, `push.subject`).
2. Každý člen rodiny si na telefóne v „Upozornenia → Nastavenia“ povolí push. Na iPhone musí byť appka pridaná na plochu.
3. E-mail (len dôležité upozornenia) vyžaduje SMTP v `.env` (`email.*`), používateľ si ho zapína v tých istých nastaveniach.

### Automaticky nasadzovať cez cron (voliteľné)

V administrácii Websupportu pridajte cron, ktorý beží každých 5 až 15 minút:

```
*/10 * * * * cd /.../vaculik.info/app && php spark app:deploy >> writable/logs/deploy.log 2>&1
```

Push do `main` sa potom prejaví do niekoľkých minút bez ďalšieho zásahu. Ak cron Websupportu nepovoľuje shell príkaz, ale len PHP skript, nastavte ako skript `/.../vaculik.info/app/spark` s argumentom `app:deploy`.

### Čo sa necommituje a kde to na serveri je

| Čo | Kde | Poznámka |
|---|---|---|
| `.env` | koreň aplikácie | vytvoriť ručne, nikdy do gitu |
| `vendor/` | koreň aplikácie | vytvára `app:deploy` cez composer |
| `writable/` | koreň aplikácie | logy, cache, `deploy.lock`, `tick.lock`; práva na zápis pre PHP |
| `writable/uploads/documents/` | koreň aplikácie | nahrané dokumenty a kartičky, mimo web rootu; zálohovať spolu s DB |
| `public/assets/app.css` | v gite | server nespúšťa npm, build CSS sa commituje (CI to kontroluje) |

### CI na GitHube

`.github/workflows/ci.yml` pri každom pushi spustí testy a overí, že `public/assets/app.css` zodpovedá zdrojom v `resources/css`.
