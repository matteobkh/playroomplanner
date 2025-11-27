<?php
/**
 * File: common/auth.php
 * Percorso: playroomplanner/common/auth.php
 * Scopo: Gestione autenticazione, sessioni e controllo accessi
 * Dipendenze: config.php, functions.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ==========================================
// GESTIONE SESSIONI
// ==========================================

/**
 * Inizializza la sessione se non già attiva
 * 
 * @return void
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurazione sessione sicura
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
        
        // Controlla timeout sessione
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            if ($elapsed > SESSION_TIMEOUT) {
                // Sessione scaduta
                session_unset();
                session_destroy();
                session_start();
            }
        }
        
        // Aggiorna timestamp ultima attività
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Verifica se l'utente è autenticato
 * 
 * @return bool True se autenticato, false altrimenti
 */
function isLoggedIn() {
    initSession();
    return isset($_SESSION['user']) && isset($_SESSION['user']['email']) && !empty($_SESSION['user']['email']);
}

/**
 * Ottiene i dati dell'utente corrente dalla sessione
 * 
 * @return array|null Array con dati utente o null se non autenticato
 */
function getCurrentUser() {
    initSession();
    return $_SESSION['user'] ?? null;
}

/**
 * Verifica se l'utente corrente è un responsabile
 * 
 * @return bool True se responsabile, false altrimenti
 */
function isResponsabile() {
    $user = getCurrentUser();
    return $user && isset($user['nome_ruolo']) && $user['nome_ruolo'] === 'responsabile';
}

/**
 * Verifica se l'utente corrente è responsabile di un determinato settore
 * 
 * @param string $nome_settore Nome del settore da verificare
 * @return bool True se è responsabile di quel settore
 */
function isResponsabileDiSettore($nome_settore) {
    $user = getCurrentUser();
    return $user && 
           isset($user['nome_ruolo']) &&
           $user['nome_ruolo'] === 'responsabile' && 
           isset($user['nome_settore']) &&
           $user['nome_settore'] === $nome_settore;
}

// ==========================================
// AUTENTICAZIONE
// ==========================================

/**
 * Effettua il login dell'utente
 * 
 * @param string $email Email utente
 * @param string $password Password in chiaro
 * @return array ['success' => bool, 'user' => array|null, 'error' => string]
 */
function login($email, $password) {
    try {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Email e password sono obbligatori'
            ];
        }
        
        $conn = getDbConnection();
        
        // Query per recuperare l'utente
        $sql = "SELECT email, password, nome, cognome, data_nascita, foto, 
                       data_inizio, nome_settore, nome_ruolo
                FROM iscritto
                WHERE email = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            closeDbConnection($conn);
            return [
                'success' => false,
                'user' => null,
                'error' => 'Errore di sistema durante il login'
            ];
        }
        
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Verifica password 
            // NOTA: In questo sistema la password è in chiaro come da specifica del progetto
            // In produzione si dovrebbe usare password_verify()
            if ($row['password'] === $password) {
                // Password corretta, crea la sessione
                initSession();
                
                // Salva dati utente in sessione (escludi password)
                unset($row['password']);
                $_SESSION['user'] = $row;
                $_SESSION['last_activity'] = time();
                
                // Rigenera session ID per sicurezza
                session_regenerate_id(true);
                
                $stmt->close();
                closeDbConnection($conn);
                
                return [
                    'success' => true,
                    'user' => $row,
                    'error' => ''
                ];
            }
        }
        
        $stmt->close();
        closeDbConnection($conn);
        
        return [
            'success' => false,
            'user' => null,
            'error' => 'Email o password non corretti'
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'user' => null,
            'error' => 'Errore durante il login'
        ];
    }
}

/**
 * Effettua il logout dell'utente
 * 
 * @return void
 */
function logout() {
    initSession();
    
    // Distruggi tutte le variabili di sessione
    $_SESSION = array();
    
    // Distruggi il cookie di sessione
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Distruggi la sessione
    session_destroy();
}

// ==========================================
// CONTROLLO ACCESSI - PER PAGINE WEB
// ==========================================

/**
 * Richiede che l'utente sia autenticato, altrimenti reindirizza al login
 * 
 * @param string $redirect_to URL dove reindirizzare dopo il login
 * @return void
 */
function requireLogin($redirect_to = null) {
    if (!isLoggedIn()) {
        $base_url = BASE_URL;
        if ($redirect_to) {
            header("Location: {$base_url}/frontend/login.php?redirect=" . urlencode($redirect_to));
        } else {
            header("Location: {$base_url}/frontend/login.php");
        }
        exit;
    }
}

/**
 * Richiede che l'utente sia un responsabile, altrimenti mostra errore
 * 
 * @return void
 */
function requireResponsabile() {
    requireLogin();
    
    if (!isResponsabile()) {
        http_response_code(403);
        die('Accesso negato: solo i responsabili possono accedere a questa risorsa');
    }
}

