# People Page API – Requisito / specifica per l’agent

Specifica per l’implementazione del parametro **light**, del parametro **grouped** e della risposta raggruppata per `GET /api/v1/people`. Da usare come riferimento in Agent mode.

---

## Obiettivo

Implementare per l’endpoint `GET /api/v1/people`:

1. **Parametro light=1** (o light=true): risposta senza shortbio e meta.description (e relative traduzioni) per ridurre il payload in listing.
2. **Parametro grouped=1**: risposta già raggruppata e ordinata per la pagina PEOPLE del front-end, secondo le specifiche sotto.

Rispettare le decisioni già confermate (tabella decisioni).

---

## Regola: una persona in più sezioni/entry

**Confermato**: se una persona ha più ruoli nella stessa istituzione oppure è associata a più istituzioni, **può e deve** apparire in più sezioni/entry (es. in `research_unit_leads` per un’istituzione e nella stessa istituzione sotto un’altra sezione come "Research Staff", oppure in due istituzioni diverse). Non deduplicare per persona: ogni coppia (persona, istituzione, ruolo) genera la propria entry nella sezione appropriata.

---

## 1. Parametro light (PersonResource)

- **Nome**: `light`; valori accettati: `1` o `true` (via `$request->boolean('light')`).
- **Quando attivo**: in `PersonResource::toArray()` restituire **null** per le chiavi:
  - `shortbio`
  - `meta.description`
  - In `translations`: `shortbio`, `meta_description` (e rimuovere dal JSON le chiavi con valore null, es. con `array_filter(..., fn($v) => $v !== null)`).
- Le altre chiavi restano invariate.

---

## 2. Config people_page (nuovo file)

- **File**: `config/people_page.php`.
- **Contenuto**:
  - **iartnet_institution_slug**: valore da env `PEOPLE_PAGE_IARTNET_SLUG`, default `'iartnet'`. Usato per identificare l’istituzione "IartNET (progetto)".
  - **global_roles**: mappatura slug → label per locale (en/it), solo per ruoli "globali" usati in testa alla pagina:
    - `academic_coordinator`: en "Academic Coordinator", it "Coordinatore Scientifico"
    - `research_unit_lead`: en "Research Unit Lead", it "Responsabile Unità di Ricerca"
  - **institution_section_roles**: slug sezione → label en/it per le 5 sezioni standard (ordine chiavi = ordine visualizzazione):
    1. academic_team_members: en "Academic Team Member", it "Personale Docente del Team di Progetto"
    2. research_staff: en "Research Staff", it "Staff di Ricerca"
    3. project_staff: en "Project Staff", it "Staff di Progetto"
    4. student_collaborators: en "Student Collaborator", it "Studente Collaboratore"
    5. external_consultant: en "External Consultant", it "Consulente Esterno"
  - **dedicated_section_roles**: slug sezione → label en/it per i ruoli che hanno sezione dedicata (mostrate **per prime** nell’ordine sotto), usando i ruoli reali dal DB:
    - general_advisor: en "General Advisor", it "Consulente Scientifico di progetto"
    - research_coordinator_communication: en "Research Coordinator and Communication", it "Coordinamento Ricerca e Comunicazione"
    - digital_collections_curator: en "Digital Collections Curator", it "Curatrice Collezioni Digitali"
    - project_manager: en "Project Manager", it "Project Manager"
    - chief_information_officer: en "Chief Information Officer", it "Responsabile Sistemi Informatici"
    - research_office_manager: en "Research Office Manager", it "Responsabile Ufficio Ricerca"
  - **no_role**: chiave (es. `no_role`) per cui considerare "nessun ruolo" se il valore nella locale è null o la stringa `"null"` (trim), e mettere le persone in una sezione "Senza ruolo" / "No role".

- **Matching ruoli**: esatto dopo **trim** sul valore nella locale della request (en/it). Nessun case-insensitive.

---

## 3. Servizio PeoplePageGroupedResponseBuilder

