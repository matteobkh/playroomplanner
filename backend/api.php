<?php
/**
 * File: backend/api.php
 * Percorso: playroomplanner/backend/api.php
 * Scopo: Router REST API per tutte le operazioni del sistema
 * Dipendenze: common/config.php, common/functions.php, common/auth.php
 */

// Includi dipendenze
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../common/auth.php';

// Avvia la sessione
initSession();

// Header per API REST JSON
header('Content-Type: application/json; charset=utf-8');

// Permetti CORS se necessario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci richieste OPTIONS per CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==========================================
// ROUTING MIGLIORATO
// ==========================================

// Ottieni il metodo HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Ottieni il path in modo robusto
$requestUri = $_SERVER['REQUEST_URI'];

// Rimuovi query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Rimuovi il prefisso del progetto e api.php
// Gestisce sia /playroomplanner/backend/api.php/... che /backend/api.php/...
$patterns = [
    '#^/playroomplanner/backend/api\.php#i',
    '#^/backend/api\.php#i',
    '#^/api\.php#i'
];

foreach ($patterns as $pattern) {
    $path = preg_replace($pattern, '', $path);
}

// Rimuovi slash iniziale e finale
$path = trim($path, '/');

// Se il path è vuoto, restituisci info API
if (empty($path)) {
    jsonResponse(['message' => 'API Play Room Planner', 'version' => '1.0', 'status' => 'online']);
}

// Split del path in segmenti
$segments = explode('/', $path);
$segments = array_values(array_filter($segments)); // Re-index e rimuovi vuoti

// Ottieni il body della richiesta per POST/PUT
$input = null;
if (in_array($method, ['POST', 'PUT'])) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Se JSON non valido, prova con form data
    if ($input === null && !empty($rawInput)) {
        parse_str($rawInput, $input);
    }
}

// Debug mode: log delle richieste (commentare in produzione)
if (DEBUG_MODE) {
    error_log("API Request: $method $path");
    error_log("Segments: " . json_encode($segments));
    if ($input) {
        error_log("Input: " . json_encode($input));
    }
}

// ==========================================
// ROUTE HANDLING
// ==========================================

