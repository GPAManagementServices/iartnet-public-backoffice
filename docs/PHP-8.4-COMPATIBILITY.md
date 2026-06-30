# Verifica compatibilità PHP 8.4

## Esito

**Il codice del progetto è compatibile con PHP 8.4.** Nessuna modifica al codice applicativo è necessaria per il passaggio da PHP 8.2/8.3 a 8.4.

## Controlli effettuati

### 1. Parametri implicitamente nullable (deprecato in 8.4)

In PHP 8.4 è deprecato dichiarare un parametro con tipo non nullable e default `null` (es. `function foo(string $a = null)`). Va usato `?string $a = null` o `string|null $a = null`.

- **Nel progetto:** Tutti i parametri nullable usano già la forma esplicita `?Type` (es. `?Model $record`, `?string $altOverride = null`). Nessuna modifica richiesta.

### 2. Altre deprecazioni PHP 8.4

- **exit()/die() con strict_types:** Non usati nel codice applicativo in modo sensibile a tipo.
- **E_STRICT / trigger_error(E_USER_ERROR):** Non utilizzati.
- **Raising zero to negative power (0 ** -n):** Non utilizzato.
- **Classe nominata `_`:** Non utilizzata.
- **lcg_value(), DatePeriod(string), session_sid_*, ReflectionMethod single-arg, ecc.:** Non utilizzati in `app/` o `config/`.

### 3. Dipendenze

- **Laravel 12:** Supporta PHP 8.2–8.5 ([support policy](https://laravel.com/docs/releases#support-policy)); PHP 8.4 è supportato.
- **Filament 3.x, Spatie Translatable, Filament Curator:** Compatibili con PHP 8.4 (stack Laravel 12).

### 4. Configurazione

- **Session:** Laravel gestisce la sessione; non sono usate direttamente le funzioni/opzioni deprecate in PHP 8.4 (`session.sid_length`, `session_set_save_handler` con >2 argomenti, ecc.).

## Aggiornamento effettuato

- `composer.json`: `require.php` impostato a `^8.4`.
- CI (`.github/workflows/tests.yml`): matrice PHP aggiornata a `8.4` (e opzionalmente 8.2/8.3 se si vuole mantenere compatibilità).

## Dopo l’aggiornamento

1. Eseguire in locale (con PHP 8.4): `composer update --no-interaction`.
2. Eseguire test e lint: `composer test`, `composer pint:check`.
3. Verificare in staging che login, API e pannello Filament funzionino correttamente.
