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

// Permetti CORS se necessario (commentare in produzione)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci richieste OPTIONS per CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==========================================
// ROUTING
// ==========================================

// Ottieni il metodo HTTP e il path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';

// Rimuovi query string dal path
$path = parse_url($path, PHP_URL_PATH);

// Rimuovi prefisso /backend/api.php se presente
$path = preg_replace('#^/playroomplanner/backend/api\.php#', '', $path);
$path = preg_replace('#^/backend/api\.php#', '', $path);

// Se il path è vuoto, imposta a /
if (empty($path) || $path === '/') {
    jsonResponse(['message' => 'API Play Room Planner', 'version' => '1.0']);
}

// Split del path in segmenti
$segments = array_filter(explode('/', $path));
$segments = array_values($segments); // Re-index array

// Ottieni il body della richiesta per POST/PUT
$input = null;
if (in_array($method, ['POST', 'PUT'])) {
    $input = json_decode(file_get_contents('php://input'), true);
}

// ==========================================
// ROUTE HANDLING
// ==========================================

try {
    // AUTENTICAZIONE
    if ($segments[0] === 'login' && $method === 'POST') {
        handleLogin($input);
    }
    elseif ($segments[0] === 'logout' && $method === 'POST') {
        handleLogout();
    }
    elseif ($segments[0] === 'current-user' && $method === 'GET') {
        handleCurrentUser();
    }
    
    // UTENTI
    elseif ($segments[0] === 'users') {
        if ($method === 'POST') {
            handleCreateUser($input);
        } elseif ($method === 'GET' && isset($segments[1])) {
            handleGetUser($segments[1]);
        } elseif ($method === 'PUT' && isset($segments[1])) {
            handleUpdateUser($segments[1], $input);
        } elseif ($method === 'DELETE' && isset($segments[1])) {
            handleDeleteUser($segments[1]);
        } else {
            jsonError('Endpoint non valido', 404);
        }
    }
    
    // PRENOTAZIONI
    elseif ($segments[0] === 'prenotazioni') {
        if ($method === 'POST') {
            handleCreatePrenotazione($input);
        } elseif ($method === 'PUT' && isset($segments[1])) {
            handleUpdatePrenotazione($segments[1], $input);
        } elseif ($method === 'DELETE' && isset($segments[1])) {
            handleDeletePrenotazione($segments[1]);
        } else {
            jsonError('Endpoint non valido', 404);
        }
    }
    
    // SALA PRENOTAZIONI SETTIMANALI
    elseif ($segments[0] === 'sala' && isset($segments[1]) && $segments[2] === 'week' && $method === 'GET') {
        handleGetSalaWeek($segments[1]);
    }
    
    // USER IMPEGNI SETTIMANALI
    elseif ($segments[0] === 'user' && isset($segments[1]) && $segments[2] === 'week' && $method === 'GET') {
        handleGetUserWeek($segments[1]);
    }
    
    // INVITI
    elseif ($segments[0] === 'inviti' && isset($segments[1]) && isset($segments[2]) && $segments[3] === 'risposta' && $method === 'POST') {
        handleRispostaInvito($segments[1], $segments[2], $input);
    }
    
    // STATISTICHE
    elseif ($segments[0] === 'operation' && $segments[1] === 'stats' && $method === 'GET') {
        handleGetStats();
    }
    
    // ROUTE NON TROVATA
    else {
        jsonError('Endpoint non trovato', 404);
    }
    
} catch (Exception $e) {
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
    requireLogin();
    
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
    requireLogin();
    
    // Verifica che l'utente possa modificare solo il proprio profilo o sia responsabile
    $currentUser = getCurrentUser();
    if ($currentUser['email'] !== $email && !isResponsabile()) {
        jsonError('Non autorizzato', 403);
    }
    
    $conn = getDbConnection();
    
    // Campi modificabili
    $updates = [];
    $types = '';
    $params = [];
    
    if (isset($input['nome'])) {
        $updates[] = 'nome = ?';
        $types .= 's';
        $params[] = $input['nome'];
    }
    
    if (isset($input['cognome'])) {
        $updates[] = 'cognome = ?';
        $types .= 's';
        $params[] = $input['cognome'];
    }
    
    if (isset($input['password']) && !empty($input['password'])) {
        if (!validatePassword($input['password'])) {
            jsonError('La password deve essere di almeno 8 caratteri');
        }
        $updates[] = 'password = ?';
        $types .= 's';
        $params[] = $input['password'];
    }
    
    if (isset($input['foto'])) {
        $updates[] = 'foto = ?';
        $types .= 's';
        $params[] = $input['foto'];
    }
    
    if (empty($updates)) {
        jsonError('Nessun campo da aggiornare');
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
            $_SESSION['user']['nome'] = $input['nome'] ?? $currentUser['nome'];
            $_SESSION['user']['cognome'] = $input['cognome'] ?? $currentUser['cognome'];
            if (isset($input['foto'])) {
                $_SESSION['user']['foto'] = $input['foto'];
            }
        }
        
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Profilo aggiornato con successo']);
    } else {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'aggiornamento');
    }
}

