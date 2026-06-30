# Activity attachments — contratto API / Filament (allineamento frontend Nuxt)

Documento di riferimento per API Laravel e pannello Filament rispetto al frontend Nuxt/Vue.

## Contesto

Il frontend **usa esclusivamente** il campo **`attachments`** (array) sulla risorsa Activity.

- Il payload JSON **non include** il campo legacy **`attachment`** (path singolo): è stato rimosso dall’API; non esiste fallback lato backend tra i due.
- L’etichetta in UI e nel titolo del dialog PDF segue questa priorità (implementazione Nuxt):
  1. `attachments[].title` se valorizzato (trim non vuoto),
  2. altrimenti **nome file** da `path` (ultimo segmento dopo `/`, URI-decoded),
  3. altrimenti ultimo segmento dell’`url`,
  4. altrimenti stringa generica `"Document"`.

## Contratto (`GET /api/v1/activities/{id}`, `GET /api/v1/activities/by-slug/{slug}`, e voci in lista paginata)

- **`attachments`**: array (può essere vuoto). Ogni elemento:

| Campo       | Tipo           | Obbligatorio | Note |
|-------------|----------------|--------------|------|
| `id`        | int            | sì           | Progressivo 1…n nell’ordine salvato (chiavi Vue nella singola risposta) |
| `url`       | string (URL)   | sì           | URL **assoluta** (`APP_URL` + disk `public`); in produzione deve essere dominio pubblico HTTPS raggiungibile dal browser |
| `path`      | string         | sì           | Path storage relativo (es. `activities/attachments/foo.pdf`) — fallback nome file in UI |
| `title`     | string \| null | no           | Titolo editoriale; `null` se assente o vuoto in CMS |
| `mimeType`  | string         | sì           | Sempre valorizzato; per PDF tipicamente `application/pdf` (file su disco, `mime_type` salvato, o inferenza da estensione `.pdf`) |

Implementazione backend: `App\Http\Resources\ActivityResource`, `App\Support\ActivityAttachmentsPresenter`.

## Requisiti infrastrutturali

1. **Accesso HTTP (evitare 403 su `/storage/...`)**  
   Gli upload su disk `public` sono sotto `storage/app/public` e serviti come `GET /storage/...` tramite symlink `public/storage` → `storage/app/public` (`php artisan storage:link`).  
   Se il web server risponde **403** su `GET /storage/activities/attachments/…`, PDF.js nel browser non caricherà il file. Verificare permessi, `location` Nginx/Apache e assenza di regole che bloccano la directory.

2. **CORS**  
   Se il PDF è servito da **host diverso** dal sito Nuxt, la risposta `GET` dell’asset deve consentire l’origine del frontend (oppure usare **stesso dominio** / reverse proxy verso il CMS).

3. **Content-Type**  
   Per i PDF serviti come file statici, il server dovrebbe rispondere con `Content-Type: application/pdf` (coerente con ciò che il client si aspetta da `mimeType` nel JSON).

4. **Sicurezza**  
   Se in futuro gli allegati non devono essere pubblici, introdurre URL firmati o route autenticate; evitare di esporre path interni non pubblicabili nell’`url` del JSON.

## Filament (backoffice)

- Sezione **Attachments**: `Repeater` con `FileUpload` (`path`, disk `public`, directory `activities/attachments`) + `title` opzionale.
- Non usare più il campo DB singolo `attachment` (migrato in colonna JSON `attachments`).

## Verifica rapida (QA)

- Activity senza allegati o `attachments: []` → nessun blocco “Documenti” in pagina dettaglio (frontend).
- Activity con 2+ PDF → carosello + dialog per ciascuno.
- PDF con `title: null` → in UI compare il nome file da `path` / `url`.

## Riferimenti codice (repo)

| Area        | Percorso |
|------------|----------|
| Serializzazione API | `app/Support/ActivityAttachmentsPresenter.php` |
| Risorsa Activity    | `app/Http/Resources/ActivityResource.php` |
| Form Filament       | `app/Filament/Resources/ActivityResource.php` (sezione Attachments) |
| Modello             | `app/Models/Activity.php` (`attachments` JSON) |

Riferimento implementazione frontend (altro repo): `ActivityAttachmentsSection.client.vue`, `shared/utils/activityAttachment.ts`, tipo `ActivityAttachment` in `shared/types/api.ts`.
