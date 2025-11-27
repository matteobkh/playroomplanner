<?php
/**
 * File: common/config.php
 * Percorso: playroomplanner/common/config.php
 * Scopo: Configurazione connessione database e costanti globali
 * Dipendenze: Nessuna
 */

// ==========================================
// CONFIGURAZIONE DATABASE
// ==========================================

// Host del database
define('DB_HOST', 'localhost');

// Nome del database (DEVE ESSERE GIÀ ESISTENTE)
define('DB_NAME', 'bbl');

// Username MySQL
define('DB_USER', 'root');

// Password MySQL (lasciare vuoto per XAMPP default)
define('DB_PASS', '');

// Charset della connessione
define('DB_CHARSET', 'utf8mb4');

// ==========================================
// CONFIGURAZIONE APPLICAZIONE
// ==========================================

// Percorso base dell'applicazione (filesystem)
define('BASE_PATH', dirname(__DIR__));

// URL base dell'applicazione (MODIFICARE IN BASE AL PROPRIO AMBIENTE)
// Per XAMPP standard: http://localhost/playroomplanner
define('BASE_URL', '/playroomplanner');

// Timeout sessione (in secondi - 2 ore)
define('SESSION_TIMEOUT', 7200);

// Modalità debug (true per sviluppo, false per produzione)
define('DEBUG_MODE', true);

// ==========================================
// CONFIGURAZIONE ORARI PRENOTAZIONI
// ==========================================

// Ora minima prenotazione (formato 24h)
define('MIN_BOOKING_HOUR', 9);

// Ora massima prenotazione (formato 24h)
define('MAX_BOOKING_HOUR', 23);

// Durata minima prenotazione (in ore)
define('MIN_BOOKING_DURATION', 1);

// Durata massima prenotazione (in ore)
define('MAX_BOOKING_DURATION', 8);

// ==========================================
// FUNZIONE CONNESSIONE DATABASE
// ==========================================

/**
 * Crea e restituisce una connessione mysqli al database
 * 
 * @return mysqli Oggetto connessione
 * @throws Exception Se la connessione fallisce
 */
function getDbConnection() {
    // Disabilita il report degli errori per gestirli manualmente
    mysqli_report(MYSQLI_REPORT_OFF);
    
    // Crea la connessione
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verifica se ci sono errori di connessione
    if ($conn->connect_error) {
        // In modalità debug, mostra l'errore dettagliato
        if (DEBUG_MODE) {
            throw new Exception('Errore connessione database: ' . $conn->connect_error);
        } else {
            // In produzione, errore generico
            throw new Exception('Errore di connessione al database. Riprova più tardi.');
        }
    }
    
    // Imposta il charset della connessione
    if (!$conn->set_charset(DB_CHARSET)) {
        throw new Exception('Errore impostazione charset: ' . $conn->error);
    }
    
    // Imposta la timezone per MySQL
    $conn->query("SET time_zone = '+01:00'");
    
    return $conn;
}

/**
 * Chiude la connessione al database
 * 
 * @param mysqli $conn Connessione da chiudere
 * @return void
 */
function closeDbConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}

// ==========================================
// GESTIONE ERRORI
// ==========================================

// Se siamo in modalità debug, mostra tutti gli errori
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Log errori in un file
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/error.log');
} else {
    // In produzione, nascondi gli errori ma logga
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
}

// ==========================================
// TIMEZONE
// ==========================================

// Imposta il timezone per l'Italia
date_default_timezone_set('Europe/Rome');

// ==========================================
// CONFIGURAZIONE SESSIONI
// ==========================================

// Imposta parametri sicuri per le sessioni
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Se siamo in HTTPS, usa cookie sicuri
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
