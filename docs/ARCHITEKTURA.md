# vaculik.info – rodinný informačný systém

Logická štruktúra systému, ktorý pomáha celej rodine orientovať sa v bežných aj nebežných dňoch.
Stack: **PHP 8.2+ / CodeIgniter 4 / MySQL 8**, nasadenie na **Websupport** (zdieľaný hosting), **mobile first PWA**.

---

## 1. Princípy návrhu

| Princíp | Čo to znamená v praxi |
|---|---|
| **Jedna obrazovka „Dnes“** | Po otvorení appky vidím to, čo ma dnes čaká: rozvrh, termíny, platby po splatnosti, úlohy. Nič netreba hľadať. |
| **Nízka kognitívna záťaž (ADHD-friendly)** | Veľké tlačidlá, jedna akcia na obrazovku, farby a ikony namiesto textu, checklisty namiesto odsekov, časovače. |
| **Všetko má termín a pripomienku** | Každá entita (platba, zmluva, STK, lieky) vie vyprodukovať termín do spoločného kalendára a notifikáciu. |
| **Dokument je prvotriedna entita** | Zmluva, bloček, kartička, záručný list – všetko je `Document` s OCR textom, pripojiteľný k čomukoľvek. Agent nad tým hľadá. |
| **Osoba, nie používateľ** | Dieťa je `Person` (má rozvrh, kartičku poistenca, úlohy) aj keď nemá login. Login je `User` naviazaný na osobu. |
| **Mobile first** | Spodná navigácia, 5 záložiek, gestá, offline cache „Dnes“, inštalovateľná PWA, tmavý režim. Desktop je len širší layout. |
| **Moduly, nie monolit** | Každá doména je samostatný CI4 modul (vlastné routes, controllers, models, views, migrations). Dá sa vypnúť. |

---

## 2. Informačná architektúra – čo kam patrí

Systém je rozdelený do **7 domén**. Každá doména obsahuje moduly. Moduly zdieľajú jadro (osoby, dokumenty, termíny, kontakty, notifikácie).

```
┌─────────────────────────────────────────────────────────────────┐
│  DNES (dashboard)   ← agregácia zo všetkých modulov              │
├─────────────────────────────────────────────────────────────────┤
│  ČAS        ŠKOLA        PENIAZE      DOMÁCNOSŤ     ZDRAVIE      │
│  Kalendár   Rozvrhy      Platby       Zásoby        Kartičky     │
│  Termíny    Edupage      Bločky       Zariadenia    Lieky        │
│  Rutiny     Zmluvy       Rozpočet     Jedálniček    Lekári       │
│  Krúžky     Úlohy/body   PAY by sq.   Nákupy        Denník       │
├─────────────────────────────────────────────────────────────────┤
│  ASISTENT (chat agent nad dátami a dokumentmi)                   │
├─────────────────────────────────────────────────────────────────┤
│  JADRO: Osoby · Používatelia · Kontakty · Dokumenty · Termíny    │
│         · Notifikácie · Nastavenia · Audit                       │
└─────────────────────────────────────────────────────────────────┘
```

### 2.1 Jadro (Core) – používa každý modul

| Modul | Účel | Kľúčové entity |
|---|---|---|
| **Domácnosť & osoby** | Kto je v rodine. Dospelí, deti, hostia (starí rodičia). Dátum narodenia, rodné číslo (šifrované), fotka, farba osoby (používa sa všade v UI). | `household`, `person` |
| **Používatelia & prístup** | Login (meno + heslo, voliteľne 2FA), roly, PIN pre deti. Postavené na **CodeIgniter Shield**. | `users`, `auth_*`, `roles` |
| **Kontakty** | Zdieľaný adresár: lekári, škola, servisy, susedia. Tagy, väzba na osobu/zariadenie/školu. Import z telefónu (vCard). | `contact`, `contact_tag` |
| **Dokumenty** | Centrálne úložisko súborov. Typ (zmluva, bloček, kartička, záručný list, potvrdenie…), expirácia, OCR text, väzba na ľubovoľnú entitu. | `document`, `document_link` (polymorfná väzba) |
| **Termíny & pripomienky** | Jednotný „deadline engine“. Každý modul zapíše termín (STK, splatnosť, expirácia pasu). Termíny sa zobrazujú v kalendári a v Dnes. | `deadline`, `reminder` |
| **Notifikácie** | Web Push (PWA), e-mail, voliteľne Telegram bot. Kto, kedy, cez čo. Tiché hodiny. | `notification`, `push_subscription` |
| **Nastavenia & audit** | Nastavenia domácnosti, feature flags modulov, log kto čo zmenil. | `setting`, `audit_log` |