- **Classe**: `App\Services\PeoplePageGroupedResponseBuilder`.
- **Metodo**: `build(Request $request): array`.
- **Comportamento**:
  - Leggere locale da `$request->query('locale', app()->getLocale())`.
  - Costruire la query **Person** come in `PersonController::index` (stesso `with`, stessi filtri: status, category_id, category_slug, institution_id, institution_slug). **I filtri si applicano anche con grouped=1.**
  - Con grouped=1 **non** usare paginazione: `->get()`.
  - Risolvere l’id dell’istituzione IartNET tramite slug da config (tabella institutions, slug nella locale corrente).
  - Per ogni persona (e per ogni sua associazione istituzione–ruolo in `institution_roles`):
    - **Ruolo globale** (person.role nella locale): se matcha `academic_coordinator` → includere in `academic_coordinator`; continuare comunque a valutare le institution_roles per le altre sezioni (la persona può apparire anche altrove).
    - Per ogni riga di **institution_roles**: istituzione da institution_id; ruolo da `institution_roles[].role` nella locale.
      - Se `institution_id === IartNET id` → aggiungere la persona a **general_coordination** (solo chi è collegato all’istituzione IartNET, via slug).
      - Se ruolo === research_unit_lead → aggiungere a **research_unit_leads** (elementi `{ institution, person }`), ordinare poi per nome istituzione.
      - Se ruolo matcha una chiave di **dedicated_section_roles** → assegnare alla sezione dedicata **per quell’istituzione**; le sezioni dedicate sono **per prime** nell’ordine definito in config.
      - Se ruolo matcha **institution_section_roles** → assegnare alla sezione corrispondente (academic_team_members, research_staff, …) **per quell’istituzione**.
      - Se ruolo è null o stringa `"null"` (trim) → sezione **no_role** per quell’istituzione.
  - **Ordine sezioni per istituzione**: prima tutte le dedicated_section_roles (ordine config), poi le 5 institution_section_roles (1–5), poi no_role. Dentro ogni sezione ordinare le persone per **cognome** (last_name nella locale).
  - **research_unit_leads**: ordinare per nome istituzione (nome nella locale).
  - **institutions**: ordinare per nome istituzione (nome nella locale).
  - Restituire array con chiavi:
    - **academic_coordinator**: array di persone (PersonResource, rispettando light).
    - **research_unit_leads**: array di `{ institution: InstitutionResource, person: PersonResource }`.
    - **general_coordination**: array di persone (PersonResource).
    - **institutions**: array di `{ institution: InstitutionResource, sections: { [slug_sezione]: array di PersonResource } }` (slug in snake_case: academic_team_members, research_staff, …, no_role, più le chiavi di dedicated_section_roles).
  - Quando si costruisce la risposta, passare sempre **$request** a PersonResource/InstitutionResource così che **light** sia rispettato.

---

## 4. PersonController::index

- All’inizio del metodo: se `$request->boolean('grouped')` allora:
  - Istanzializzare **PeoplePageGroupedResponseBuilder** e chiamare **build($request)**.
  - Restituire **response()->json($result)** (senza paginazione).
  - Non applicare paginazione quando grouped=1.
- Altrimenti: logica attuale (filtri + paginazione o get). La collection PersonResource riceve comunque il request, quindi **light** funziona anche in lista non grouped.

---

## 5. Verifiche

- `GET /api/v1/people?grouped=1&locale=en` → JSON con `academic_coordinator`, `research_unit_leads`, `general_coordination`, `institutions`; istituzioni ordinate per nome; sezioni ordinate; persone per sezione ordinate per cognome.
- `GET /api/v1/people?light=1&locale=en` (senza grouped) → stessa struttura attuale ma shortbio e meta.description null (e assenti in translations).
- `GET /api/v1/people?grouped=1&light=1&locale=en` → grouped con payload light.
- Con grouped=1, filtri (es. institution_slug, category_id) applicati: risultato filtrato e comunque raggruppato.
- Ruoli dal DB: usare solo le label esatte (dopo trim) come da elenco ruoli (role_en / role_it).
- Una persona con più ruoli/istituzioni appare in più sezioni/entry come da regola sopra.