function handleDeleteUser($email) {
    requireResponsabile();
    
    $conn = getDbConnection();
    
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
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'eliminazione');
    }
}

// ==========================================
// HANDLER FUNCTIONS - PRENOTAZIONI
// ==========================================

function handleCreatePrenotazione($input) {
    requireResponsabile();
    
    $currentUser = getCurrentUser();
    
    // Validazione dati obbligatori
    $required = ['data_ora_inizio', 'durata', 'nome_settore', 'nome_sala'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            jsonError("Il campo $field è obbligatorio");
        }
    }
    
    // Valida orario
    $timeValidation = validateBookingTime($input['data_ora_inizio']);
    if (!$timeValidation['valid']) {
        jsonError($timeValidation['error']);
    }
    
    // Valida durata
    $durationValidation = validateBookingDuration($input['durata']);
    if (!$durationValidation['valid']) {
        jsonError($durationValidation['error']);
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
    }
    
    $sala = $result->fetch_assoc();
    $stmt->close();
    
    // Verifica sovrapposizioni con altre prenotazioni della stessa sala
    $overlapCheck = checkRoomOverlap($conn, $input['nome_sala'], $input['nome_settore'], 
                                     $input['data_ora_inizio'], $input['durata']);
    
    if ($overlapCheck['overlap']) {
        closeDbConnection($conn);
        jsonError('Esiste già una prenotazione sovrapposta per questa sala');
    }
    
    // Inserisci la prenotazione
    $sql = "INSERT INTO prenotazione (data_ora_inizio, durata, attivita, num_iscritti, 
                                     criterio, nome_settore, nome_sala, email_responsabile)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    $attivita = $input['attivita'] ?? null;
    $num_iscritti = $input['num_iscritti'] ?? null;
    $criterio = $input['criterio'] ?? 'tutti';
    
    $stmt->bind_param('sisisiis', 
        $input['data_ora_inizio'],
        $input['durata'],
        $attivita,
        $num_iscritti,
        $criterio,
        $input['nome_settore'],
        $input['nome_sala'],
        $currentUser['email']
    );
    
    if (!$stmt->execute()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante la creazione della prenotazione');
    }
    
    $prenotazione_id = $conn->insert_id;
    $stmt->close();
    
    // Gestisci inviti se presenti
    if (isset($input['invitati']) && is_array($input['invitati']) && count($input['invitati']) > 0) {
        // Verifica capienza
        if (count($input['invitati']) > $sala['capienza']) {
            // Elimina la prenotazione appena creata
            $sql = "DELETE FROM prenotazione WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $prenotazione_id);
            $stmt->execute();
            $stmt->close();
            
            closeDbConnection($conn);
            jsonError('Il numero di invitati supera la capienza della sala (' . $sala['capienza'] . ')');
        }
        
        // Inserisci gli inviti
        $sql = "INSERT INTO invito (email_iscritto, id_prenotazione) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        foreach ($input['invitati'] as $email) {
            $stmt->bind_param('si', $email, $prenotazione_id);
            $stmt->execute();
        }
        
        $stmt->close();
    }
    
    closeDbConnection($conn);
    jsonSuccess(['message' => 'Prenotazione creata con successo', 'prenotazione_id' => $prenotazione_id], 201);
}