### 2.2 Čas

| Modul | Účel | Poznámky |
|---|---|---|
| **Kalendár** | Spoločný rodinný kalendár. Udalosť má osobu/osoby, miesto, kto vezie, prílohy. Pohľady: deň / týždeň / mesiac / agenda. | Sync s Google Calendar (sekcia 6). |
| **Termíny** | Zoznam všetkých blížiacich sa deadlinov zo všetkých modulov (filter: osoba, typ, horizont). | Len pohľad nad `deadline`. |
| **Rutiny & checklisty** ★ | Ranná/večerná rutina, balenie tašky podľa rozvrhu, víkendové upratovanie. Vizuálne, odškrtávacie, s časovačom. | Nový modul, viď sekcia 3. |
| **Krúžky & logistika** ★ | Sezónne aktivity (tréning, ZUŠ), kto koho kedy vezie, striedanie rodičov. | Nový modul, viď sekcia 3. |

### 2.3 Škola

| Modul | Účel | Poznámky |
|---|---|---|
| **Školy a inštitúcie** | Škola, škôlka, ZUŠ: adresa, kontakty, školský rok, prázdniny. Osoba → trieda → škola. | `institution`, `enrollment` |
| **Rozvrhy** | Týždenný rozvrh per dieťa, párny/nepárny týždeň, výnimky (riaditeľské voľno). Zobrazí sa v Dnes („dnes má telesnú → balíme úbor“). | Import: ručne, z Edupage exportu, alebo fotka rozvrhu → LLM extrakcia. |
| **Správy z Edupage** | Jednotná schránka správ zo školy, priradená k dieťaťu, s označením „vyžaduje akciu“ (podpísať, zaplatiť, priniesť). Akcia → úloha / platba / termín jedným ťuknutím. | Zdroj: e-mailové notifikácie Edupage cez IMAP cron (spoľahlivé), neoficiálne API ako voliteľný konektor. |
| **Zmluvy** | Zmluvy so školou/škôlkou, dodatky, výpovedné lehoty, viazanosť. Je to `Document` typu zmluva + metadáta (platnosť od–do, mesačná suma → generuje pravidelnú platbu). | Väzba na Platby a Termíny. |
| **Úlohy & odmeny** ★ | Domáce úlohy, povinnosti, bodový systém, vreckové. | Nový modul, viď sekcia 3. |

### 2.4 Peniaze

| Modul | Účel | Poznámky |
|---|---|---|
| **Platby** | Pravidelné (školné, obedy, krúžky – recurrence rule) aj jednorazové (výlet, lyžiarsky). Stav: čaká / zaplatené / po splatnosti. Kto platí, za koho, komu, IBAN, VS. | Generuje `deadline`. |
| **PAY by square** | Z platby vygeneruje QR kód podľa slovenského štandardu – zaplatím z mobilu za 10 sekúnd. | Knižnica `pay-by-square` (PHP), bez externých služieb. |
| **Bločky & doklady** | Fotka bločku z mobilu → OCR/LLM extrahuje sumu, dátum, obchod → návrh platby alebo výdavku. Bloček je `Document`, prepojený na platbu alebo zariadenie (záruka). | |
| **Rozpočet (prehľad)** | Mesačný prehľad výdavkov per kategória a dieťa. Bez účtovníctva, len orientácia. | Fáza 2+. |

