# Play Room Planner

Sistema di gestione prenotazioni sale per settori artistici (Musica, Teatro, Danza).

## Requisiti

- PHP 8.x
- MySQL 5.7+
- XAMPP (macOS/Windows/Linux)
- Browser moderno con supporto JavaScript

## Installazione

### 1. Copia i file

Copia l'intera cartella `playroomplanner` nella directory `htdocs` di XAMPP:
```
/Applications/XAMPP/htdocs/playroomplanner/
```

### 2. Configurazione Database

**IMPORTANTE**: Il database deve essere già esistente con lo schema fornito nella consegna.

Modifica il file `common/config.php` con le tue credenziali MySQL:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'playroomplanner'); // Nome del tuo database
define('DB_USER', 'root');            // Username MySQL
define('DB_PASS', '');                // Password MySQL
```

### 3. Avvia XAMPP

1. Apri XAMPP Control Panel
2. Avvia Apache
3. Avvia MySQL

### 4. Accedi all'applicazione

Apri il browser e vai a:
```
http://localhost/playroomplanner/
```

## Credenziali di Test

Usa uno degli utenti già presenti nel database:

- **Responsabile Musica**: supermario@bbldrizzy.it / 12345678
- **Docente Musica**: luca@bbldrizzy.it / 12345678
- **Responsabile Teatro**: elvirap@bbldrizzy.it / password
- **Responsabile Danza**: elia@bbldrizzy.it / password
- **Allievo Danza**: carlotta.peda@bbldrizzy.it / password

## Struttura del Progetto

```
playroomplanner/
├─ index.php                  # Entry point
├─ README.md                  # Questo file
├─ frontend/                  # Pagine frontend
│  ├─ home.php               # Dashboard utente
│  ├─ login.php              # Login
│  ├─ register.php           # Registrazione
│  ├─ profile.php            # Profilo utente
│  ├─ sala_prenotazioni.php  # Prenotazioni sala
│  ├─ user_impegni.php       # Impegni utente
│  └─ components/            # Componenti riutilizzabili
│     ├─ header.html
│     ├─ nav.php
│     └─ footer.html
├─ backend/                   # Logica backend
│  ├─ api.php                # Router REST API
│  ├─ users-exe.php          # Logica utenti
│  └─ prenotazioni-exe.php   # Logica prenotazioni
├─ common/                    # File comuni
│  ├─ config.php             # Configurazione DB
│  ├─ functions.php          # Funzioni utility
│  └─ auth.php               # Sistema autenticazione
├─ css/                       # Fogli di stile
│  └─ style.css
├─ js/                        # JavaScript
│  ├─ app.js                 # Logica applicazione
│  └─ calendar.js            # Utility calendario
└─ images/                    # Immagini
   └─ placeholder.png
```

## API Endpoints

### Autenticazione

- `POST /backend/api.php/login`
  - Body: `{email, password}`
  - Response: `{success, user:{email, nome, cognome, ruolo, settore}}`

- `POST /backend/api.php/logout`
  - Response: `{success}`

### Utenti

- `POST /backend/api.php/users`
  - Body: `{email, password, nome, cognome, data_nascita, nome_ruolo, nome_settore?, data_inizio?}`
  - Response: `{success, message}`

- `GET /backend/api.php/users/{email}`
  - Response: `{success, user:{...}}`

- `PUT /backend/api.php/users/{email}`
  - Body: `{nome?, cognome?, password?, foto?}`
  - Response: `{success, message}`

- `DELETE /backend/api.php/users/{email}`
  - Response: `{success, message}`

### Prenotazioni

- `POST /backend/api.php/prenotazioni`
  - Body: `{data_ora_inizio, durata, attivita, criterio, nome_settore, nome_sala, invitati[]?}`
  - Response: `{success, prenotazione_id}`

- `PUT /backend/api.php/prenotazioni/{id}`
  - Body: `{data_ora_inizio?, durata?, attivita?}`
  - Response: `{success, message}`

- `DELETE /backend/api.php/prenotazioni/{id}`
  - Response: `{success, message}`

- `GET /backend/api.php/sala/{nome_sala}/week?date=YYYY-MM-DD&settore={nome_settore}`
  - Response: `{success, prenotazioni:[...]}`

### Inviti

- `POST /backend/api.php/inviti/{prenotazione_id}/{email}/risposta`
  - Body: `{risposta:'si'|'no', motivazione?}`
  - Response: `{success, message}`

### Query Utility

- `GET /backend/api.php/user/{email}/week?date=YYYY-MM-DD`
  - Response: `{success, impegni:[...]}`

- `GET /backend/api.php/operation/stats`
  - Response: `{success, stats:{partecipanti_per_prenotazione, prenotazioni_per_giorno, conflitti}}`

## Vincoli Implementati

### Prenotazioni

- ✅ Orario: solo ore intere tra 09:00 e 23:00
- ✅ Durata: minimo 1 ora
- ✅ No sovrapposizioni nella stessa sala
- ✅ Capienza: partecipanti ≤ capienza sala
- ✅ Solo responsabili possono creare prenotazioni

### Inviti

- ✅ Risposta "no" richiede motivazione obbligatoria
- ✅ No sovrapposizioni per l'utente che accetta
- ✅ Verifica capienza prima dell'accettazione

### Utenti

- ✅ Responsabili devono avere data_inizio valorizzata
- ✅ Email univoca (PK)
- ✅ Ruolo: responsabile, docente, allievo, tecnico

## Funzionalità Principali

### Per tutti gli utenti

- Visualizzazione proprio profilo
- Modifica dati personali
- Visualizzazione impegni settimanali
- Risposta a inviti (accettazione/rifiuto)
- Visualizzazione calendario prenotazioni sale

### Per responsabili

- Creazione prenotazioni
- Modifica/cancellazione proprie prenotazioni
- Invito partecipanti
- Visualizzazione statistiche

## Tecnologie Utilizzate

- **Backend**: PHP 8.x con PDO (MySQLi)
- **Database**: MySQL
- **Frontend**: HTML5, Bootstrap 5.3, JavaScript ES6
- **API**: REST JSON
- **Autenticazione**: Sessioni PHP

## Troubleshooting

### Errore di connessione al database
- Verifica che MySQL sia avviato in XAMPP
- Controlla le credenziali in `common/config.php`
- Assicurati che il database esista

### Pagina bianca
- Abilita la visualizzazione errori in `common/config.php`
- Controlla i log di Apache in XAMPP

### Sessione non funziona
- Verifica che i cookie siano abilitati nel browser
- Controlla i permessi della cartella `/tmp` o della directory sessioni PHP

## Supporto

Per problemi o domande, verifica:
1. Log errori PHP (XAMPP Control Panel → Logs)
2. Console browser (F12 → Console)
3. Network tab per chiamate API fallite

## Licenza

Progetto didattico - Seconda Consegna Play Room Planner