function handleUpdatePrenotazione($id, $input) {
    requireLogin();
    
    $currentUser = getCurrentUser();
    $conn = getDbConnection();
    
    // Verifica che la prenotazione esista e che l'utente sia il responsabile
    $sql = "SELECT email_responsabile, nome_sala, nome_settore FROM prenotazione WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Prenotazione non trovata', 404);
    }
    
    $prenotazione = $result->fetch_assoc();
    $stmt->close();
    
    // Solo il responsabile può modificare
    if ($prenotazione['email_responsabile'] !== $currentUser['email']) {
        closeDbConnection($conn);
        jsonError('Non autorizzato', 403);
    }
    
    // Campi modificabili
    $updates = [];
    $types = '';
    $params = [];
    
    if (isset($input['data_ora_inizio'])) {
        $timeValidation = validateBookingTime($input['data_ora_inizio']);
        if (!$timeValidation['valid']) {
            closeDbConnection($conn);
            jsonError($timeValidation['error']);
        }
        
        $updates[] = 'data_ora_inizio = ?';
        $types .= 's';
        $params[] = $input['data_ora_inizio'];
    }
    
    if (isset($input['durata'])) {
        $durationValidation = validateBookingDuration($input['durata']);
        if (!$durationValidation['valid']) {
            closeDbConnection($conn);
            jsonError($durationValidation['error']);
        }
        
        $updates[] = 'durata = ?';
        $types .= 'i';
        $params[] = $input['durata'];
    }
    
    if (isset($input['attivita'])) {
        $updates[] = 'attivita = ?';
        $types .= 's';
        $params[] = $input['attivita'];
    }
    
    if (empty($updates)) {
        closeDbConnection($conn);
        jsonError('Nessun campo da aggiornare');
    }
    
    // Se modifichiamo data o durata, verifica sovrapposizioni
    if (isset($input['data_ora_inizio']) || isset($input['durata'])) {
        // Ottieni i dati attuali se non forniti
        $sql = "SELECT data_ora_inizio, durata FROM prenotazione WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();
        $stmt->close();
        
        $new_inizio = $input['data_ora_inizio'] ?? $current['data_ora_inizio'];
        $new_durata = $input['durata'] ?? $current['durata'];
        
        $overlapCheck = checkRoomOverlap($conn, $prenotazione['nome_sala'], 
                                        $prenotazione['nome_settore'], 
                                        $new_inizio, $new_durata, $id);
        
        if ($overlapCheck['overlap']) {
            closeDbConnection($conn);
            jsonError('La modifica crea una sovrapposizione con un\'altra prenotazione');
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
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'aggiornamento');
    }
}

function handleDeletePrenotazione($id) {
    requireLogin();
    
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
    }
    
    $prenotazione = $result->fetch_assoc();
    $stmt->close();
    
    if ($prenotazione['email_responsabile'] !== $currentUser['email']) {
        closeDbConnection($conn);
        jsonError('Non autorizzato', 403);
    }
    
    // Elimina la prenotazione (gli inviti verranno eliminati automaticamente per CASCADE)
    $sql = "DELETE FROM prenotazione WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Prenotazione eliminata']);
    } else {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante l\'eliminazione');
    }
}

// ==========================================
// HANDLER FUNCTIONS - QUERY SETTIMANALI
// ==========================================