### 2.5 Domácnosť

| Modul | Účel | Poznámky |
|---|---|---|
| **Zásoby** | Lokality (chladnička, špajza, mraznička, lekárnička, garáž) → položky (množstvo, jednotka, expirácia, minimum). Pod minimom → nákupný zoznam. Expiruje → notifikácia. | Čiarové kódy cez kameru (voliteľné). |
| **Zariadenia & majetok** | Autá (ŠPZ, STK, EK, PZP, havarijné, km, servisná história), spotrebiče (kúpené, záruka do, bloček, manuál), dom/byt (revízie, kotol, komín). Plánované opravy, kontakt na servis. | Každý termín (STK, záruka, revízia) → `deadline`. |
| **Jedálniček & nákupy** ★ | Týždenný plán jedál, zoznam receptov, automatický nákupný zoznam prepojený so zásobami. | Nový modul, viď sekcia 3. |

### 2.6 Zdravie

| Modul | Účel | Poznámky |
|---|---|---|
| **Kartičky poistencov** | Per osoba: poisťovňa (VšZP, Dôvera, Union), číslo, fotka prednej/zadnej strany, EÚ preukaz. Dostupné offline v PWA – v čakárni bez signálu. | Je to `Document` typu kartička + štruktúrované polia. |
| **Lekári & vyšetrenia** | Pediatr, zubár, psychológ, ortopéd… (kontakty s rolou). Vyšetrenie = udalosť v kalendári + záznam (výsledok, správa ako dokument, ďalšia kontrola → termín). | |
| **Lieky & zdravotný denník** ★ | Dávkovanie, pripomienky, zásoba (prepojená s lekárničkou), denník symptómov/správania pre konzultácie s odborníkom. | Nový modul, viď sekcia 3. |

### 2.7 Asistent

| Modul | Účel | Poznámky |
|---|---|---|
| **Chat agent** | „Kedy má Ema najbližšie zubára?“, „Koľko platíme za škôlku a do kedy platí zmluva?“, „Nájdi záručný list na práčku.“ Odpovedá nad dátami modulov a OCR textom dokumentov, rešpektuje oprávnenia používateľa. | Architektúra v sekcii 7. |
| **Rýchle vloženie** | Jedno textové pole / fotka / hlas → agent rozpozná, či ide o platbu, udalosť, kontakt alebo dokument a predvyplní formulár. Najdôležitejšia ADHD funkcia: zníži bariéru zadávania na minimum. | Používa štruktúrované výstupy LLM. |

---

## 3. Päť navrhnutých modulov navyše (★)

Vybrané tak, aby pokryli najčastejšie trecie plochy rodiny s ADHD deťmi a ADHD rodičom: prechody medzi činnosťami, zabúdanie, motivácia, logistika a jedlo.

### 3.1 Rutiny & checklisty
- **Prečo:** ADHD mozog nedrží sekvenciu krokov v hlave. Vizuálna rutina s odškrtávaním a časovačom znižuje konflikty ráno aj večer.
- **Čo:** Šablóny rutín (ráno, večer, pred školou, po príchode), per osoba, kroky s ikonou a odhadovaným časom, režim „kiosk“ na tablete v kuchyni, časovač na krok (Pomodoro pre úlohy).
- **Prepojenia:** Rozvrh (čo zajtra baliť), Úlohy & odmeny (dokončená rutina = body), Lieky (krok „ranný liek“).

### 3.2 Úlohy, povinnosti & odmeny
- **Prečo:** Okamžitá spätná väzba funguje lepšie než vzdialený cieľ. Transparentný bodový systém odstraňuje vyjednávanie.
- **Čo:** Úlohy jednorazové aj opakované, priradené osobe, s bodmi. Rodič schvaľuje. Body → vreckové / odmeny z katalógu (vybrané spolu s deťmi). Streaky, nie tresty. Dospelí majú vlastný zoznam (bez bodov, s prioritou a „dnes len 3 veci“).
- **Prepojenia:** Edupage správa „priniesť“ → úloha. Rutiny → body. Dnes → zoznam na dnes.