try {
    $resource = $segments[0] ?? '';
    
    switch ($resource) {
        // ==========================================
        // AUTENTICAZIONE
        // ==========================================
        case 'login':
            if ($method === 'POST') {
                handleLogin($input);
            } else {
                jsonError('Metodo non consentito', 405);
            }
            break;
            
        case 'logout':
            if ($method === 'POST') {
                handleLogout();
            } else {
                jsonError('Metodo non consentito', 405);
            }
            break;
            
        case 'current-user':
            if ($method === 'GET') {
                handleCurrentUser();
            } else {
                jsonError('Metodo non consentito', 405);
            }
            break;
        
        // ==========================================
        // UTENTI
        // ==========================================
        case 'users':
            if ($method === 'POST' && count($segments) === 1) {
                // POST /users - Registrazione
                handleCreateUser($input);
            } elseif ($method === 'GET' && isset($segments[1])) {
                // GET /users/{email}
                handleGetUser(urldecode($segments[1]));
            } elseif ($method === 'PUT' && isset($segments[1])) {
                // PUT /users/{email}
                handleUpdateUser(urldecode($segments[1]), $input);
            } elseif ($method === 'DELETE' && isset($segments[1])) {
                // DELETE /users/{email}
                handleDeleteUser(urldecode($segments[1]));
            } else {
                jsonError('Endpoint non valido', 404);
            }
            break;
        
        // ==========================================
        // PRENOTAZIONI
        // ==========================================
        case 'prenotazioni':
            if ($method === 'POST' && count($segments) === 1) {
                // POST /prenotazioni
                handleCreatePrenotazione($input);
            } elseif ($method === 'GET' && isset($segments[1])) {
                // GET /prenotazioni/{id}
                handleGetPrenotazione((int)$segments[1]);
            } elseif ($method === 'PUT' && isset($segments[1])) {
                // PUT /prenotazioni/{id}
                handleUpdatePrenotazione((int)$segments[1], $input);
            } elseif ($method === 'DELETE' && isset($segments[1])) {
                // DELETE /prenotazioni/{id}
                handleDeletePrenotazione((int)$segments[1]);
            } else {
                jsonError('Endpoint non valido', 404);
            }
            break;
        
        // ==========================================
        // SALA PRENOTAZIONI SETTIMANALI
        // ==========================================
        case 'sala':
            // GET /sala/{nome_sala}/week?date=...&settore=...
            if ($method === 'GET' && isset($segments[1]) && isset($segments[2]) && $segments[2] === 'week') {
                handleGetSalaWeek(urldecode($segments[1]));
            } else {
                jsonError('Endpoint non valido', 404);
            }
            break;
        
        // ==========================================
        // USER IMPEGNI SETTIMANALI
        // ==========================================
        case 'user':
            // GET /user/{email}/week?date=...
            if ($method === 'GET' && isset($segments[1]) && isset($segments[2]) && $segments[2] === 'week') {
                handleGetUserWeek(urldecode($segments[1]));
            } else {
                jsonError('Endpoint non valido', 404);
            }
            break;
        
        // ==========================================
        // INVITI
        // ==========================================
        case 'inviti':
            // POST /inviti/{prenotazione_id}/{email}/risposta
            if ($method === 'POST' && isset($segments[1]) && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'risposta') {
                handleRispostaInvito((int)$segments[1], urldecode($segments[2]), $input);
            } else {
                jsonError('Endpoint non valido', 404);
            }
            break;
        
        // ==========================================
        // STATISTICHE
        // ==========================================
        case 'operation':
            if ($method === 'GET' && isset($segments[1]) && $segments[1] === 'stats') {
                handleGetStats();
            } else {
                jsonError('Endpoint non valido', 404);
            }
            break;
        
        // ==========================================
        // SETTORI E SALE (nuovi endpoint)
        // ==========================================
        case 'settori':
            if ($method === 'GET') {
                handleGetSettori();
            } else {
                jsonError('Metodo non consentito', 405);
            }
            break;
            
        case 'sale':
            if ($method === 'GET') {
                handleGetSale();
            } else {
                jsonError('Metodo non consentito', 405);
            }
            break;
        
        // ==========================================
        // ROUTE NON TROVATA
        // ==========================================
        default:
            jsonError('Endpoint non trovato: ' . $path, 404);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonError('Errore del server: ' . $e->getMessage(), 500);
}

// ==========================================
// HANDLER FUNCTIONS - AUTENTICAZIONE
// ==========================================

function handleLogin($input) {
    if (!isset($input['email']) || !isset($input['password'])) {
        jsonError('Email e password obbligatori');
    }
    
    $result = login($input['email'], $input['password']);
    
    if ($result['success']) {
        jsonSuccess(['user' => $result['user']]);
    } else {
        jsonError($result['error'], 401);
    }
}

function handleLogout() {
    logout();
    jsonSuccess(['message' => 'Logout effettuato']);
}

function handleCurrentUser() {
    $user = getCurrentUser();
    if ($user) {
        jsonSuccess(['user' => $user]);
    } else {
        jsonError('Non autenticato', 401);
    }
}

// ==========================================
// HANDLER FUNCTIONS - UTENTI
// ==========================================

function handleCreateUser($input) {
    $result = registerUser($input);
    
    if ($result['success']) {
        jsonSuccess(['message' => 'Utente registrato con successo'], 201);
    } else {
        jsonError($result['error']);
    }
}

function handleGetUser($email) {
    if (!requireLoginApi()) return;
    
    $conn = getDbConnection();
    
    $sql = "SELECT email, nome, cognome, data_nascita, foto, data_inizio, 
                   nome_settore, nome_ruolo
            FROM iscritto
            WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['user' => $row]);
    } else {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Utente non trovato', 404);
    }
}

