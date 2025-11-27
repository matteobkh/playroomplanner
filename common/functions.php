<?php
/**
 * File: common/functions.php
 * Percorso: playroomplanner/common/functions.php
 * Scopo: Funzioni di utilità generale per validazioni, calcoli e operazioni comuni
 * Dipendenze: config.php
 */

require_once __DIR__ . '/config.php';

// ==========================================
// FUNZIONI DI VALIDAZIONE
// ==========================================

/**
 * Valida un indirizzo email
 * 
 * @param string $email Email da validare
 * @return bool True se valida, false altrimenti
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida una password (minimo 8 caratteri)
 * 
 * @param string $password Password da validare
 * @return bool True se valida, false altrimenti
 */
function validatePassword($password) {
    return strlen($password) >= 8;
}

/**
 * Valida una data nel formato YYYY-MM-DD
 * 
 * @param string $date Data da validare
 * @return bool True se valida, false altrimenti
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Valida un datetime nel formato YYYY-MM-DD HH:MM:SS
 * 
 * @param string $datetime Datetime da validare
 * @return bool True se valido, false altrimenti
 */
function validateDatetime($datetime) {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

/**
 * Valida un ruolo utente
 * 
 * @param string $ruolo Ruolo da validare
 * @return bool True se valido, false altrimenti
 */
function validateRuolo($ruolo) {
    $ruoli_validi = ['responsabile', 'docente', 'allievo', 'tecnico'];
    return in_array($ruolo, $ruoli_validi);
}

// ==========================================
// FUNZIONI PER PRENOTAZIONI
// ==========================================

/**
 * Valida l'orario di una prenotazione (deve essere ora intera tra 09:00 e 23:00)
 * 
 * @param string $data_ora_inizio Datetime inizio nel formato YYYY-MM-DD HH:MM:SS
 * @return array ['valid' => bool, 'error' => string]
 */
function validateBookingTime($data_ora_inizio) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $data_ora_inizio);
    
    if (!$dt) {
        return ['valid' => false, 'error' => 'Formato datetime non valido'];
    }
    
    // Verifica che minuti e secondi siano zero (ora intera)
    if ($dt->format('i') !== '00' || $dt->format('s') !== '00') {
        return ['valid' => false, 'error' => 'Le prenotazioni devono iniziare ad ore intere (es. 10:00:00, 14:00:00)'];
    }
    
    // Verifica che l'ora sia tra MIN_BOOKING_HOUR e MAX_BOOKING_HOUR
    $ora = (int)$dt->format('H');
    if ($ora < MIN_BOOKING_HOUR || $ora > MAX_BOOKING_HOUR) {
        return ['valid' => false, 'error' => 'Le prenotazioni sono consentite solo tra le ' . MIN_BOOKING_HOUR . ':00 e le ' . MAX_BOOKING_HOUR . ':00'];
    }
    
    // Verifica che la prenotazione non sia nel passato
    $now = new DateTime();
    if ($dt < $now) {
        return ['valid' => false, 'error' => 'Non è possibile prenotare nel passato'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Valida la durata di una prenotazione
 * 
 * @param int $durata Durata in ore
 * @return array ['valid' => bool, 'error' => string]
 */
function validateBookingDuration($durata) {
    if ($durata < MIN_BOOKING_DURATION) {
        return ['valid' => false, 'error' => 'La durata minima è ' . MIN_BOOKING_DURATION . ' ora/e'];
    }
    
    if ($durata > MAX_BOOKING_DURATION) {
        return ['valid' => false, 'error' => 'La durata massima è ' . MAX_BOOKING_DURATION . ' ore'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Verifica se due prenotazioni si sovrappongono
 * 
 * @param string $inizio1 Inizio prima prenotazione
 * @param int $durata1 Durata prima prenotazione (ore)
 * @param string $inizio2 Inizio seconda prenotazione
 * @param int $durata2 Durata seconda prenotazione (ore)
 * @return bool True se si sovrappongono
 */
function checkBookingOverlap($inizio1, $durata1, $inizio2, $durata2) {
    $dt1_start = new DateTime($inizio1);
    $dt1_end = clone $dt1_start;
    $dt1_end->modify("+{$durata1} hours");
    
    $dt2_start = new DateTime($inizio2);
    $dt2_end = clone $dt2_start;
    $dt2_end->modify("+{$durata2} hours");
    
    // Due intervalli si sovrappongono se:
    // inizio1 < fine2 AND fine1 > inizio2
    return ($dt1_start < $dt2_end && $dt1_end > $dt2_start);
}

/**
 * Controlla se esiste una sovrapposizione per una sala in un dato periodo
 * 
 * @param mysqli $conn Connessione database
 * @param string $nome_sala Nome della sala
 * @param string $nome_settore Nome del settore
 * @param string $data_ora_inizio Inizio prenotazione
 * @param int $durata Durata in ore
 * @param int|null $exclude_id ID prenotazione da escludere (per modifiche)
 * @return array ['overlap' => bool, 'prenotazione_id' => int|null]
 */
function checkRoomOverlap($conn, $nome_sala, $nome_settore, $data_ora_inizio, $durata, $exclude_id = null) {
    $dt_start = new DateTime($data_ora_inizio);
    $dt_end = clone $dt_start;
    $dt_end->modify("+{$durata} hours");
    
    $fine = $dt_end->format('Y-m-d H:i:s');
    
    // Query per trovare prenotazioni sovrapposte
    $sql = "SELECT id, data_ora_inizio, durata 
            FROM prenotazione 
            WHERE nome_sala = ? 
            AND nome_settore = ?
            AND data_ora_inizio < ?
            AND DATE_ADD(data_ora_inizio, INTERVAL durata HOUR) > ?";
    
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_id !== null) {
        $stmt->bind_param('ssssi', $nome_sala, $nome_settore, $fine, $data_ora_inizio, $exclude_id);
    } else {
        $stmt->bind_param('ssss', $nome_sala, $nome_settore, $fine, $data_ora_inizio);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return ['overlap' => true, 'prenotazione_id' => $row['id']];
    }
    
    return ['overlap' => false, 'prenotazione_id' => null];
}

/**
 * Controlla se un utente ha sovrapposizioni con i suoi impegni accettati
 * 
 * @param mysqli $conn Connessione database
 * @param string $email Email utente
 * @param string $data_ora_inizio Inizio prenotazione
 * @param int $durata Durata in ore
 * @param int|null $exclude_prenotazione_id ID prenotazione da escludere
 * @return array ['overlap' => bool, 'prenotazione_id' => int|null]
 */
function checkUserOverlap($conn, $email, $data_ora_inizio, $durata, $exclude_prenotazione_id = null) {
    $dt_start = new DateTime($data_ora_inizio);
    $dt_end = clone $dt_start;
    $dt_end->modify("+{$durata} hours");
    
    $fine = $dt_end->format('Y-m-d H:i:s');
    
    // Query per trovare impegni dell'utente sovrapposti
    $sql = "SELECT p.id, p.data_ora_inizio, p.durata
            FROM prenotazione p
            JOIN invito i ON p.id = i.id_prenotazione
            WHERE i.email_iscritto = ?
            AND i.risposta = 'si'
            AND p.data_ora_inizio < ?
            AND DATE_ADD(p.data_ora_inizio, INTERVAL p.durata HOUR) > ?";
    
    if ($exclude_prenotazione_id !== null) {
        $sql .= " AND p.id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_prenotazione_id !== null) {
        $stmt->bind_param('sssi', $email, $fine, $data_ora_inizio, $exclude_prenotazione_id);
    } else {
        $stmt->bind_param('sss', $email, $fine, $data_ora_inizio);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return ['overlap' => true, 'prenotazione_id' => $row['id']];
    }
    
    return ['overlap' => false, 'prenotazione_id' => null];
}

// ==========================================
// FUNZIONI PER CALCOLO SETTIMANE
// ==========================================

/**
 * Ottiene il primo e ultimo giorno della settimana per una data
 * 
 * @param string $date Data nel formato YYYY-MM-DD
 * @return array ['start' => string, 'end' => string] in formato YYYY-MM-DD
 */
function getWeekRange($date) {
    $dt = new DateTime($date);
    
    // Trova il lunedì della settimana
    $day_of_week = (int)$dt->format('N'); // 1 (lunedì) - 7 (domenica)
    $dt->modify('-' . ($day_of_week - 1) . ' days');
    $start = $dt->format('Y-m-d');
    
    // Domenica (6 giorni dopo lunedì)
    $dt->modify('+6 days');
    $end = $dt->format('Y-m-d');
    
    return ['start' => $start, 'end' => $end];
}

/**
 * Genera array di date per una settimana
 * 
 * @param string $start_date Data inizio settimana (lunedì)
 * @return array Array di date in formato YYYY-MM-DD
 */
function getWeekDays($start_date) {
    $days = [];
    $dt = new DateTime($start_date);
    
    for ($i = 0; $i < 7; $i++) {
        $days[] = $dt->format('Y-m-d');
        $dt->modify('+1 day');
    }
    
    return $days;
}

// ==========================================
// FUNZIONI HELPER
// ==========================================

/**
 * Sanitizza una stringa per output HTML
 * 
 * @param string $str Stringa da sanitizzare
 * @return string Stringa sanitizzata
 */
function sanitizeOutput($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatta una data per la visualizzazione
 * 
 * @param string $date Data nel formato YYYY-MM-DD
 * @param string $format Formato output (default: d/m/Y)
 * @return string Data formattata
 */
function formatDate($date, $format = 'd/m/Y') {
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * Formatta un datetime per la visualizzazione
 * 
 * @param string $datetime Datetime nel formato YYYY-MM-DD HH:MM:SS
 * @param string $format Formato output (default: d/m/Y H:i)
 * @return string Datetime formattato
 */
function formatDatetime($datetime, $format = 'd/m/Y H:i') {
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Genera una risposta JSON e termina lo script
 * 
 * @param array $data Dati da restituire
 * @param int $status_code HTTP status code
 * @return void
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Genera una risposta di errore JSON
 * 
 * @param string $message Messaggio di errore
 * @param int $status_code HTTP status code
 * @return void
 */
function jsonError($message, $status_code = 400) {
    jsonResponse(['success' => false, 'error' => $message], $status_code);
}

/**
 * Genera una risposta di successo JSON
 * 
 * @param mixed $data Dati da restituire
 * @param int $status_code HTTP status code
 * @return void
 */
function jsonSuccess($data = [], $status_code = 200) {
    $response = ['success' => true];
    if (is_array($data)) {
        $response = array_merge($response, $data);
    }
    jsonResponse($response, $status_code);
}