### 3.3 Jedálniček & nákupný zoznam
- **Prečo:** „Čo bude na večeru“ je denný rozhodovací náklad. Plán na týždeň + automatický nákup ho odstráni.
- **Čo:** Recepty (ingrediencie, čas, obľúbenosť u detí), týždenný plán ťahaním, nákupný zoznam generovaný z plánu mínus zásoby, zdieľaný v reálnom čase (kto je v obchode, odškrtáva).
- **Prepojenia:** Zásoby (odpočet pri varení, doplnenie po nákupe), Bločky (nákup → bloček → výdavok).

### 3.4 Lieky & zdravotný denník
- **Prečo:** Medikácia pri ADHD vyžaduje presnosť (ráno, rovnaký čas), recepty sa obnovujú, odborníci chcú vidieť trend správania.
- **Čo:** Liek per osoba, dávkovanie, pripomienka, potvrdenie podania (kto podal), zásoba v lekárničke s upozornením na došahovanie, termín obnovenia receptu. Denník: krátke denné záznamy (nálada, spánok, škola, poznámka), exportovateľný PDF pre lekára.
- **Prepojenia:** Zásoby (lekárnička), Lekári (kontrola), Termíny (recept), Rutiny (ranný liek).

### 3.5 Krúžky, aktivity & logistika
- **Prečo:** Dve deti, päť krúžkov, dvaja rodičia a jedno auto. Kto vezie a kto vyzdvihne je najčastejší zdroj chaosu.
- **Čo:** Aktivita (sezóna od–do, dni, miesto, tréner/kontakt, platba, výbava), týždenný rozpis dopravy s priradeným rodičom, výmeny a potvrdenia, výbava → checklist pred odchodom.
- **Prepojenia:** Kalendár (generuje udalosti), Platby (sezónny poplatok), Kontakty (tréner), Rutiny (výbava).

**Ďalšie kandidáti do zásobníka** (fáza 3+): Expirácie dokladov (pasy, OP, vodičák – pokrýva Termíny), Trezor hesiel a účtov (prihlásenia do školských systémov), Darčeky & oslavy (narodeniny, wishlisty), Cestovanie (baliace zoznamy, doklady), Šatník & veľkosti detí.

---

## 4. Mobilná navigácia (sitemap)

Spodná lišta má **5 záložiek**. Všetko ostatné je pod „Viac“ alebo dostupné z Dnes.

```
┌──────────────────────────────────────────┐
│  [Osoba ▾]  Dnes, utorok 6. 10.    🔔 3  │   ← prepínač osoby filtruje celý obsah
├──────────────────────────────────────────┤
│  ⚠ 2 platby po splatnosti        [QR]   │   ← karty zoradené podľa naliehavosti
│  🎒 Ema: telesná, výtvarná → balíme...   │
│  💊 Tomáš: ranný liek  [✓ podané]        │
│  📅 15:00 zubár Ema (vezie: Vlado)       │
│  ✅ Dnes 3 úlohy        ▸ otvoriť        │
│  📩 2 nové správy z Edupage              │
├──────────────────────────────────────────┤
│                 (＋) rýchle vloženie      │   ← text / fotka / hlas → agent roztriedi
├──────────────────────────────────────────┤
│  Dnes   Kalendár   Peniaze   Dokumenty   Viac  │
└──────────────────────────────────────────┘
```

| Záložka | Obsah |
|---|---|
| **Dnes** | Agregovaný dashboard (vyššie). Jediná obrazovka, ktorú väčšina členov potrebuje. |
| **Kalendár** | Agenda / týždeň / mesiac. Vrstvy: udalosti, termíny, rozvrhy, krúžky, rutiny. Filter per osoba. |
| **Peniaze** | Platby (čakajúce / zaplatené), QR, bločky, rozpočet. |
| **Dokumenty** | Všetky dokumenty s fulltextom, filter typ/osoba/expirácia. Kartičky poistencov sú tu prišpendlené navrchu. |
| **Viac** | Mriežka modulov: Škola · Edupage · Rozvrhy · Zmluvy · Úlohy · Rutiny · Krúžky · Zásoby · Zariadenia · Jedálniček · Lieky · Lekári · Kontakty · Asistent · Nastavenia. |