function handleUpdateUser($email, $input) {
    if (!requireLoginApi()) return;
    
    // Verifica che l'utente possa modificare solo il proprio profilo o sia responsabile
    $currentUser = getCurrentUser();
    if ($currentUser['email'] !== $email && !isResponsabile()) {
        jsonError('Non autorizzato', 403);
        return;
    }
    
    $conn = getDbConnection();
    
    // Campi modificabili
    $updates = [];
    $types = '';
    $params = [];
    
    if (isset($input['nome']) && !empty(trim($input['nome']))) {
        $updates[] = 'nome = ?';
        $types .= 's';
        $params[] = trim($input['nome']);
    }
    
    if (isset($input['cognome']) && !empty(trim($input['cognome']))) {
        $updates[] = 'cognome = ?';
        $types .= 's';
        $params[] = trim($input['cognome']);
    }
    
    if (isset($input['password']) && !empty($input['password'])) {
        if (!validatePassword($input['password'])) {
            closeDbConnection($conn);
            jsonError('La password deve essere di almeno 8 caratteri');
            return;
        }
        $updates[] = 'password = ?';
        $types .= 's';
        $params[] = $input['password'];
    }
    
    if (isset($input['foto'])) {
        $updates[] = 'foto = ?';
        $types .= 's';
        $params[] = $input['foto'] ?: null;
    }
    
    if (empty($updates)) {
        closeDbConnection($conn);
        jsonError('Nessun campo da aggiornare');
        return;
    }
    
    $sql = "UPDATE iscritto SET " . implode(', ', $updates) . " WHERE email = ?";
    $types .= 's';
    $params[] = $email;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Aggiorna la sessione se l'utente ha modificato il proprio profilo
        if ($currentUser['email'] === $email) {
            if (isset($input['nome'])) $_SESSION['user']['nome'] = trim($input['nome']);
            if (isset($input['cognome'])) $_SESSION['user']['cognome'] = trim($input['cognome']);
            if (isset($input['foto'])) $_SESSION['user']['foto'] = $input['foto'];
        }
        
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Profilo aggiornato con successo']);
    } else {
        $error = $stmt->error;
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'aggiornamento: ' . $error);
    }
}

function handleDeleteUser($email) {
    if (!requireResponsabileApi()) return;
    
    $conn = getDbConnection();
    
    // Prima elimina gli inviti dell'utente
    $sql = "DELETE FROM invito WHERE email_iscritto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
    
    // Poi elimina l'utente
    $sql = "DELETE FROM iscritto WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        closeDbConnection($conn);
        
        if ($affected > 0) {
            jsonSuccess(['message' => 'Utente eliminato']);
        } else {
            jsonError('Utente non trovato', 404);
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'eliminazione: ' . $error);
    }
}

// ==========================================
// HANDLER FUNCTIONS - PRENOTAZIONI
// ==========================================

