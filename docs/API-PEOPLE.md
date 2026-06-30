# API People (`/api/v1/people`)

Documentazione allineata al codice attuale (`PersonController`, `PersonResource`, `PeoplePageGroupedResponseBuilder`, `PeoplePageRoleCatalog`, `config/people_page.php`, catalogo DB `people_roles`). Dettaglio catalogo: [PEOPLE-ROLES-CATALOG.md](PEOPLE-ROLES-CATALOG.md).

---

## Endpoint

| Metodo | Path | Descrizione |
|--------|------|-------------|
| `GET` | `/api/v1/people` | Lista persone (piatta o raggruppata) |
| `GET` | `/api/v1/people/{id}` | Dettaglio per ID numerico |
| `GET` | `/api/v1/people/by-slug/{slug}` | Dettaglio per slug nella `locale` richiesta |
| `GET` | `/api/v1/institutions/{institution}/people` | Persone collegate a un’istituzione (ID numerico **o** slug nella `locale`) |

Base URL tipica: `{APP_URL}/api/v1/...`.

---

## Parametri query comuni

| Parametro | Default | Note |
|-----------|---------|------|
| `locale` | `app()->getLocale()` | Lingua per campi tradotti (slug, nomi, ruoli, ecc.). |
| `status` | `published` | Filtra `people.status`. Valore vuoto = nessun filtro su status (solo dove il controller lo applica: verificare i singoli endpoint). |
| `light` | `false` | Se `true`, in `PersonResource` vengono omessi `shortbio`, `meta.description` e relative voci in `translations` (payload più leggero). |

### Paginazione (solo liste **non** `grouped`)

| Parametro | Comportamento |
|-----------|----------------|
| `paginate` | Se `false` → **nessuna** paginazione (tutti i record della query). |
| `all` | Se `true` → **nessuna** paginazione (equivalente pratico a “tutto”). |
| `per_page` | Dimensione pagina (default **20**, massimo **200**). |

Se non si disattiva la paginazione, la risposta segue il formato Laravel paginato: `data`, `links`, `meta`.

**Nota:** con `grouped=1` la risposta **non** è paginata dal controller (sempre JSON “pieno” della struttura raggruppata).

---

## Lista piatta: `GET /api/v1/people`

### Filtri opzionali