/**
 * Richiede che l'utente sia il proprietario della risorsa o un responsabile
 * 
 * @param string $email Email del proprietario della risorsa
 * @return void
 */
function requireOwnerOrResponsabile($email) {
    requireLogin();
    
    $user = getCurrentUser();
    
    // Permetti se è il proprietario o se è responsabile
    if ($user['email'] !== $email && !isResponsabile()) {
        http_response_code(403);
        die('Accesso negato: non hai i permessi per questa operazione');
    }
}

// ==========================================
// REGISTRAZIONE
// ==========================================

/**
 * Registra un nuovo utente nel sistema
 * 
 * @param array $data Dati utente: email, password, nome, cognome, data_nascita, nome_ruolo, nome_settore?, data_inizio?
 * @return array ['success' => bool, 'error' => string]
 */
function registerUser($data) {
    try {
        // Validazione dati
        $required = ['email', 'password', 'nome', 'cognome', 'data_nascita', 'nome_ruolo'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return ['success' => false, 'error' => "Il campo $field è obbligatorio"];
            }
        }
        
        // Trim dei campi stringa
        $data['email'] = trim($data['email']);
        $data['nome'] = trim($data['nome']);
        $data['cognome'] = trim($data['cognome']);
        $data['nome_ruolo'] = trim($data['nome_ruolo']);
        
        // Valida email
        if (!validateEmail($data['email'])) {
            return ['success' => false, 'error' => 'Email non valida'];
        }
        
        // Valida password
        if (!validatePassword($data['password'])) {
            return ['success' => false, 'error' => 'La password deve essere di almeno 8 caratteri'];
        }
        
        // Valida data di nascita
        if (!validateDate($data['data_nascita'])) {
            return ['success' => false, 'error' => 'Data di nascita non valida (formato: YYYY-MM-DD)'];
        }
        
        // Valida ruolo
        if (!validateRuolo($data['nome_ruolo'])) {
            return ['success' => false, 'error' => 'Ruolo non valido. Ruoli ammessi: responsabile, docente, allievo, tecnico'];
        }
        
        // Se è responsabile, richiedi data_inizio
        if ($data['nome_ruolo'] === 'responsabile' && (!isset($data['data_inizio']) || empty($data['data_inizio']))) {
            return ['success' => false, 'error' => 'Per i responsabili è obbligatoria la data di inizio ruolo'];
        }
        
        // Valida data_inizio se presente
        if (isset($data['data_inizio']) && !empty($data['data_inizio']) && !validateDate($data['data_inizio'])) {
            return ['success' => false, 'error' => 'Data inizio non valida (formato: YYYY-MM-DD)'];
        }
        
        $conn = getDbConnection();
        
        // Verifica se l'email esiste già
        $sql = "SELECT email FROM iscritto WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            closeDbConnection($conn);
            return ['success' => false, 'error' => 'Email già registrata'];
        }
        $stmt->close();
        
        // Verifica che il settore esista se specificato
        $nome_settore = null;
        if (isset($data['nome_settore']) && !empty(trim($data['nome_settore']))) {
            $nome_settore = trim($data['nome_settore']);
            $sql = "SELECT nome_settore FROM settore WHERE nome_settore = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $nome_settore);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                closeDbConnection($conn);
                return ['success' => false, 'error' => 'Settore non valido'];
            }
            $stmt->close();
        }
        
        // Inserisci il nuovo utente
        $sql = "INSERT INTO iscritto (email, password, nome, cognome, data_nascita, foto, data_inizio, nome_settore, nome_ruolo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            closeDbConnection($conn);
            return ['success' => false, 'error' => 'Errore di sistema durante la registrazione'];
        }
        
        $foto = isset($data['foto']) && !empty($data['foto']) ? $data['foto'] : null;
        $data_inizio = isset($data['data_inizio']) && !empty($data['data_inizio']) ? $data['data_inizio'] : null;
        
        $stmt->bind_param('sssssssss',
            $data['email'],
            $data['password'], // In produzione, usare password_hash()
            $data['nome'],
            $data['cognome'],
            $data['data_nascita'],
            $foto,
            $data_inizio,
            $nome_settore,
            $data['nome_ruolo']
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            $error = $stmt->error;
            $stmt->close();
            closeDbConnection($conn);
            return ['success' => false, 'error' => 'Errore durante la registrazione: ' . $error];
        }
        
        $stmt->close();
        
        // Se è un nuovo iscritto con settore, aggiorna il contatore del settore
        if ($nome_settore) {
            $sql = "UPDATE settore SET num_iscritti = num_iscritti + 1 WHERE nome_settore = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $nome_settore);
            $stmt->execute();
            $stmt->close();
        }
        
        closeDbConnection($conn);
        
        return ['success' => true, 'error' => ''];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Errore durante la registrazione'];
    }
}