function handleCreatePrenotazione($input) {
    if (!requireResponsabileApi()) return;
    
    $currentUser = getCurrentUser();
    
    // Debug: log dei dati ricevuti
    error_log("handleCreatePrenotazione - Input ricevuto: " . json_encode($input));
    
    // Validazione dati obbligatori
    $required = ['data_ora_inizio', 'durata', 'nome_settore', 'nome_sala'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            jsonError("Il campo $field è obbligatorio");
            return;
        }
    }
    
    // Normalizza il formato datetime
    $data_ora_inizio = normalizeDateTime($input['data_ora_inizio']);
    if (!$data_ora_inizio) {
        jsonError('Formato data/ora non valido. Usa YYYY-MM-DD HH:MM:SS');
        return;
    }
    
    error_log("Data normalizzata: $data_ora_inizio");
    
    // Valida orario
    $timeValidation = validateBookingTime($data_ora_inizio);
    if (!$timeValidation['valid']) {
        jsonError($timeValidation['error']);
        return;
    }
    
    // Valida durata
    $durata = (int)$input['durata'];
    $durationValidation = validateBookingDuration($durata);
    if (!$durationValidation['valid']) {
        jsonError($durationValidation['error']);
        return;
    }
    
    $conn = getDbConnection();
    
    // Verifica che la sala esista
    $sql = "SELECT capienza FROM sala WHERE nome_sala = ? AND nome_settore = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $input['nome_sala'], $input['nome_settore']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Sala non trovata');
        return;
    }
    
    $sala = $result->fetch_assoc();
    $stmt->close();
    
    // Verifica sovrapposizioni con altre prenotazioni della stessa sala
    $overlapCheck = checkRoomOverlap($conn, $input['nome_sala'], $input['nome_settore'], 
                                     $data_ora_inizio, $durata);
    
    if ($overlapCheck['overlap']) {
        closeDbConnection($conn);
        jsonError('Esiste già una prenotazione sovrapposta per questa sala');
        return;
    }
    
    // Prepara i dati per l'inserimento
    $attivita = isset($input['attivita']) && !empty($input['attivita']) ? $input['attivita'] : null;
    $num_iscritti = isset($input['num_iscritti']) ? (int)$input['num_iscritti'] : null;
    $criterio = isset($input['criterio']) && !empty($input['criterio']) ? $input['criterio'] : 'tutti';
    
    // Inserisci la prenotazione
    $sql = "INSERT INTO prenotazione (data_ora_inizio, durata, attivita, num_iscritti, 
                                     criterio, nome_settore, nome_sala, email_responsabile)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        closeDbConnection($conn);
        jsonError('Errore preparazione query: ' . $conn->error);
        return;
    }
    
    $stmt->bind_param('sissssss', 
        $data_ora_inizio,
        $durata,
        $attivita,
        $num_iscritti,
        $criterio,
        $input['nome_settore'],
        $input['nome_sala'],
        $currentUser['email']
    );
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante la creazione della prenotazione: ' . $error);
        return;
    }
    
    $prenotazione_id = $conn->insert_id;
    $stmt->close();
    
    error_log("Prenotazione creata con ID: $prenotazione_id");
    
    // Gestisci inviti se presenti
    if (isset($input['invitati']) && is_array($input['invitati']) && count($input['invitati']) > 0) {
        // Filtra invitati vuoti
        $invitati = array_filter($input['invitati'], function($email) {
            return !empty(trim($email));
        });
        
        if (count($invitati) > 0) {
            // Verifica capienza
            if (count($invitati) > $sala['capienza']) {
                // Elimina la prenotazione appena creata
                $sql = "DELETE FROM prenotazione WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $prenotazione_id);
                $stmt->execute();
                $stmt->close();
                
                closeDbConnection($conn);
                jsonError('Il numero di invitati (' . count($invitati) . ') supera la capienza della sala (' . $sala['capienza'] . ')');
                return;
            }
            
            // Inserisci gli inviti
            $sql = "INSERT INTO invito (email_iscritto, id_prenotazione) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            
            $invitatiInseriti = 0;
            foreach ($invitati as $email) {
                $email = trim($email);
                // Verifica che l'utente esista
                $checkSql = "SELECT email FROM iscritto WHERE email = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param('s', $email);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $stmt->bind_param('si', $email, $prenotazione_id);
                    if ($stmt->execute()) {
                        $invitatiInseriti++;
                    }
                }
                $checkStmt->close();
            }
            
            $stmt->close();
            error_log("Invitati inseriti: $invitatiInseriti");
        }
    }
    
    closeDbConnection($conn);
    jsonSuccess(['message' => 'Prenotazione creata con successo', 'prenotazione_id' => $prenotazione_id], 201);
}

