# vaculik.info

Rodinný informačný systém – kalendár, škola, platby, dokumenty, zdravie, domácnosť a asistent na jednom mieste. Mobile first PWA.

- Stack: PHP 8.2+ · CodeIgniter 4 · Shield · MySQL 8 · HTMX + Alpine.js + Tailwind
- Hosting: Websupport (pull-based deploy príkazom `php spark app:deploy`)

| Dokument | Obsah |
|---|---|
| [docs/ARCHITEKTURA.md](docs/ARCHITEKTURA.md) | Logická štruktúra, moduly, dátový model, integrácie, fázy |
| [docs/VYVOJ.md](docs/VYVOJ.md) | Lokálne spustenie, testy, nový modul, nasadenie |
| [CLAUDE.md](CLAUDE.md) | Konvencie pre vývoj |

## Stav

- [x] Fáza 0 – základ: CI4 + Shield, roly, modulová štruktúra, mobilný shell, PWA, Osoby, „Dnes“, CI, deploy príkaz
- [x] Fáza 1 – denný chod: Kalendár (ICS import), Termíny, Platby + PAY by square, Dokumenty + kartičky poistencov (offline), Kontakty, Upozornenia (in-app, push, e-mail, ranný súhrn)
- [ ] Fáza 2 – škola a zdravie
- [ ] Fáza 3 – asistent a domácnosť
- [ ] Fáza 4 – ADHD moduly
