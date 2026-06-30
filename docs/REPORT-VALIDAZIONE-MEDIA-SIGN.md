# Report di Validazione e Collaudo — Endpoint Media Sign (Glide/Curator)

**Riferimento:** Refactoring architetturale delegazione manipolazione immagini al frontend Nuxt  
**Data:** 2025-03-12  
**Ruolo:** QA Engineer & Security Auditor Laravel  

---

## 1. Obiettivi Raggiunti

| Obiettivo | Stato | Dettaglio |
|-----------|--------|-----------|
| **Disaccoppiamento frontend/backend** | ✅ | Il backend espone solo `GET /api/v1/media/sign`: riceve `path` + parametri Glide, restituisce URL firmata. Nessuna elaborazione pixel lato server; Nuxt/Curator lato frontend gestiscono la richiesta all’URL firmata. |
| **Mitigazione OOM** | ✅ | Validazione `w` e `h` con `max:2500` in `MediaSignRequest`. Richieste con dimensioni >2500px vengono rifiutate con 422 prima di toccare Glide. |
| **Saturazione risorse** | ✅ | Il server Laravel non decodifica/ridimensiona immagini; la firma è operazione a basso costo (HMAC). Carico pesante resta sul servizio Glide/Curator, protetto dai limiti geometrici. |
| **Esposizione `path` nelle API** | ✅ | Le API Resources (Activity, Person, Institution, Page, Project, ResearchCatalogue) includono `'path' => $media->path` nella closure `$serializeMedia`, consentendo al frontend di chiamare `/media/sign` con il path senza dover inferirlo. |

**Benefici sintetici:**  
- Backend non espone più endpoint di trasformazione immagini; superficie di attacco e carico CPU/memoria ridotti.  
- Controllo centralizzato dei limiti (2500px, whitelist `fit`, whitelist opzionale `fm` per formato output) prima della generazione dell’URL firmata.  
- Flusso chiaro: API entità → `path` in JSON → Nuxt → `GET /api/v1/media/sign?path=...&w=...&fm=webp` (facoltativo) → URL firmata → richiesta a Curator/Glide.

---

## 2. Analisi di Sicurezza

### 2.1 APP_KEY non esposta

- **Implementazione:** In `App\Http\Controllers\Api\MediaSignController` la chiave è usata solo come argomento di `UrlBuilderFactory::create('/curator/', config('app.key'))`. La risposta JSON contiene **solo** `url` (stringa dell’URL firmata).
- **Verifica:** Nessun punto del controller/route restituisce `config('app.key')` o variabili d’ambiente; la chiave non è mai serializzata nella response.
- **Conclusione:** APP_KEY **non** è esposta al client.

### 2.2 Validazioni e vettori di attacco

| Vettore | Validazione attuale | Esito |
|--------|----------------------|--------|
| **Dimensioni eccessive (OOM)** | `w`, `h`: `nullable|integer|min:10|max:2500` | ✅ Richieste con `w`/`h` > 2500 (es. `w=10000`) → 422 Validation Exception. |
| **Parametri Glide non consentiti** | Solo `path`, `w`, `h`, `fit`, `fm` accettati da `MediaSignRequest`; `$glideParams` con `$validated->except('path')->filter()` | ✅ Parametri aggiuntivi non passati al builder. |
| **Valori `fit` arbitrari** | `fit`: `nullable|string|in:contain,max,fill,stretch,crop` | ✅ Solo valori whitelist. |
| **Formato output (`fm`)** | `fm`: `nullable|string|in:webp,png,jpg` (parametro Glide) | ✅ Solo formati whitelist; assente → default Glide/Curator. |
| **Path mancante** | `path`: `required|string` | ✅ Assente → 422. |
| **Path traversal / path non Curator** | `App\Rules\MediaCuratorPathRule`: prefisso obbligatorio `media/`, vietato `..` e `\`, solo `[A-Za-z0-9_\-\.\/]` dopo il prefisso, `rawurldecode` prima del check | ✅ 422; tentativi con `..` o `\` generano `Log::warning('media.sign.suspicious_path', …)` **senza** URL firmata né token in log. |
| **Rate limiting** | Middleware `throttle:media-sign`; `RateLimiter::for('media-sign')` in `AppServiceProvider` con `config('media.sign_max_attempts_per_minute')` (default 120/min per IP, env `MEDIA_SIGN_MAX_ATTEMPTS_PER_MINUTE`) | ✅ Oltre il limite → **429**. |

### 2.3 Logging

- Eventi **strutturati** solo per path sospetti (`path_traversal`, `backslash`) con chiavi `reason` e `path_preview` (troncato); **non** si loggano URL complete con firma Glide.

### 2.4 Riepilogo sicurezza

- **Confermato:** APP_KEY non esposta; limiti geometrici; whitelist `fit`/`fm`; path ristretto a `media/…`; throttle per IP su endpoint pubblico.
- **Documentazione contratto API:** `docs/API-MEDIA-SIGN.md`.

---

## 3. Piano di Collaudo (Test & Benchmark)

### 3.1 Variabili d’ambiente per i comandi

Sostituire nei comandi (in PowerShell: `$env:BASE_URL`, in bash: `$BASE_URL`):

- `BASE_URL` = base dell’API (es. `https://backoffice.iartnet.local` o `http://localhost`)
- `PATH_MEDIA` = path reale di un media (es. `media/2024/01/example.jpg`), ottenuto da una response di un’entità (es. `GET /api/v1/activities/1` → `media.cover_image.path`).