function handleGetPrenotazione($id) {
    if (!requireLoginApi()) return;
    
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, i.nome AS responsabile_nome, i.cognome AS responsabile_cognome
            FROM prenotazione p
            JOIN iscritto i ON p.email_responsabile = i.email
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['prenotazione' => $row]);
    } else {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Prenotazione non trovata', 404);
    }
}

function handleUpdatePrenotazione($id, $input) {
    if (!requireLoginApi()) return;
    
    $currentUser = getCurrentUser();
    $conn = getDbConnection();
    
    // Verifica che la prenotazione esista e ottieni i dati
    $sql = "SELECT * FROM prenotazione WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Prenotazione non trovata', 404);
        return;
    }
    
    $prenotazione = $result->fetch_assoc();
    $stmt->close();
    
    // Solo il responsabile che ha creato può modificare
    if ($prenotazione['email_responsabile'] !== $currentUser['email']) {
        closeDbConnection($conn);
        jsonError('Non autorizzato a modificare questa prenotazione', 403);
        return;
    }
    
    // Campi modificabili
    $updates = [];
    $types = '';
    $params = [];
    
    $new_inizio = $prenotazione['data_ora_inizio'];
    $new_durata = $prenotazione['durata'];
    
    if (isset($input['data_ora_inizio']) && !empty($input['data_ora_inizio'])) {
        $new_inizio = normalizeDateTime($input['data_ora_inizio']);
        if (!$new_inizio) {
            closeDbConnection($conn);
            jsonError('Formato data/ora non valido');
            return;
        }
        
        $timeValidation = validateBookingTime($new_inizio);
        if (!$timeValidation['valid']) {
            closeDbConnection($conn);
            jsonError($timeValidation['error']);
            return;
        }
        
        $updates[] = 'data_ora_inizio = ?';
        $types .= 's';
        $params[] = $new_inizio;
    }
    
    if (isset($input['durata'])) {
        $new_durata = (int)$input['durata'];
        $durationValidation = validateBookingDuration($new_durata);
        if (!$durationValidation['valid']) {
            closeDbConnection($conn);
            jsonError($durationValidation['error']);
            return;
        }
        
        $updates[] = 'durata = ?';
        $types .= 'i';
        $params[] = $new_durata;
    }
    
    if (isset($input['attivita'])) {
        $updates[] = 'attivita = ?';
        $types .= 's';
        $params[] = $input['attivita'] ?: null;
    }
    
    if (isset($input['criterio'])) {
        $updates[] = 'criterio = ?';
        $types .= 's';
        $params[] = $input['criterio'];
    }
    
    if (empty($updates)) {
        closeDbConnection($conn);
        jsonError('Nessun campo da aggiornare');
        return;
    }
    
    // Verifica sovrapposizioni se modifichiamo data o durata
    if (isset($input['data_ora_inizio']) || isset($input['durata'])) {
        $overlapCheck = checkRoomOverlap($conn, $prenotazione['nome_sala'], 
                                        $prenotazione['nome_settore'], 
                                        $new_inizio, $new_durata, $id);
        
        if ($overlapCheck['overlap']) {
            closeDbConnection($conn);
            jsonError('La modifica crea una sovrapposizione con un\'altra prenotazione');
            return;
        }
    }
    
    // Esegui aggiornamento
    $sql = "UPDATE prenotazione SET " . implode(', ', $updates) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Prenotazione aggiornata']);
    } else {
        $error = $stmt->error;
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'aggiornamento: ' . $error);
    }
}

