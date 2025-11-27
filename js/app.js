/**
 * File: js/app.js
 * Percorso: playroomplanner/js/app.js
 * Scopo: Logica JavaScript principale per interazioni, chiamate API e gestione UI
 * Dipendenze: Bootstrap 5.3
 */

// ==========================================
// API HELPER FUNCTIONS
// ==========================================

/**
 * Funzione wrapper per chiamate API fetch
 * @param {string} url - URL dell'endpoint API
 * @param {string} method - Metodo HTTP (GET, POST, PUT, DELETE)
 * @param {object|null} data - Dati da inviare nel body (per POST/PUT)
 * @returns {Promise<object>} - Risposta JSON dell'API
 */
async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin' // Includi cookie di sessione
    };
    
    // Aggiungi body per POST/PUT
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        
        // Verifica se la risposta è JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Risposta non JSON ricevuta dal server');
        }
        
        const result = await response.json();
        
        // Se il server restituisce un errore HTTP, lancia eccezione
        if (!response.ok) {
            throw new Error(result.error || `HTTP error ${response.status}`);
        }
        
        return result;
    } catch (error) {
        console.error('API call error:', error);
        throw error;
    }
}

/**
 * Effettua il logout dell'utente
 * @returns {Promise<void>}
 */
async function logout() {
    try {
        await apiCall('../backend/api.php/logout', 'POST');
        window.location.href = '../index.php';
    } catch (error) {
        console.error('Logout error:', error);
        alert('Errore durante il logout');
    }
}

// ==========================================
// UI HELPER FUNCTIONS
// ==========================================

