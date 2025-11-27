/**
 * File: js/calendar.js
 * Percorso: playroomplanner/js/calendar.js
 * Scopo: Utility per gestione calendario e visualizzazione settimane
 * Dipendenze: Nessuna
 */

// ==========================================
// WEEK CALCULATION
// ==========================================

/**
 * Ottiene il lunedì della settimana per una data specifica
 * @param {Date} date - Data di riferimento
 * @returns {Date} - Lunedì della settimana
 */
function getMondayOfWeek(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
    return new Date(d.setDate(diff));
}

/**
 * Ottiene tutti i giorni di una settimana partendo da una data
 * @param {Date|string} date - Data di riferimento (può essere Date object o stringa YYYY-MM-DD)
 * @returns {Array<Date>} - Array di 7 date (da lunedì a domenica)
 */
function getWeekDays(date) {
    const monday = getMondayOfWeek(new Date(date));
    const days = [];
    
    for (let i = 0; i < 7; i++) {
        const day = new Date(monday);
        day.setDate(monday.getDate() + i);
        days.push(day);
    }
    
    return days;
}

/**
 * Ottiene il range di date (inizio-fine) di una settimana
 * @param {Date|string} date - Data di riferimento
 * @returns {object} - {start: Date, end: Date}
 */
function getWeekRange(date) {
    const days = getWeekDays(date);
    return {
        start: days[0],
        end: days[6]
    };
}

/**
 * Formatta una data in formato YYYY-MM-DD
 * @param {Date} date - Data da formattare
 * @returns {string} - Data formattata
 */