function handleDeletePrenotazione($id) {
    if (!requireLoginApi()) return;
    
    $currentUser = getCurrentUser();
    $conn = getDbConnection();
    
    // Verifica che la prenotazione esista e che l'utente sia il responsabile
    $sql = "SELECT email_responsabile FROM prenotazione WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Prenotazione non trovata', 404);
        return;
    }
    
    $prenotazione = $result->fetch_assoc();
    $stmt->close();
    
    if ($prenotazione['email_responsabile'] !== $currentUser['email']) {
        closeDbConnection($conn);
        jsonError('Non autorizzato', 403);
        return;
    }
    
    // Elimina prima gli inviti associati
    $sql = "DELETE FROM invito WHERE id_prenotazione = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    
    // Poi elimina la prenotazione
    $sql = "DELETE FROM prenotazione WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Prenotazione eliminata']);
    } else {
        $error = $stmt->error;
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'eliminazione: ' . $error);
    }
}

// ==========================================
// HANDLER FUNCTIONS - QUERY SETTIMANALI
// ==========================================

function handleGetSalaWeek($nome_sala) {
    if (!requireLoginApi()) return;
    
    $nome_settore = $_GET['settore'] ?? '';
    $date = $_GET['date'] ?? date('Y-m-d');
    
    if (empty($nome_settore)) {
        jsonError('Parametro settore obbligatorio');
        return;
    }
    
    // Calcola range settimana
    $weekRange = getWeekRange($date);
    $start = $weekRange['start'];
    $end = $weekRange['end'];
    
    $conn = getDbConnection();
    
    // Ottieni tutte le prenotazioni della sala per la settimana
    $sql = "SELECT p.*, i.nome AS responsabile_nome, i.cognome AS responsabile_cognome
            FROM prenotazione p
            JOIN iscritto i ON p.email_responsabile = i.email
            WHERE p.nome_sala = ?
            AND p.nome_settore = ?
            AND DATE(p.data_ora_inizio) BETWEEN ? AND ?
            ORDER BY p.data_ora_inizio";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $nome_sala, $nome_settore, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prenotazioni = [];
    while ($row = $result->fetch_assoc()) {
        $prenotazioni[] = $row;
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    jsonSuccess(['prenotazioni' => $prenotazioni, 'week' => ['start' => $start, 'end' => $end]]);
}

function handleGetUserWeek($email) {
    if (!requireLoginApi()) return;
    
    $currentUser = getCurrentUser();
    
    // L'utente può vedere solo i propri impegni a meno che non sia responsabile
    if ($currentUser['email'] !== $email && !isResponsabile()) {
        jsonError('Non autorizzato', 403);
        return;
    }
    
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Calcola range settimana
    $weekRange = getWeekRange($date);
    $start = $weekRange['start'];
    $end = $weekRange['end'];
    
    $conn = getDbConnection();
    
    // Ottieni tutti gli impegni dell'utente per la settimana
    $sql = "SELECT p.*, inv.risposta, inv.data_ora_risposta, inv.motivazione,
                   i.nome AS responsabile_nome, i.cognome AS responsabile_cognome
            FROM prenotazione p
            JOIN invito inv ON p.id = inv.id_prenotazione
            JOIN iscritto i ON p.email_responsabile = i.email
            WHERE inv.email_iscritto = ?
            AND DATE(p.data_ora_inizio) BETWEEN ? AND ?
            ORDER BY p.data_ora_inizio";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $email, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $impegni = [];
    while ($row = $result->fetch_assoc()) {
        $impegni[] = $row;
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    jsonSuccess(['impegni' => $impegni, 'week' => ['start' => $start, 'end' => $end]]);
}

// ==========================================
// HANDLER FUNCTIONS - INVITI
// ==========================================

function handleRispostaInvito($prenotazione_id, $email, $input) {
    if (!requireLoginApi()) return;
    
    $currentUser = getCurrentUser();
    
    // L'utente può rispondere solo ai propri inviti
    if ($currentUser['email'] !== $email) {
        jsonError('Non autorizzato', 403);
        return;
    }
    
    if (!isset($input['risposta']) || !in_array($input['risposta'], ['si', 'no'])) {
        jsonError('Risposta non valida. Usare "si" o "no"');
        return;
    }
    
    // Se rifiuta, la motivazione è obbligatoria
    if ($input['risposta'] === 'no' && (!isset($input['motivazione']) || empty(trim($input['motivazione'])))) {
        jsonError('La motivazione è obbligatoria per i rifiuti');
        return;
    }
    
    $conn = getDbConnection();
    
    // Verifica che l'invito esista
    $sql = "SELECT * FROM invito WHERE email_iscritto = ? AND id_prenotazione = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $email, $prenotazione_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Invito non trovato', 404);
        return;
    }
    
    $stmt->close();
    
    // Se accetta, verifica sovrapposizioni con altri impegni
    if ($input['risposta'] === 'si') {
        // Ottieni dati prenotazione
        $sql = "SELECT data_ora_inizio, durata, nome_sala, nome_settore FROM prenotazione WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $prenotazione_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $prenotazione = $result->fetch_assoc();
        $stmt->close();
        
        if (!$prenotazione) {
            closeDbConnection($conn);
            jsonError('Prenotazione non trovata', 404);
            return;
        }
        
        // Verifica sovrapposizioni con impegni già accettati
        $overlapCheck = checkUserOverlap($conn, $email, $prenotazione['data_ora_inizio'], 
                                        $prenotazione['durata'], $prenotazione_id);
        
        if ($overlapCheck['overlap']) {
            closeDbConnection($conn);
            jsonError('Hai già un impegno sovrapposto in questo orario');
            return;
        }
        
        // Verifica capienza sala
        $sql = "SELECT s.capienza, 
                       (SELECT COUNT(*) FROM invito WHERE id_prenotazione = ? AND risposta = 'si') AS partecipanti_attuali
                FROM prenotazione p
                JOIN sala s ON p.nome_sala = s.nome_sala AND p.nome_settore = s.nome_settore
                WHERE p.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $prenotazione_id, $prenotazione_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $capienza_info = $result->fetch_assoc();
        $stmt->close();
        
        if ($capienza_info && $capienza_info['partecipanti_attuali'] >= $capienza_info['capienza']) {
            closeDbConnection($conn);
            jsonError('La sala ha raggiunto la capienza massima');
            return;
        }
    }
    
    // Aggiorna la risposta
    $sql = "UPDATE invito SET risposta = ?, data_ora_risposta = NOW(), motivazione = ? 
            WHERE email_iscritto = ? AND id_prenotazione = ?";
    
    $stmt = $conn->prepare($sql);
    $motivazione = isset($input['motivazione']) ? trim($input['motivazione']) : null;
    $stmt->bind_param('sssi', $input['risposta'], $motivazione, $email, $prenotazione_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Risposta registrata con successo']);
    } else {
        $error = $stmt->error;
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante la registrazione della risposta: ' . $error);
    }
}

// ==========================================
// HANDLER FUNCTIONS - STATISTICHE
// ==========================================

function handleGetStats() {
    if (!requireResponsabileApi()) return;
    
    $conn = getDbConnection();
    
    $stats = [];
    
    // Numero partecipanti e verifica capienza
    $sql = "SELECT P.id, P.attivita, P.nome_sala,
                   COUNT(DISTINCT I.email_iscritto) AS partecipanti,
                   S.capienza,
                   CASE WHEN COUNT(DISTINCT I.email_iscritto) > S.capienza THEN 'Superata'
                        ELSE 'OK' END AS stato
            FROM prenotazione P
            JOIN sala S ON P.nome_sala = S.nome_sala AND P.nome_settore = S.nome_settore
            LEFT JOIN invito I ON P.id = I.id_prenotazione AND I.risposta='si'
            GROUP BY P.id, S.capienza";
    
    $result = $conn->query($sql);
    $partecipanti_per_prenotazione = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $partecipanti_per_prenotazione[] = $row;
        }
    }
    $stats['partecipanti_per_prenotazione'] = $partecipanti_per_prenotazione;
    
    // Numero prenotazioni per giorno e sala
    $sql = "SELECT DATE(P.data_ora_inizio) AS giorno, 
                   P.nome_sala, 
                   COUNT(*) AS num_prenotazioni
            FROM prenotazione P
            GROUP BY giorno, P.nome_sala
            ORDER BY giorno";
    
    $result = $conn->query($sql);
    $prenotazioni_per_giorno = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $prenotazioni_per_giorno[] = $row;
        }
    }
    $stats['prenotazioni_per_giorno'] = $prenotazioni_per_giorno;
    
    closeDbConnection($conn);
    
    jsonSuccess(['stats' => $stats]);
}