---

### 3.2 cURL — Richiesta valida (simulazione Nuxt)

```bash
# Richiesta valida: path + w e h entro 2500 (sostituire BASE_URL)
curl -s -w "\n\nHTTP_CODE:%{http_code}\nTIME_TTFB_MS:%{time_starttransfer}\nTIME_TOTAL_MS:%{time_total}\n" \
  "${BASE_URL}/api/v1/media/sign?path=media/2024/01/example.jpg&w=800&h=600&fit=crop" \
  -o response_valid.json
```

**Risultato atteso:**  
- `HTTP_CODE:200`  
- Body JSON: `{"url":"https://.../curator/media/2024/01/example.jpg?w=800&h=600&fit=crop&s=..."}` (con parametro di firma `s`).  
- Registrare `TIME_TTFB_MS` e `TIME_TOTAL_MS` come baseline.

---

### 3.3 cURL — Richiesta malevola (w=10000)

```bash
# Attacco dimensioni: w=10000 oltre il limite 2500 (sostituire BASE_URL)
curl -s -w "\n\nHTTP_CODE:%{http_code}\nTIME_TTFB_MS:%{time_starttransfer}\nTIME_TOTAL_MS:%{time_total}\n" \
  "${BASE_URL}/api/v1/media/sign?path=media/2024/01/example.jpg&w=10000&h=500" \
  -o response_malicious_w.json
```

**Risultato atteso:**  
- `HTTP_CODE:422`  
- Body: messaggi di validazione Laravel (es. `The w field must not be greater than 2500.`)  
- **Nessuna** URL firmata generata.

---

### 3.4 cURL — Altri casi da registrare

**Path assente (422):**
```bash
curl -s -w "\nHTTP_CODE:%{http_code}\nTIME_TTFB_MS:%{time_starttransfer}\n" \
  "${BASE_URL}/api/v1/media/sign?w=800&h=600" \
  -o response_no_path.json
```

**Fit non consentito (422):**
```bash
curl -s -w "\nHTTP_CODE:%{http_code}\nTIME_TTFB_MS:%{time_starttransfer}\n" \
  "${BASE_URL}/api/v1/media/sign?path=media/2024/01/example.jpg&fit=invalid" \
  -o response_invalid_fit.json
```

---

### 3.5 Misurazione e storicizzazione TTFB/tempi

- **TTFB:** usare `%{time_starttransfer}` in `-w` (secondi; moltiplicare per 1000 per ms).  
- **Tempo totale:** `%{time_total}`.  
- **Storicizzazione:** eseguire più volte (es. 5–10) in condizioni simili e registrare in un foglio o CSV:

  - Endpoint: `GET /api/v1/media/sign`
  - Data, ambiente (local/staging)
  - Caso (valido / malevolo w=10000 / path assente / fit invalido)
  - HTTP_CODE, TIME_TTFB_MS, TIME_TOTAL_MS

Questo permette confronti futuri dopo tuning PHP/OPcache/rete.

**Esempio one-liner per estrarre solo TTFB (ms) e status dopo la chiamata:**
```bash
# Dopo aver eseguito curl con -o response_valid.json, i metrici sono su stdout
# Per salvare in file di log (PowerShell):
# $out = curl ... 2>&1; $out | Out-File -Append media_sign_benchmark.log
```

---

### 3.6 Checklist collaudo

- [ ] Richiesta valida (path + w, h ≤ 2500, fit consentito) → 200, JSON con `url` firmata.  
- [ ] `w=10000` (o h=10000) → 422, nessuna URL in response.  
- [ ] `path` assente → 422.  
- [ ] `fit` non in whitelist → 422.  
- [ ] `path` con `..` o senza prefisso `media/` → 422.  
- [ ] Superato il rate limit → 429.  
- [ ] Test automatici: `php artisan test --filter=MediaSignTest`.  
- [ ] TTFB e time_total registrati per richiesta valida e per 422 (per confronti futuri).

---

*Fine report.*
