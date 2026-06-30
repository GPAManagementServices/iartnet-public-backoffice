# Checklist OSS hardening (sezione E)

## E1 – Verifica sicurezza e coerenza

- [x] **.env non tracciato:** `git ls-files .env` non restituisce nulla → il file `.env` non è versionato (corretto).
- [x] **Segreti:** configurazione tramite `env()` in `config/`; nessun segreto hardcoded nel codice.
- [x] **Deploy:** il workflow usa solo `secrets.*` (FTP), nessun valore in chiaro.

Se in passato `.env` fosse stato committato, eseguire `git log -- .env` e, in caso positivo, pianificare rotazione di `APP_KEY` e rimozione dalla history.

- **composer.lock:** tenere in repo per install e CI riproducibili; in assenza, la CI può mostrare warning e risolvere dipendenze diverse.
- **artisan / progetto completo:** la CI richiede che in repo ci siano `artisan`, `app/`, `config/`, ecc.; altrimenti `composer install` fallisce in post-autoload-dump.

## E2 – Branch e strategia commit

**Branch proposta:** `chore/oss-hardening`

**Strategia:** 4 commit per macro-sezione (A, B, C, D). La sezione E è solo verifica (nessun commit aggiuntivo).

| Commit | File | Messaggio (Conventional Commits) |
|--------|------|-----------------------------------|
| **A** | README.md, LICENSE, CONTRIBUTING.md, CODE_OF_CONDUCT.md, SECURITY.md, CHANGELOG.md, .gitattributes | `docs: add OSS docs and project identity (README, LICENSE, CONTRIBUTING, CoC, SECURITY, CHANGELOG)` |
| **B** | composer.json, .env.example | `chore: set package identity and env.example (brera/iartnet-backoffice, .env)` |
| **C** | pint.json, composer.json (script pint), CONTRIBUTING.md (sezione Pint), .github/workflows/tests.yml | `chore: add Pint and CI workflow (tests + lint)` |
| **D** | tests/Feature/ExampleTest.php, docs/README.md, docs/adr/0001-stack-tecnologico.md, docs/OSS-HARDENING-CHECKLIST.md | `test(docs): meaningful feature tests and docs/ with ADR` |

**Nota:** eseguire i commit nell’ordine A → B → C → D (alcuni file sono modificati in più sezioni). Prima di committare: `composer test` e `composer pint:check`.