Asistent je dostupný aj plávajúcim tlačidlom na každej obrazovke (chat bublina).

Deti vidia zjednodušený režim: Dnes (svoj rozvrh, rutiny, úlohy, body), Kalendár (len svoje), Viac (odmeny). Bez peňazí a dokumentov.

---

## 5. Roly a oprávnenia

| Rola | Kto | Čo môže |
|---|---|---|
| `admin` | Rodič-správca | Všetko, správa používateľov a modulov. |
| `adult` | Druhý rodič | Všetko okrem správy používateľov. |
| `child` | Dieťa s loginom (PIN) | Svoj rozvrh, rutiny, úlohy, body, kalendár (vlastný). Nič finančné, žiadne cudzie dokumenty. |
| `guest` | Starí rodičia, opatrovateľka | Len čítanie: kalendár vybraných osôb, kontakty, kartičky vybraných detí. |

Oprávnenia sa vyhodnocujú na úrovni **osoby** (`person_id`), nie modulu: dieťa vidí svoje dáta vo všetkých povolených moduloch. Agent dedí oprávnenia používateľa, ktorý sa pýta.

---

## 6. Integrácie

| Integrácia | Prístup | Fáza |
|---|---|---|
| **Google Calendar** | Fáza 1: jednosmerný import ICS/API (read-only) per osoba, cron každých 15 min. Fáza 2: OAuth2 obojsmerný sync, mapovanie `calendar_event.google_id`. Udalosti vytvorené v systéme sa pushnú do rodinného Google kalendára. | 1 → 2 |
| **Edupage** | Nemá oficiálne API. Primárny zdroj: e-mailové notifikácie preposielané na vyhradenú schránku, cron ich číta cez IMAP a parsuje (odosielateľ, dieťa, predmet, typ). Sekundárne: ručné vloženie, zdieľanie textu z Edupage appky do PWA (Web Share Target). Neoficiálne API len ako izolovaný konektor s vypínačom. | 1 |
| **PAY by square** | Lokálne generovanie QR (PHP knižnica), bez externej služby. | 1 |
| **Web Push** | VAPID kľúče, service worker, subscription per zariadenie. Fallback e-mail cez SMTP Websupportu. | 1 |
| **Telegram bot** | Voliteľný kanál notifikácií a rýchleho vloženia („pošli fotku bločku botovi“). | 3 |
| **LLM (Anthropic Claude)** | Agent, OCR/extrakcia z fotiek, rýchle vloženie. Cez oficiálny PHP SDK `anthropic-ai/sdk`. | 2 |

---

## 7. Asistent – architektúra

Asistent nie je „chat nad databázou“, ale **agent s nástrojmi**, ktorý rešpektuje oprávnenia.

```
používateľ ──▶ /asistent (chat UI, SSE stream)
                  │
                  ▼
           AssistantService (CI4)
                  │  system prompt: kto sa pýta, dnešný dátum, členovia domácnosti
                  │  tools (PHP funkcie s oprávneniami používateľa):
                  │    search_documents(query, person?, type?)   ← MySQL FULLTEXT nad OCR
                  │    get_events(from, to, person?)
                  │    get_payments(status, person?)
                  │    get_deadlines(horizon)
                  │    get_contacts(tag?)
                  │    get_inventory(location?)
                  │    propose_create(entity, fields)           ← vráti predvyplnený formulár, NIKDY neukladá priamo
                  ▼
           Anthropic Messages API (model claude-opus-5-5, tool runner z PHP SDK)
```

