# IARTNET Backoffice

Backoffice per il progetto IARTNET (Brera): applicazione Laravel con pannello admin [Filament](https://filamentphp.com) e API REST. Gestione multilingue (IT/EN) di attività, istituzioni, persone, progetti, pagine, FAQ e research catalogue; categorizzazione polimorfica, media (cover, gallery, OpenGraph) tramite Curator, meta e SEO per le entità; API v1 con lettura per id e slug e filtro persone per istituzione.

## Requisiti

- PHP 8.4+
- Composer
- Node.js e npm
- Database: MariaDB 

## Installazione

```bash
git clone <url-repo> iartnet_backoffice && cd iartnet_backoffice
composer install
cp .env.example .env
php artisan key:generate
# Configurare .env con il proprio DB (DB_CONNECTION, DB_DATABASE, ecc.)
php artisan migrate
npm install
npm run build
```

## Avvio in sviluppo

```bash
composer dev
```

Oppure in modo separato: `php artisan serve`, `npm run dev`, e se serve queue/log.

## Comandi utili

| Comando | Descrizione |
|---------|-------------|
| `composer setup` | Installazione completa (composer, .env, key, migrate, npm, build) |
| `composer test` | Esegue i test PHPUnit |
| `php artisan filament:user` | Crea un utente admin Filament |
| `npm run build` | Build asset per produzione |

## API

Le API pubbliche sono sotto il prefisso `/api/v1` (es. `/api/v1/projects`, `/api/v1/activities`, `/api/v1/institutions`, `/api/v1/institutions/{id}/people`, …).

## Documentazione

- [docs/API-PEOPLE.md](docs/API-PEOPLE.md) – API People (`/api/v1/people`)
- [docs/PEOPLE-ROLES-CATALOG.md](docs/PEOPLE-ROLES-CATALOG.md) – catalogo ruoli persone (DB + Filament)
- [CONTRIBUTING.md](CONTRIBUTING.md) – come contribuire
- [SECURITY.md](SECURITY.md) – segnalazione vulnerabilità
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) – codice di condotta

## Licenza

Questo progetto è rilasciato sotto licenza [AGPL-3.0-or-later](LICENSE).

Licenze delle dipendenze Composer: [THIRD_PARTY.md](THIRD_PARTY.md).

---

## English

**IARTNET Backoffice** – Laravel + Filament admin panel and REST API for the IARTNET (Brera) project. Multilingual (IT/EN) content, categorisation, media (Curator), SEO meta and OpenGraph; API v1 with index, show by id/slug, and people by institution. See above for requirements, installation (`composer install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`, `npm install`, `npm run build`) and main commands (`composer dev`, `composer test`). Licensed under [AGPL-3.0-or-later](LICENSE).