// ==========================================
// HANDLER FUNCTIONS - SETTORI E SALE
// ==========================================

function handleGetSettori() {
    $conn = getDbConnection();
    
    $sql = "SELECT nome_settore, num_iscritti FROM settore ORDER BY nome_settore";
    $result = $conn->query($sql);
    
    $settori = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settori[] = $row;
        }
    }
    
    closeDbConnection($conn);
    jsonSuccess(['settori' => $settori]);
}

function handleGetSale() {
    $settore = $_GET['settore'] ?? null;
    
    $conn = getDbConnection();
    
    if ($settore) {
        $sql = "SELECT s.nome_sala, s.nome_settore, s.capienza, 
                       GROUP_CONCAT(d.nome_dotazione SEPARATOR ', ') AS dotazioni
                FROM sala s
                LEFT JOIN dotazione d ON s.nome_sala = d.nome_sala AND s.nome_settore = d.nome_settore
                WHERE s.nome_settore = ?
                GROUP BY s.nome_sala, s.nome_settore, s.capienza
                ORDER BY s.nome_sala";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $settore);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql = "SELECT s.nome_sala, s.nome_settore, s.capienza,
                       GROUP_CONCAT(d.nome_dotazione SEPARATOR ', ') AS dotazioni
                FROM sala s
                LEFT JOIN dotazione d ON s.nome_sala = d.nome_sala AND s.nome_settore = d.nome_settore
                GROUP BY s.nome_sala, s.nome_settore, s.capienza
                ORDER BY s.nome_settore, s.nome_sala";
        $result = $conn->query($sql);
    }
    
    $sale = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sale[] = $row;
        }
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    closeDbConnection($conn);
    
    jsonSuccess(['sale' => $sale]);
}