Zásady:
- **Čítanie áno, zápis len cez potvrdenie.** Agent vie navrhnúť platbu alebo udalosť, používateľ ju potvrdí ťuknutím.
- **Dokumenty → text pri nahratí.** Pri uploade sa cez LLM (vision) vytiahne OCR text a štruktúrované polia (typ, dátum, suma, expirácia). Uloží sa do `document.ocr_text` a FULLTEXT indexu. Agent potom hľadá v texte, nie v obrázkoch – rýchle a lacné.
- **Konverzácie sa ukladajú** (`assistant_conversation`, `assistant_message`) kvôli kontinuite a auditu.
- **Websupport limity:** odpoveď sa streamuje cez SSE; `max_execution_time` nastaviť na 120 s pre endpoint asistenta; dlhé OCR dávky bežia v crone, nie v requeste.

---

## 8. Dátový model – jadro

Zjednodušený prehľad hlavných tabuliek. Každá má `id`, `household_id`, `created_at`, `updated_at`, `deleted_at` (soft delete), `created_by`.

```
household ──< person ──< user (Shield)
                │
                ├──< enrollment >── institution ──< timetable ──< timetable_slot
                ├──< insurance_card
                ├──< medication ──< medication_log
                ├──< task_assignment >── task ; reward_ledger
                └──< routine ──< routine_step

document ──< document_link (linkable_type, linkable_id)     ← polymorfné: payment, asset, contract, person, event…
deadline  (deadlinable_type, deadlinable_id, due_at, kind)  ← polymorfné: STK, splatnosť, expirácia, recept
reminder  (deadline_id | event_id, person_id, channel, offset)
notification (user_id, channel, payload, sent_at, read_at)

calendar_event (person_ids[], starts_at, ends_at, location, driver_person_id, google_id, source)
payment (recurrence_rule, amount, due_at, payee, iban, vs, status, person_id, category_id)
contract (institution_id, person_id, valid_from, valid_to, notice_period, monthly_amount)
edupage_message (person_id, received_at, subject, body, action_required, linked_task_id)

contact ──< contact_tag ; contact_link (linkable_type, linkable_id)
inventory_location ──< inventory_item (qty, unit, min_qty, expires_at)
asset (kind: car|appliance|property, …) ──< asset_service_record ; asset_deadline → deadline
recipe ──< recipe_ingredient ; meal_plan ──< meal_plan_entry ; shopping_list ──< shopping_item
activity (season_from, season_to, weekday, time, place, contact_id) ──< activity_ride (date, driver_person_id)
assistant_conversation ──< assistant_message
audit_log
```

Citlivé polia (rodné číslo, čísla kartičiek, IBAN) sú šifrované cez CI4 `Encryption` službu; súbory ležia mimo `public/` a servírujú sa cez controller s kontrolou oprávnení.

---

## 9. Štruktúra kódu (CodeIgniter 4, modulárne)

```
app/
  Config/               ← Routes.php len includuje routes modulov, Autoload.psr4 registruje Modules\*
  Core/                 ← zdieľané: BaseController, BaseModel, Traits (HasDocuments, HasDeadlines), Services
  Modules/
    Household/          ← osoby, domácnosť, roly
    Contacts/
    Documents/          ← upload, OCR pipeline, polymorfné linky
    Deadlines/          ← deadline engine + reminders
    Notifications/      ← push, email, telegram adaptéry
    Calendar/           ← udalosti + Google sync
    School/             ← inštitúcie, rozvrhy, edupage, zmluvy
    Finance/            ← platby, PAY by square, bločky, rozpočet
    Inventory/
    Assets/             ← zariadenia, autá, servis
    Health/             ← kartičky, lekári, lieky, denník
    Routines/
    Tasks/              ← úlohy & odmeny
    Meals/
    Activities/         ← krúžky & logistika
    Assistant/
    Dashboard/          ← „Dnes“: zbiera karty z modulov cez DashboardCardProvider interface
  Each module:
    Config/Routes.php, Controllers/, Models/, Entities/, Services/, Views/,
    Database/Migrations/, Database/Seeds/, Language/sk/
public/
  assets/               ← build Tailwind + Alpine.js (htmx pre čiastočné prekreslenie), service worker, manifest.json
writable/
  uploads/              ← dokumenty (mimo public)
```