| Parametro | Effetto |
|-----------|---------|
| `category_id` | Persone con categoria `person` con quell’ID. |
| `category_slug` | Persone con categoria `person` il cui slug JSON coincide con il valore nella `locale` richiesta. |
| `institution_id` | Persone che hanno l’ID in **`institution_roles[].institution_id`** (JSON). *Non* usa da sola il campo legacy `institutions` (array di ID). |
| `institution_slug` | Risolve l’istituzione per `slug` nella `locale`; se non esiste → lista vuota. Poi stesso criterio di `institution_id` su `institution_roles`. |
| `role` | Vedi sezione [Filtro `role`](#filtro-role). |

Ordinamento: `id` crescente.

---

## Vista raggruppata (People page): `GET /api/v1/people?grouped=1`

Risposta **JSON custom** (non collezione paginata), pensata per la pagina People.

### Struttura top-level

```json
{
  "academic_coordinator": [ /* array di oggetti Person (stesso schema PersonResource) */ ],
  "research_unit_leads": [
    {
      "institution": { /* InstitutionResource */ },
      "person": { /* PersonResource */ }
    }
  ],
  "general_coordination": [ /* PersonResource[] */ ],
  "institutions": [
    {
      "institution": { /* InstitutionResource */ },
      "sections": {
        "<section_slug>": [ /* PersonResource[] */ ],
        "no_role": [ /* … */ ]
      }
    }
  ]
}
```

### Regole di business (sintesi)

- **`academic_coordinator`:** persone il cui `role` globale (JSON) corrisponde esattamente (nella `locale`) all’etichetta configurata per `academic_coordinator` in `people_page.global_roles`.
- **`research_unit_leads`:** coppie istituzione + persona per ruolo *Research Unit Lead* (match su etichette config globali / di sezione come da `PeoplePageGroupedResponseBuilder`).
- **`general_coordination`:** persone con almeno una riga in `institution_roles` verso l’istituzione il cui slug nella **`locale` della richiesta** coincide con `config('people_page.iartnet_institution_slug')` (default `iartnet`, sovrascrivibile con env `PEOPLE_PAGE_IARTNET_SLUG`).  
  Quelle persone **non** vengono duplicate nelle sezioni per-istituzione né in `research_unit_leads` nel builder.
- **`institutions`:** altre istituzioni (esclusa IartNET come sopra), ciascuna con `sections` ordinate secondo le chiavi in `dedicated_section_roles`, poi `institution_section_roles`, infine `no_role`.  
  Dentro ogni sezione le persone sono ordinate per **cognome** (`last_name`) nella `locale`.

### Ordinamento istituzioni (senza `sort_order` in DB)

1. Chiave lessicografica: `slug_en` → altrimenti `slug_it` → altrimenti slug tradotto (en → it → locale).  
2. Tie-break: nome istituzione in minuscolo nella `locale` richiesta.

Stesso criterio per l’ordine delle voci in `research_unit_leads`.

I filtri `category_id`, `category_slug`, `institution_id`, `institution_slug`, `role` si applicano anche a `grouped=1` (stessa query base del builder).

---

## `GET /api/v1/institutions/{institution}/people`

- `{institution}`: **ID numerico** oppure **slug** dell’istituzione nella `locale` della query.
- Include persone con:
  - `institutions` (array JSON di ID) che contiene l’istituzione, **oppure**
  - `institution_roles` che referenzia quell’`institution_id`.
- `status` default `published`.
- Paginazione: stesse regole `paginate` / `all` / `per_page` della lista piatta.

---

## Dettaglio persona

- **`GET /api/v1/people/{id}`** — nessun filtro `status` sul controller (restituisce il record o 404).
- **`GET /api/v1/people/by-slug/{slug}`** — filtra `status` (default `published`).

---

## Filtro `role`

`GET /api/v1/people?role=<chiave>`

### Chiavi ammesse

Tutte le chiavi definite in `config/people_page.php` in:

- `global_roles`
- `dedicated_section_roles`
- `institution_section_roles`

**esclusa** la chiave `no_role` (`no_role_section_slug`), che **non** è filtrabile via query.

Esempi di chiavi: `academic_coordinator`, `research_unit_lead`, `general_advisor`, `research_group_coordinator`, `project_staff`, `research_staff`, … (l’elenco completo dipende dalla config deployata).

### Comportamento SQL (`PeoplePageRoleCatalog::applyRoleFilterToPeopleQuery`)

- **`research_unit_lead`:** match se una delle etichette esatte (tutte le lingue derivate dalla config per quella chiave) compare in **`role`** **oppure** in **`institution_roles`** (MySQL `JSON_SEARCH`).
- **`academic_coordinator`:** solo su **`role`** (etichette esatte da config).
- **Altri ruoli:** solo su **`institution_roles`** (etichette esatte; classificazione allineata alle sezioni).

### Errori

- `role` non in whitelist → **422** (validazione Laravel).

---

## Payload `PersonResource` (retrocompatibilità)

Campi principali:

- Identità: `id`, `first_name`, `last_name`, `slug`, `status`, `role` (ruolo globale nella `locale`), `email`, `shortbio` (se non `light`).
- **Catalogo ruoli (campi aggiuntivi, retrocompatibili):**
  - `people_role_id` — intero o `null` (FK verso `people_roles`).
  - `people_role` — `{ id, slug, name_en, name_it }` quando la relazione `peopleRole` è eager-loadata (default negli endpoint People); se non caricata, la chiave **non** compare (`whenLoaded`).
- Media: `image_id`, `opengraph_picture_id`, oggetto `media` con `image` e `opengraph_picture` (oggetti Curator: `id`, `url`, **`path`** per `GET /api/v1/media/sign`, `alt`, `title`, `caption`, `description`, dimensioni, `type`, `name`).
- `categories` (se relazione caricata): `id`, `name`, `slug`, `type`.
- **`institutions`:** derivato da `institution_roles`, non dal solo elenco ID legacy. Per ogni voce:
  - `id`, `name`, `slug`, `status`
  - `role`, `role_translations` (dal JSON salvato)
  - **`role_key`**: slug stabile da catalogo (`PeoplePageRoleCatalog::classifyInstitutionRoleTranslations`)
  - **`role_label_en`**: etichetta inglese canonica dalla config se il ruolo è riconosciuto; altrimenti coerente con `no_role`
- `institution_roles`: JSON grezzo come in DB (array di righe `{ institution_id, people_role_id?, role: { en, it, ... } }`).
- `meta`: titoli/descrizioni SEO/OpenGraph (rispettando `light`).
- `created_at`, `updated_at` (ISO8601).
- `translations`: mappa delle traduzioni per i campi esposti (filtrata se `light`).

---

## Payload `InstitutionResource` (in `grouped` / `research_unit_leads`)

Oltre a `name`, `slug`, `status`, `website`, `description`, media, categorie (se caricate), sono esposti:

- **`slug_en`**, **`slug_it`**: colonne dedicate sul modello.
- **`display_sort_key`**: stringa usata per ordinamento stabile (slug EN → IT → fallback slug tradotto).

---

## Esempi

```http
# Lista piatta, tutte le pubblicate, locale inglese, senza paginazione
GET /api/v1/people?paginate=false&locale=en

# Filtro per ruolo sezione
GET /api/v1/people?locale=it&role=project_staff

# Vista raggruppata + filtro ruolo
GET /api/v1/people?grouped=1&locale=it&role=research_unit_lead

# Persone di un’istituzione per slug
GET /api/v1/institutions/miur/people?locale=it
```

---

## Configurazione rilevante

| Chiave / env | Ruolo |
|--------------|--------|
| `people_page.php` | Etichette ruoli globali, di sezione dedicata, per istituzione; slug `no_role`; slug istituzione IartNET. Usata dal seed iniziale `people_roles` e dalla classificazione/filtri basati su label. |
| `PEOPLE_PAGE_IARTNET_SLUG` | Override slug istituzione per `general_coordination` (default `iartnet`). |
| `people_roles` (tabella) | Catalogo editabile in Filament; in salvataggio persona allinea le label nel JSON `role` / `institution_roles[].role`. |

---

## Note implementative

- I filtri su `institution_id` / `institution_slug` nella **lista principale** usano **`institution_roles`**, non il campo `institutions` (legacy).
- Il filtro `role` si basa su **match esatto** delle stringhe nelle traduzioni rispetto alle etichette in config (trim); varianti ortografiche non matchano.
- Database: query `JSON_SEARCH` su JSON — ambiente atteso **MySQL/MariaDB** (come da implementazione attuale).
