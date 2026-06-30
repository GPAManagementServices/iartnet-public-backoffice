# ADR 0001 – Stack tecnologico

## Stato

Accettato.

## Contesto

Il backoffice IARTNET (Brera) deve permettere la gestione di contenuti multilingue, media e relazioni complesse (attività, istituzioni, persone, progetti, pagine, FAQ, research catalogue), con API pubbliche in lettura per frontend/servizi esterni.

## Decisione

- **Framework:** Laravel (PHP 8.2+).
- **Admin panel:** Filament 3.
- **Media:** Filament Curator (awcodes/filament-curator).
- **Traduzioni:** Spatie Laravel Translatable.
- **API:** REST sotto `/api/v1`, JSON, risorse Laravel (Resource classes), paginazione e filtro per status/slug/locale dove necessario.
- **Database:** supporto SQLite (sviluppo), MySQL/MariaDB o PostgreSQL (produzione).
- **Frontend asset:** Vite, Tailwind CSS per tema Filament e eventuali estensioni.

## Conseguenze

- Il codice segue le convenzioni Laravel e Filament; estensioni e policy sono allineate a questo stack.
- Modifiche allo stack (es. cambio ORM o rimozione Filament) richiedono un nuovo ADR e piano di migrazione.
