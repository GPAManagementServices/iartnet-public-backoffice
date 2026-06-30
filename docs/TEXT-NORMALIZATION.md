# Normalizzazione testo al salvataggio

## Comportamento

- **`App\Support\TextNormalizer`**: `trim()` PHP (ASCII whitespace) + rimozione `\p{Zs}` ai bordi (spazi Unicode, es. NBSP). Idempotente.
- **`App\Models\Concerns\NormalizesTextOnSave`**: trait applicato ai modelli content; il callback `saving` del trait viene registrato **prima** di `booted()` del modello, così titoli/nomi sono puliti **prima** di `Str::slug` e logica esistente.

## Cosa viene normalizzato

1. **Attributi Spatie `translatable`**: ogni valore stringa per locale.
2. **Scalari opzionali** (`scalarOptionalTextAttributes`): stringa vuota dopo normalizzazione → `null`.
3. **Scalari obbligatori** (`scalarRequiredTextAttributes`): trim + Zs; `''` resta `''`.
4. **JSON/array** (`jsonArrayTextNormalizableAttributes`): ricorsione sulle **sole foglie stringa**; numeri, chiavi e struttura invariati.

## Modelli

| Modello            | JSON/array normalizzati      | Scalari opzionali      | Scalari obbligatori        |
|--------------------|-----------------------------|-------------------------|----------------------------|
| Activity           | `people` (label per lingua), `attachments` (titoli), `gallery` (didascalie IT/EN per slide); **`video_urls`**: lista URL (trim, dedup) via `App\Support\ActivityVideoUrls` + sync colonna legacy `video_url` | — | —                        |
| Institution        | —                           | `website`               | —                          |
| Person             | `institution_roles`         | `email`, `website`      | —                          |
| Project            | `people`, `gallery` (didascalie IT/EN per slide) | —                       | —                          |
| Page               | —                           | —                       | `slug_it`, `slug_en`       |
| Faq                | —                           | —                       | `slug_it`, `slug_en`, `status` |
| ResearchCatalogue  | —                           | `external_link`, `author` | `slug_it`, `slug_en`, `status` |
| Category           | —                           | —                       | `type`, `status`           |
| User               | —                           | `email`                 | `name`, `role`             |

Per nuovi modelli con testo: aggiungere `use NormalizesTextOnSave` e, se serve, override dei tre metodi di configurazione.

## Test

```bash
php vendor/bin/phpunit tests/Unit/TextNormalizerTest.php
```