/**
 * Mostra un loading overlay
 */
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Caricamento...</span>
        </div>
    `;
    document.body.appendChild(overlay);
}

/**
 * Nasconde il loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Mostra un alert Bootstrap
 * @param {string} message - Messaggio da mostrare
 * @param {string} type - Tipo di alert (success, danger, warning, info)
 * @param {string} containerId - ID del container dove inserire l'alert
 */
function showAlert(message, type = 'info', containerId = 'alertContainer') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    container.innerHTML = alertHtml;
    
    // Auto-dismiss dopo 5 secondi
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

/**
 * Mostra un modal di conferma Bootstrap
 * @param {string} title - Titolo del modal
 * @param {string} message - Messaggio
 * @param {function} onConfirm - Callback da eseguire se confermato
 */
function showConfirmModal(title, message, onConfirm) {
    // Rimuovi modal esistenti
    const existingModal = document.getElementById('confirmModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Crea nuovo modal
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${message}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="button" class="btn btn-primary" id="confirmBtn">Conferma</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    document.getElementById('confirmBtn').addEventListener('click', () => {
        modal.hide();
        if (onConfirm) onConfirm();
    });
    
    modal.show();
    
    // Rimuovi modal dal DOM quando viene chiuso
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// ==========================================
// FORM VALIDATION HELPERS
// ==========================================

/**
 * Valida un'email
 * @param {string} email - Email da validare
 * @returns {boolean}
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Valida una data nel formato YYYY-MM-DD
 * @param {string} date - Data da validare
 * @returns {boolean}
 */
function validateDate(date) {
    const re = /^\d{4}-\d{2}-\d{2}$/;
    if (!re.test(date)) return false;
    
    const d = new Date(date);
    return d instanceof Date && !isNaN(d);
}

/**
 * Valida un datetime nel formato YYYY-MM-DD HH:MM:SS
 * @param {string} datetime - Datetime da validare
 * @returns {boolean}
 */
function validateDatetime(datetime) {
    const re = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
    if (!re.test(datetime)) return false;
    
    const d = new Date(datetime.replace(' ', 'T'));
    return d instanceof Date && !isNaN(d);
}

// ==========================================
// FORMATTING HELPERS
// ==========================================

/**
 * Formatta una data in formato italiano
 * @param {string} date - Data in formato YYYY-MM-DD o ISO
 * @param {boolean} includeTime - Includi orario
 * @returns {string}
 */
function formatDate(date, includeTime = false) {
    const d = new Date(date);
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return d.toLocaleDateString('it-IT', options);
}

/**
 * Formatta un orario da datetime
 * @param {string} datetime - Datetime in formato ISO o YYYY-MM-DD HH:MM:SS
 * @returns {string} - Orario in formato HH:MM
 */
function formatTime(datetime) {
    const d = new Date(datetime.replace(' ', 'T'));
    return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Ottiene il nome del giorno della settimana
 * @param {Date} date - Data
 * @returns {string} - Nome del giorno (es. "Lunedì")
 */
function getDayName(date) {
    const days = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
    return days[date.getDay()];
}

/**
 * Ottiene il nome del mese
 * @param {Date} date - Data
 * @returns {string} - Nome del mese (es. "Gennaio")
 */
function getMonthName(date) {
    const months = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                    'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
    return months[date.getMonth()];
}

// ==========================================
// USER INFO HELPERS
// ==========================================

/**
 * Ottiene i dati dell'utente corrente
 * @returns {Promise<object>} - Dati utente
 */
async function getCurrentUser() {
    try {
        const response = await apiCall('../backend/api.php/current-user', 'GET');
        return response.user;
    } catch (error) {
        console.error('Error getting current user:', error);
        return null;
    }
}

// ==========================================
// PRENOTAZIONI HELPERS
// ==========================================

/**
 * Crea una nuova prenotazione
 * @param {object} data - Dati della prenotazione
 * @returns {Promise<object>} - Risultato operazione
 */
async function createPrenotazione(data) {
    return await apiCall('../backend/api.php/prenotazioni', 'POST', data);
}

/**
 * Modifica una prenotazione esistente
 * @param {number} id - ID prenotazione
 * @param {object} data - Dati da modificare
 * @returns {Promise<object>} - Risultato operazione
 */
async function updatePrenotazione(id, data) {
    return await apiCall(`../backend/api.php/prenotazioni/${id}`, 'PUT', data);
}

/**
 * Elimina una prenotazione
 * @param {number} id - ID prenotazione
 * @returns {Promise<object>} - Risultato operazione
 */
async function deletePrenotazione(id) {
    return await apiCall(`../backend/api.php/prenotazioni/${id}`, 'DELETE');
}

/**
 * Ottiene le prenotazioni di una sala per una settimana
 * @param {string} nomeSala - Nome della sala
 * @param {string} nomeSettore - Nome del settore
 * @param {string} date - Data di riferimento (YYYY-MM-DD)
 * @returns {Promise<array>} - Lista prenotazioni
 */
async function getSalaPrenotazioni(nomeSala, nomeSettore, date) {
    const url = `../backend/api.php/sala/${encodeURIComponent(nomeSala)}/week?date=${date}&settore=${encodeURIComponent(nomeSettore)}`;
    const response = await apiCall(url, 'GET');
    return response.prenotazioni || [];
}

/**
 * Ottiene gli impegni di un utente per una settimana
 * @param {string} email - Email utente
 * @param {string} date - Data di riferimento (YYYY-MM-DD)
 * @returns {Promise<array>} - Lista impegni
 */
async function getUserImpegni(email, date) {
    const url = `../backend/api.php/user/${encodeURIComponent(email)}/week?date=${date}`;
    const response = await apiCall(url, 'GET');
    return response.impegni || [];
}

// ==========================================
// INVITI HELPERS
// ==========================================

/**
 * Risponde a un invito
 * @param {number} prenotazioneId - ID prenotazione
 * @param {string} email - Email utente
 * @param {string} risposta - 'si' o 'no'
 * @param {string|null} motivazione - Motivazione (obbligatoria per 'no')
 * @returns {Promise<object>} - Risultato operazione
 */
async function rispondiInvito(prenotazioneId, email, risposta, motivazione = null) {
    const url = `../backend/api.php/inviti/${prenotazioneId}/${encodeURIComponent(email)}/risposta`;
    return await apiCall(url, 'POST', { risposta, motivazione });
}

// ==========================================
// SANITIZATION
// ==========================================

/**
 * Sanitizza una stringa per output HTML
 * @param {string} str - Stringa da sanitizzare
 * @returns {string} - Stringa sanitizzata
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ==========================================
// INITIALIZATION
// ==========================================

// Quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    // Aggiungi event listener per pulsanti logout globali
    const logoutButtons = document.querySelectorAll('[data-action="logout"]');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });
});
