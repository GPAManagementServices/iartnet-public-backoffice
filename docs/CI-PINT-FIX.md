# Far passare la CI: Pint (stile codice)

Se la CI fallisce sullo step **Pint (stile codice)** (`./vendor/bin/pint --test`), significa che nel repo ci sono file che non rispettano le regole di stile del preset Laravel (ordered_imports, no_unused_imports, braces, ecc.).

## Fix in locale (una tantum)

Dalla **root del repo** (dove si trovano `composer.json` e `artisan`):

1. **Applica lo stile** (modifica i file):
   ```bash
   composer pint
   ```
   Su Windows PowerShell puoi usare: `.\run-pint.ps1`

2. **Verifica** che tutto sia a posto:
   ```bash
   composer pint:check
   ```
   L’output deve essere senza errori.

3. **Committa solo le modifiche di stile**:
   ```bash
   git add -A
   git status   # controlla che ci siano solo file PHP/config toccati da Pint
   git commit -m "style: fix code style with pint"
   git push
   ```

Dopo il push, la CI dovrebbe essere verde (a parità di altri requisiti: artisan, composer.lock, test).

## Nota

- Le modifiche sono **solo di formattazione** (ordine import, rimozione import inutilizzati, spazi, parentesi); la logica non cambia.
- Se preferisci non versionare lo script: `run-pint.ps1` è opzionale; basta usare `composer pint` e `composer pint:check`.