Frontend: **server-rendered CI4 views + HTMX + Alpine.js + Tailwind**. Žiadny SPA framework – rýchle na zdieľanom hostingu, jednoduché na údržbu, PWA cez service worker (cache „Dnes“, kartičky poistencov, offline fronta pre odškrtnutia).

Modul sa registruje cez interface (`DashboardCardProvider`, `DeadlineProvider`, `AssistantToolProvider`), takže Dnes, Termíny a Asistent o ňom vedia automaticky.

---

## 10. Nasadenie na Websupport

| Téma | Riešenie |
|---|---|
| PHP / DB | PHP 8.2+, MySQL 8 (utf8mb4). `intl`, `mbstring`, `gd`/`imagick`, `curl` sú na Websupporte dostupné. |
| Deploy | Git push → GitHub Actions build (composer install --no-dev, Tailwind build) → rsync/SFTP na hosting. Alternatíva: `git pull` cez SSH na Websupporte. `.env` len na serveri. |
| Document root | Smeruje na `public/`. Zvyšok aplikácie mimo webroot. |
| Cron | Websupport cron každých 5 min spúšťa `php spark queue:work` (CI4 Queue) a `php spark app:tick` (pripomienky, Google sync, IMAP Edupage, expirácie zásob, OCR fronta). |
| Súbory | `writable/uploads`, limit veľkosti 10 MB, obrázky sa zmenšia pri uploade. Zálohy: nočný dump DB + rsync uploads na externé úložisko. |
| Bezpečnosť | HTTPS (Let's Encrypt v paneli), CSRF, rate limit na login, Shield session + remember me, šifrované citlivé polia, hlavičky CSP. |
| Monitoring | CI4 log → súbor, denný e-mail súhrn chýb; health endpoint `/up` pre externý uptime monitor. |

---

## 11. Fázy realizácie

| Fáza | Rozsah | Výsledok |
|---|---|---|
| **0 – Základ** | CI4 skeleton, Shield, modulová štruktúra, Tailwind/HTMX, PWA shell, deploy pipeline, migrácie jadra. | Prihlásenie, osoby, prázdne Dnes. |
| **1 – Denný chod** | Kalendár (ICS import), Termíny, Platby + PAY by square, Dokumenty + kartičky poistencov, Kontakty, Notifikácie (push + email), Dnes. | Rodina ho začne reálne používať. |
| **2 – Škola & zdravie** | Rozvrhy, Edupage (IMAP), Zmluvy, Lekári & vyšetrenia, Lieky, Zariadenia & autá, Google OAuth obojsmerne. | Pokryté všetky pôvodné požiadavky okrem agenta a zásob. |
| **3 – Asistent & domácnosť** | OCR pipeline, Asistent, Rýchle vloženie, Zásoby, Bločky s extrakciou. | Agent hľadá v dokumentoch, zadávanie fotkou. |
| **4 – ADHD moduly** | Rutiny, Úlohy & odmeny, Krúžky & logistika, Jedálniček & nákupy, Zdravotný denník, kiosk režim. | Kompletný systém. |

Každá fáza končí nasadením na produkciu a týždňom reálneho používania pred ďalšou.

---

## 12. Otvorené rozhodnutia

1. **Edupage konektor:** spoliehať sa len na e-mail notifikácie, alebo investovať do neoficiálneho API s rizikom, že sa rozbije?
2. **Google Calendar:** stačí jeden zdieľaný rodinný kalendár, alebo každá osoba svoj (viac OAuth účtov)?
3. **Deti a login:** PIN na spoločnom tablete, alebo vlastné účty na vlastných telefónoch?
4. **Jazyk UI:** iba slovenčina, alebo i18n od začiatku (CI4 `Language/`)? Návrh: štruktúra i18n áno, preklady zatiaľ len sk.
