# API — Firma URL media (Glide / Curator)

Endpoint per ottenere un **URL firmato** verso `/curator/…` (League Glide + Awcodes Curator).  
La **conversione di formato** (es. WebP) e il **resize/crop** avvengono quando il browser (o il server Nuxt) esegue **GET sull’URL restituito**, non durante `media/sign`.

## `GET /api/v1/media/sign`

### Autenticazione

Nessuna (endpoint pubblico). È applicato **rate limiting** per IP (vedi sotto).

### Parametri query

| Parametro | Obbligatorio | Descrizione |
|-----------|--------------|-------------|
| `path` | **Sì** | Path del file sul disk Curator, normalizzato: deve iniziare con `media/` (slash iniziale opzionale). Caratteri ammessi: lettere, numeri, `_`, `-`, `.`, `/`. Non ammessi `..`, `\`, spazi o altri simboli. |
| `w` | No | Larghezza output (pixel), intero **10–2500**. |
| `h` | No | Altezza output (pixel), intero **10–2500**. |
| `fit` | No | Modalità Glide: `contain`, `max`, `fill`, `stretch`, `crop`. |
| `fm` | No | Formato output (whitelist): `webp`, `png`, `jpg`. Se omesso, usa il default del server Glide. |

Altri parametri Glide **non** sono accettati (non vengono inoltrati al builder).

### Rate limiting

- **Limite:** `MEDIA_SIGN_MAX_ATTEMPTS_PER_MINUTE` richieste per minuto per **IP** (default **120**).
- Risposta superato il limite: **429 Too Many Requests**.

### Risposta 200

```json
{
  "url": "https://esempio.it/curator/media/2024/01/file.jpg?s=…&w=640&fm=webp"
}
```

`url` è assoluta (`APP_URL` + path firmato). **Non** loggare né condividere come segreto: contiene la firma HMAC per Glide.

### Errori di validazione

**422 Unprocessable Entity** con body JSON standard Laravel (`message`, `errors`).

---

## Convenzione frontend (Nuxt)

1. Leggere `path` dal JSON delle API entità (es. `media.cover_image.path`).
2. Chiamare `GET /api/v1/media/sign` con `path`, `w`/`h`/`fit` e opzionalmente `fm=webp`.
3. Usare l’`url` restituito come `src` (o fetch) dell’immagine trasformata.

**WebP con fallback:** provare prima `fm=webp`; se la risposta del GET all’URL firmato fallisce (es. server senza WebP), ripetere `media/sign` **senza** `fm` o con `fm=jpg` e usare quell’URL.

---

## Esempi

### Solo path

```http
GET /api/v1/media/sign?path=media%2F2024%2F01%2Fhero.jpg
```

### Resize + crop + WebP

```http
GET /api/v1/media/sign?path=media%2F2024%2F01%2Fhero.jpg&w=1200&h=630&fit=crop&fm=webp
```

### cURL

```bash
curl -sG "https://BACKOFFICE/api/v1/media/sign" \
  --data-urlencode "path=media/2024/01/hero.jpg" \
  --data-urlencode "w=800" \
  --data-urlencode "fm=webp"
```

---

## Variabili d’ambiente

| Variabile | Default | Ruolo |
|-----------|---------|--------|
| `MEDIA_SIGN_MAX_ATTEMPTS_PER_MINUTE` | `120` | Throttle per IP su `media/sign` |

## Post-deploy (checklist)

- [ ] PHP con supporto **WebP** (e PNG/JPEG) dove viene eseguito Glide, se si usa `fm=webp`.
- [ ] `APP_KEY` stabile (invalida le URL già firmate se cambia).
- [ ] Valore throttle adeguato al traffico (CDN / caching sugli asset generati consigliato a valle).