// ==========================================
// HELPER FUNCTIONS PER API
// ==========================================

/**
 * Verifica login per API (restituisce JSON invece di redirect)
 */
function requireLoginApi() {
    if (!isLoggedIn()) {
        jsonError('Non autenticato', 401);
        return false;
    }
    return true;
}

/**
 * Verifica responsabile per API (restituisce JSON invece di redirect)
 */
function requireResponsabileApi() {
    if (!requireLoginApi()) {
        return false;
    }
    
    if (!isResponsabile()) {
        jsonError('Solo i responsabili possono effettuare questa operazione', 403);
        return false;
    }
    return true;
}

/**
 * Normalizza un datetime da vari formati a YYYY-MM-DD HH:MM:SS
 */
function normalizeDateTime($datetime) {
    if (empty($datetime)) {
        return null;
    }
    
    // Rimuovi eventuali T e Z dal formato ISO
    $datetime = str_replace('T', ' ', $datetime);
    $datetime = str_replace('Z', '', $datetime);
    
    // Prova vari formati
    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
        'd/m/Y H:i:s',
        'd/m/Y H:i'
    ];
    
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $datetime);
        if ($dt !== false) {
            // Forza minuti e secondi a 00 per avere ore intere
            return $dt->format('Y-m-d H:00:00');
        }
    }
    
    // Ultimo tentativo con strtotime
    $timestamp = strtotime($datetime);
    if ($timestamp !== false) {
        return date('Y-m-d H:00:00', $timestamp);
    }
    
    return null;
}
