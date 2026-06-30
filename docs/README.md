# Documentazione progetto

Questa cartella contiene documentazione di architettura e decisioni di progetto.

## Contenuti

- **[adr/](adr/)** – Architecture Decision Records: decisioni architetturali rilevanti e loro contesto.
- **[API-PEOPLE.md](API-PEOPLE.md)** – endpoint e payload People (inclusi campi catalogo ruoli).
- **[PEOPLE-ROLES-CATALOG.md](PEOPLE-ROLES-CATALOG.md)** – tabella `people_roles`, Filament, migration, allineamento API.

## Struttura applicazione (sintesi)

- **Backoffice:** pannello Filament sotto `/admin` (risorse per Activity, Category, Faq, Institution, Page, Person, **People roles** (catalogo ruoli), Project, ResearchCatalogue, User).
- **API pubbliche:** prefisso `/api/v1`, lettura (index, show by id, show by slug) per le stesse entità; filtro `people` per istituzione.
- **Modelli:** contenuti multilingue (Spatie Translatable), media (Curator), categorizzazione polimorfica (categorizables), meta/SEO e OpenGraph.
- **Configurazione:** `.env` per segreti e ambiente; nessun segreto in repo.