function handleGetSalaWeek($nome_sala) {
    requireLogin();
    
    $nome_settore = $_GET['settore'] ?? '';
    $date = $_GET['date'] ?? date('Y-m-d');
    
    if (empty($nome_settore)) {
        jsonError('Parametro settore obbligatorio');
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
    
    jsonSuccess(['prenotazioni' => $prenotazioni]);
}

function handleGetUserWeek($email) {
    requireLogin();
    
    $currentUser = getCurrentUser();
    
    // L'utente può vedere solo i propri impegni a meno che non sia responsabile
    if ($currentUser['email'] !== $email && !isResponsabile()) {
        jsonError('Non autorizzato', 403);
    }
    
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Calcola range settimana
    $weekRange = getWeekRange($date);
    $start = $weekRange['start'];
    $end = $weekRange['end'];
    
    $conn = getDbConnection();
    
    // Ottieni tutti gli impegni dell'utente per la settimana (prenotazioni con invito accettato)
    $sql = "SELECT p.*, inv.risposta, inv.data_ora_risposta,
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
    
    jsonSuccess(['impegni' => $impegni]);
}

// ==========================================
// HANDLER FUNCTIONS - INVITI
// ==========================================

function handleRispostaInvito($prenotazione_id, $email, $input) {
    requireLogin();
    
    $currentUser = getCurrentUser();
    
    // L'utente può rispondere solo ai propri inviti
    if ($currentUser['email'] !== $email) {
        jsonError('Non autorizzato', 403);
    }
    
    if (!isset($input['risposta']) || !in_array($input['risposta'], ['si', 'no'])) {
        jsonError('Risposta non valida. Usare "si" o "no"');
    }
    
    // Se rifiuta, la motivazione è obbligatoria
    if ($input['risposta'] === 'no' && (empty($input['motivazione']) || trim($input['motivazione']) === '')) {
        jsonError('La motivazione è obbligatoria per i rifiuti');
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
    }
    
    $stmt->close();
    
    // Se accetta, verifica sovrapposizioni con altri impegni
    if ($input['risposta'] === 'si') {
        // Ottieni dati prenotazione
        $sql = "SELECT data_ora_inizio, durata FROM prenotazione WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $prenotazione_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $prenotazione = $result->fetch_assoc();
        $stmt->close();
        
        if (!$prenotazione) {
            closeDbConnection($conn);
            jsonError('Prenotazione non trovata', 404);
        }
        
        // Verifica sovrapposizioni con impegni già accettati
        $overlapCheck = checkUserOverlap($conn, $email, $prenotazione['data_ora_inizio'], 
                                        $prenotazione['durata'], $prenotazione_id);
        
        if ($overlapCheck['overlap']) {
            closeDbConnection($conn);
            jsonError('Hai già un impegno sovrapposto in questo orario');
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
        
        if ($capienza_info['partecipanti_attuali'] >= $capienza_info['capienza']) {
            closeDbConnection($conn);
            jsonError('La sala ha raggiunto la capienza massima');
        }
    }
    
    // Aggiorna la risposta
    $sql = "UPDATE invito SET risposta = ?, data_ora_risposta = NOW(), motivazione = ? 
            WHERE email_iscritto = ? AND id_prenotazione = ?";
    
    $stmt = $conn->prepare($sql);
    $motivazione = $input['motivazione'] ?? null;
    $stmt->bind_param('sssi', $input['risposta'], $motivazione, $email, $prenotazione_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDbConnection($conn);
        jsonSuccess(['message' => 'Risposta registrata con successo']);
    } else {
        $stmt->close();
        closeDbConnection($conn);
        jsonError('Errore durante la registrazione della risposta');
    }
}

// ==========================================
// HANDLER FUNCTIONS - STATISTICHE
// ==========================================

function handleGetStats() {
    requireResponsabile();
    
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
    while ($row = $result->fetch_assoc()) {
        $partecipanti_per_prenotazione[] = $row;
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
    while ($row = $result->fetch_assoc()) {
        $prenotazioni_per_giorno[] = $row;
    }
    $stats['prenotazioni_per_giorno'] = $prenotazioni_per_giorno;
    
    closeDbConnection($conn);
    
    jsonSuccess(['stats' => $stats]);
}