function formatDateISO(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Naviga alla settimana precedente
 * @param {Date|string} currentDate - Data corrente
 * @returns {Date} - Lunedì della settimana precedente
 */
function getPreviousWeek(currentDate) {
    const monday = getMondayOfWeek(new Date(currentDate));
    monday.setDate(monday.getDate() - 7);
    return monday;
}

/**
 * Naviga alla settimana successiva
 * @param {Date|string} currentDate - Data corrente
 * @returns {Date} - Lunedì della settimana successiva
 */
function getNextWeek(currentDate) {
    const monday = getMondayOfWeek(new Date(currentDate));
    monday.setDate(monday.getDate() + 7);
    return monday;
}

/**
 * Verifica se una data è oggi
 * @param {Date} date - Data da verificare
 * @returns {boolean} - True se è oggi
 */
function isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

/**
 * Verifica se una data è nel passato
 * @param {Date} date - Data da verificare
 * @returns {boolean} - True se è nel passato
 */
function isPast(date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkDate = new Date(date);
    checkDate.setHours(0, 0, 0, 0);
    return checkDate < today;
}

// ==========================================
// TIME SLOT GENERATION
// ==========================================

/**
 * Genera gli slot orari per una giornata (dalle 09:00 alle 23:00)
 * @returns {Array<string>} - Array di orari in formato HH:00
 */
function generateTimeSlots() {
    const slots = [];
    for (let hour = 9; hour <= 23; hour++) {
        slots.push(`${String(hour).padStart(2, '0')}:00`);
    }
    return slots;
}

/**
 * Combina data e ora in datetime string
 * @param {Date|string} date - Data
 * @param {string} time - Ora in formato HH:00
 * @returns {string} - Datetime in formato YYYY-MM-DD HH:00:00
 */
function combineDatetime(date, time) {
    const dateStr = typeof date === 'string' ? date : formatDateISO(date);
    return `${dateStr} ${time}:00`;
}

/**
 * Verifica se un datetime è disponibile (non occupato da prenotazioni)
 * @param {string} datetime - Datetime da verificare
 * @param {number} durata - Durata in ore
 * @param {Array} prenotazioni - Lista prenotazioni esistenti
 * @returns {boolean} - True se disponibile
 */
function isSlotAvailable(datetime, durata, prenotazioni) {
    const slotStart = new Date(datetime.replace(' ', 'T'));
    const slotEnd = new Date(slotStart);
    slotEnd.setHours(slotEnd.getHours() + durata);
    
    for (const pren of prenotazioni) {
        const prenStart = new Date(pren.data_ora_inizio.replace(' ', 'T'));
        const prenEnd = new Date(prenStart);
        prenEnd.setHours(prenEnd.getHours() + pren.durata);
        
        // Controlla sovrapposizione
        if (slotStart < prenEnd && slotEnd > prenStart) {
            return false;
        }
    }
    
    return true;
}

/**
 * Trova le prenotazioni attive in un determinato slot
 * @param {string} datetime - Datetime dello slot
 * @param {Array} prenotazioni - Lista prenotazioni
 * @returns {Array} - Prenotazioni che occupano lo slot
 */
function getBookingsInSlot(datetime, prenotazioni) {
    const slotTime = new Date(datetime.replace(' ', 'T'));
    const active = [];
    
    for (const pren of prenotazioni) {
        const prenStart = new Date(pren.data_ora_inizio.replace(' ', 'T'));
        const prenEnd = new Date(prenStart);
        prenEnd.setHours(prenEnd.getHours() + pren.durata);
        
        // Se lo slot è tra inizio e fine della prenotazione
        if (slotTime >= prenStart && slotTime < prenEnd) {
            active.push(pren);
        }
    }
    
    return active;
}

// ==========================================
// CALENDAR RENDERING
// ==========================================

/**
 * Genera HTML per l'header del calendario (giorni della settimana)
 * @param {Array<Date>} weekDays - Array di 7 date
 * @returns {string} - HTML dell'header
 */
function renderCalendarHeader(weekDays) {
    const dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    let html = '';
    
    weekDays.forEach((day, index) => {
        const isCurrentDay = isToday(day);
        const dayClass = isCurrentDay ? 'bg-primary text-white' : '';
        
        html += `
            <div class="calendar-day-header ${dayClass}">
                <div>${dayNames[index]}</div>
                <div class="fw-bold">${day.getDate()}/${day.getMonth() + 1}</div>
            </div>
        `;
    });
    
    return html;
}

/**
 * Genera HTML per una griglia di calendario settimanale con prenotazioni
 * @param {Array<Date>} weekDays - Array di 7 date
 * @param {Array} prenotazioni - Lista prenotazioni
 * @param {object} options - Opzioni: {onSlotClick: function, onBookingClick: function}
 * @returns {string} - HTML della griglia
 */
function renderCalendarGrid(weekDays, prenotazioni, options = {}) {
    const timeSlots = generateTimeSlots();
    let html = '<div class="calendar-grid">';
    
    // Header con giorni
    html += renderCalendarHeader(weekDays);
    
    // Griglia con slot orari
    timeSlots.forEach(time => {
        weekDays.forEach(day => {
            const datetime = combineDatetime(day, time);
            const bookings = getBookingsInSlot(datetime, prenotazioni);
            const isPastSlot = isPast(new Date(datetime.replace(' ', 'T')));
            
            html += `
                <div class="calendar-time-slot ${bookings.length > 0 ? 'has-booking' : ''} ${isPastSlot ? 'past-slot' : ''}"
                     data-datetime="${datetime}"
                     data-time="${time}">
                    <small class="text-muted">${time}</small>
            `;
            
            // Mostra prenotazioni in questo slot
            bookings.forEach(booking => {
                const prenStart = new Date(booking.data_ora_inizio.replace(' ', 'T'));
                const prenStartTime = `${String(prenStart.getHours()).padStart(2, '0')}:00`;
                
                // Mostra solo se la prenotazione inizia in questo slot
                if (prenStartTime === time) {
                    html += `
                        <div class="booking-block" data-booking-id="${booking.id}">
                            <strong>${booking.attivita || 'Prenotazione'}</strong><br>
                            <small>${time} - ${booking.durata}h</small>
                        </div>
                    `;
                }
            });
            
            html += '</div>';
        });
    });
    
    html += '</div>';
    
    return html;
}

/**
 * Formatta un range di date per visualizzazione
 * @param {Date} start - Data inizio
 * @param {Date} end - Data fine
 * @returns {string} - Range formattato (es. "1-7 Gennaio 2025")
 */
function formatWeekRange(start, end) {
    const monthNames = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                        'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
    
    if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()) {
        return `${start.getDate()}-${end.getDate()} ${monthNames[start.getMonth()]} ${start.getFullYear()}`;
    } else if (start.getFullYear() === end.getFullYear()) {
        return `${start.getDate()} ${monthNames[start.getMonth()]} - ${end.getDate()} ${monthNames[end.getMonth()]} ${start.getFullYear()}`;
    } else {
        return `${start.getDate()} ${monthNames[start.getMonth()]} ${start.getFullYear()} - ${end.getDate()} ${monthNames[end.getMonth()]} ${end.getFullYear()}`;
    }
}

// ==========================================
// EXPORT (se necessario per moduli)
// ==========================================

// Se usato come modulo ES6:
// export { getMondayOfWeek, getWeekDays, getWeekRange, formatDateISO, 
//          getPreviousWeek, getNextWeek, isToday, isPast, generateTimeSlots, 
//          combineDatetime, isSlotAvailable, getBookingsInSlot, renderCalendarHeader, 
//          renderCalendarGrid, formatWeekRange };
