# Come contribuire

Grazie per l’interesse a contribuire a IARTNET Backoffice. Questa guida descrive come proporre modifiche in modo tracciabile e reviewabile.

## Flusso di lavoro

1. **Fork** del repository (se non hai accesso in scrittura).
2. Crea un **branch** a partire da `main` (es. `feat/nuova-funzione`, `fix/correzione-api`).
3. Apporta le modifiche e **testa** in locale (`composer test`).
4. Apri una **Pull Request** verso `main` con la descrizione richiesta sotto.

## Cosa deve contenere una PR

Ogni Pull Request deve includere:

- **Riferimento** a requisito / user story / ticket (o almeno un ID interno).
- **Elenco dei cambi** principali (file o aree toccate).
- **Test aggiunti** (se applicabile) e **come verificare** il comportamento (passi manuali o comandi).
- **Migrazioni:** se la PR introduce o modifica migrazioni:
  - Istruzioni di **rollout** (come applicare in ambiente).
  - Istruzioni di **rollback** (come annullare in sicurezza).
- **Comportamento:** se cambia il comportamento visibile (API, UI, permessi):
  - **Criteri di accettazione** aggiornati o indicati in descrizione.

## Commit e messaggi

- Usa **Conventional Commits**: `feat/`, `fix/`, `refactor/`, `test/`, `chore/` con messaggio descrittivo.
- Esempi:
  - `feat(api): filtro persone per istituzione`
  - `fix(admin): validazione slug su Page`
  - `chore(deps): aggiornamento Filament a 3.x`

## Formattazione codice (Pint)

Il progetto usa [Laravel Pint](https://laravel.com/docs/pint) con il preset `laravel` (configurazione in `pint.json`).

- **Applicare lo stile:** `composer pint` (o `./vendor/bin/pint`)
- **Solo controllo (nessuna modifica):** `composer pint:check` (o `./vendor/bin/pint --test`)

Consigliato eseguire `composer pint` prima di ogni commit; la CI esegue `pint --test` per verificare che il codice sia formattato.

## Verifiche prima della PR

- **`composer.lock`** deve essere committato (install riproducibili e CI); se manca, eseguire `composer update` e aggiungere il file.
- `composer install` e `composer test` devono passare.
- `composer pint:check` (opzionale ma consigliato).
- Nessun segreto, password o chiavi nel codice (solo configurazione via `.env` / env).

## Documentazione utile

- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) – codice di condotta.
- [SECURITY.md](SECURITY.md) – come segnalare vulnerabilità.

---

## English

To contribute: fork the repo, create a branch from `main`, make your changes, run `composer test`, and open a Pull Request. Each PR should reference a ticket/user story, list main changes, describe tests and how to verify; for migrations, include rollout and rollback instructions. Use [Conventional Commits](https://www.conventionalcommits.org/) (e.g. `feat:`, `fix:`, `chore:`). See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) and [SECURITY.md](SECURITY.md).
