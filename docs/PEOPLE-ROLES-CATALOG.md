# Catalogo ruoli persone (`people_roles`)

Ruoli gestiti in **database** (CRUD Filament) e referenziati dalle persone; le etichette **EN/IT** restano anche nel JSON `people.role` e `people.institution_roles[].role` per compatibilità con filtri e classificazione basate su stringhe (`PeoplePageRoleCatalog`, `config/people_page.php`).

## Database

| Tabella / colonna | Descrizione |
|-------------------|-------------|
| `people_roles` | `slug` (unique), `name_en`, `name_it`, `sort_order`, timestamps |
| `people.people_role_id` | FK nullable → `people_roles` (ruolo globale della persona) |
| `people.institution_roles` | JSON: righe `{ institution_id, people_role_id?, role: { en, it } }` |

### Migration (ordine)

1. `2026_03_14_140000_create_people_roles_table.php` — crea `people_roles`
2. `2026_03_14_140100_add_people_role_id_to_people_table.php` — aggiunge FK su `people`
3. `2026_03_14_140200_seed_people_roles_and_backfill_people.php` — seed da `config/people_page.php` + backfill FK/JSON dove possibile

Opzionale (bonifica traduzioni da config, prima del seed se già in uso):

- `2026_03_13_120000_bonify_people_role_translations_from_config.php`

**Rollback:** ordine inverso delle migration; il `down()` della seed azzera `people.people_role_id` e rimuove le righe del catalogo (evitare `TRUNCATE` con FK attive).

## Backoffice Filament

- **Content → People roles** (`/admin/people-roles`): lista, creazione, modifica.
- **Eliminazione:** disabilitata se il ruolo è in uso (`people.people_role_id` o riferimento in `institution_roles` JSON).
- **People:** select “Global role (catalog)” e, nel repeater istituzioni, “Role (catalog)”; al salvataggio il JSON `role` si allinea al catalogo quando è impostato un `people_role_id`.

## API

Oltre ai campi in [API-PEOPLE.md](API-PEOPLE.md), con eager-load standard dei controller:

- `people_role_id` — ID nel catalogo o `null`
- `people_role` — `{ id, slug, name_en, name_it }` se la relazione è caricata; omesso se non caricata (`whenLoaded`)

Le righe in `institution_roles` possono includere `people_role_id` oltre a `role`.

## Verifica

```bash
php artisan migrate:status
# Docker:
docker compose exec app php artisan migrate
```

---

*Implementazione: `App\Models\PeopleRole`, `App\Models\Person` (evento `saving`), `App\Filament\Resources\PeopleRoleResource`, `App\Http\Resources\PersonResource`.*
