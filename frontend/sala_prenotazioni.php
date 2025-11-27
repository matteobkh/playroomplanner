<?php
/**
 * File: frontend/sala_prenotazioni.php
 * Percorso: playroomplanner/frontend/sala_prenotazioni.php
 * Scopo: Visualizzazione calendario prenotazioni sale con gestione completa
 * Dipendenze: common/auth.php
 */

require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/config.php';
requireLogin();

$user = getCurrentUser();

// Ottieni lista settori e sale
$conn = getDbConnection();

$sql = "SELECT DISTINCT nome_settore FROM settore ORDER BY nome_settore";
$settori_result = $conn->query($sql);
$settori = [];
while ($row = $settori_result->fetch_assoc()) {
    $settori[] = $row;
}

// Ottieni tutte le sale per popolare il dropdown
$sql = "SELECT nome_sala, nome_settore, capienza FROM sala ORDER BY nome_settore, nome_sala";
$sale_result = $conn->query($sql);
$sale = [];
while ($row = $sale_result->fetch_assoc()) {
    $sale[] = $row;
}

// Ottieni lista utenti per gli inviti (solo del settore se non responsabile)
if (isResponsabile()) {
    $sql = "SELECT email, nome, cognome, nome_settore FROM iscritto ORDER BY nome, cognome";
} else {
    $sql = "SELECT email, nome, cognome, nome_settore FROM iscritto WHERE nome_settore = ? ORDER BY nome, cognome";
}
$stmt = $conn->prepare($sql);
if (!isResponsabile()) {
    $stmt->bind_param('s', $user['nome_settore']);
}
$stmt->execute();
$utenti_result = $stmt->get_result();
$utenti = [];
while ($row = $utenti_result->fetch_assoc()) {
    $utenti[] = $row;
}
$stmt->close();

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prenotazioni Sale - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .calendar-time-slot {
            min-height: 50px;
            border: 1px solid #dee2e6;
            padding: 5px;
            position: relative;
            background: white;
        }
        .calendar-time-slot.clickable:hover {
            background: #e7f3ff;
            cursor: pointer;
        }
        .calendar-time-slot.past-slot {
            background: #f8f9fa;
            opacity: 0.6;
        }
        .booking-block {
            background: #0d6efd;
            color: white;
            padding: 5px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-top: 5px;
            cursor: pointer;
        }
        .booking-block:hover {
            background: #0b5ed7;
        }
        .time-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'components/nav.php'; ?>

    <div class="container-fluid py-4">
        <h2 class="mb-4">
            <i class="bi bi-calendar-check"></i> Prenotazioni Sale
        </h2>

        <div id="alertContainer"></div>

        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Settore</label>
                        <select class="form-select" id="settoreFilter">
                            <option value="">Seleziona settore</option>
                            <?php foreach ($settori as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['nome_settore']); ?>"
                                    <?php echo ($user['nome_settore'] === $s['nome_settore']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['nome_settore']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Sala</label>
                        <select class="form-select" id="salaFilter">
                            <option value="">Seleziona prima il settore</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Settimana</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" id="prevWeek" type="button">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <input type="date" class="form-control" id="dateFilter" 
                                   value="<?php echo date('Y-m-d'); ?>">
                            <button class="btn btn-outline-secondary" id="nextWeek" type="button">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="loadCalendar">
                            <i class="bi bi-search"></i> Carica
                        </button>
                    </div>
                </div>
                <?php if (isResponsabile()): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <button class="btn btn-success" id="createBookingBtn" disabled>
                            <i class="bi bi-plus-circle"></i> Nuova Prenotazione
                        </button>
                        <small class="text-muted ms-2">Seleziona una sala e carica il calendario per creare prenotazioni</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendario -->
        <div class="card">
            <div class="card-header calendar-header text-white">
                <h4 class="mb-0" id="weekTitle">Calendario Prenotazioni</h4>
            </div>
            <div class="card-body p-0">
                <div id="calendarContainer" class="p-4 text-center text-muted">
                    <i class="bi bi-calendar3 display-1"></i>
                    <p class="mt-3">Seleziona settore, sala e data, poi clicca "Carica"</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuova Prenotazione -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Nuova Prenotazione
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data e Ora Inizio *</label>
                                <input type="datetime-local" class="form-control" id="booking_datetime" 
                                       step="3600" required>
                                <small class="text-muted">Solo ore intere (09:00 - 23:00)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durata (ore) *</label>
                                <input type="number" class="form-control" id="booking_duration" 
                                       min="1" max="8" value="2" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attività</label>
                            <input type="text" class="form-control" id="booking_activity" 
                                   placeholder="Es: Prova musicale, Lezione di danza...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Criterio</label>
                            <select class="form-select" id="booking_criterio">
                                <option value="tutti">Tutti</option>
                                <option value="settore">Solo settore</option>
                                <option value="invito">Solo su invito</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Invita Partecipanti (opzionale)</label>
                            <select class="form-select" id="booking_invitati" multiple size="5">
                                <?php foreach ($utenti as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u['email']); ?>">
                                        <?php echo htmlspecialchars($u['nome'] . ' ' . $u['cognome'] . ' (' . $u['nome_settore'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Tieni premuto Ctrl (Cmd su Mac) per selezionare più utenti</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Sala:</strong> <span id="modal_sala_name"></span><br>
                            <strong>Settore:</strong> <span id="modal_settore_name"></span><br>
                            <strong>Capienza:</strong> <span id="modal_sala_capienza"></span> persone
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-success" id="saveBookingBtn">
                        <i class="bi bi-check-circle"></i> Crea Prenotazione
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dettagli Prenotazione -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle"></i> Dettagli Prenotazione
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Contenuto dinamico -->
                </div>
                <div class="modal-footer" id="detailsActions">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/calendar.js"></script>
    <script>
        // Dati sale disponibili dal PHP
        const allSale = <?php echo json_encode($sale); ?>;
        let currentPrenotazioni = [];
        let currentSala = null;
        let currentSettore = null;
        let currentDate = null;
        let bookingModal = null;
        let detailsModal = null;

        // Inizializzazione
        document.addEventListener('DOMContentLoaded', function() {
            bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
            detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

            // Event listeners
            document.getElementById('settoreFilter').addEventListener('change', onSettoreChange);
            document.getElementById('loadCalendar').addEventListener('click', loadCalendar);
            document.getElementById('prevWeek').addEventListener('click', navigatePrevWeek);
            document.getElementById('nextWeek').addEventListener('click', navigateNextWeek);
            
            <?php if (isResponsabile()): ?>
            document.getElementById('createBookingBtn').addEventListener('click', openBookingModal);
            document.getElementById('saveBookingBtn').addEventListener('click', saveBooking);
            <?php endif; ?>

            // Carica sale del settore preselezionato
            if (document.getElementById('settoreFilter').value) {
                onSettoreChange();
            }
        });

        // Carica sale quando cambia il settore
        function onSettoreChange() {
            const settore = document.getElementById('settoreFilter').value;
            const salaSelect = document.getElementById('salaFilter');
            
            salaSelect.innerHTML = '<option value="">Seleziona sala</option>';
            
            if (!settore) return;
            
            const saleSettore = allSale.filter(s => s.nome_settore === settore);
            saleSettore.forEach(sala => {
                const option = document.createElement('option');
                option.value = sala.nome_sala;
                option.dataset.capienza = sala.capienza;
                option.textContent = `${sala.nome_sala} (capienza: ${sala.capienza})`;
                salaSelect.appendChild(option);
            });
        }

        // Carica calendario
        async function loadCalendar() {
            const sala = document.getElementById('salaFilter').value;
            const settore = document.getElementById('settoreFilter').value;
            const date = document.getElementById('dateFilter').value;
            
            if (!sala || !settore) {
                showAlert('Seleziona settore e sala', 'warning');
                return;
            }
            
            currentSala = sala;
            currentSettore = settore;
            currentDate = date;
            
            showLoading();
            
            try {
                const prenotazioni = await getSalaPrenotazioni(sala, settore, date);
                currentPrenotazioni = prenotazioni;
                
                renderCalendar();
                
                // Abilita pulsante crea prenotazione
                <?php if (isResponsabile()): ?>
                document.getElementById('createBookingBtn').disabled = false;
                <?php endif; ?>
                
            } catch (error) {
                showAlert('Errore caricamento calendario: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        // Renderizza calendario
        function renderCalendar() {
            const weekDays = getWeekDays(currentDate);
            const weekRange = getWeekRange(currentDate);
            const timeSlots = generateTimeSlots();
            
            document.getElementById('weekTitle').textContent = 
                `${currentSala} - ${formatWeekRange(weekRange.start, weekRange.end)}`;
            
            let html = '<div class="table-responsive"><table class="table table-bordered mb-0">';
            
            // Header con giorni
            html += '<thead><tr><th style="width: 80px;">Ora</th>';
            const dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
            weekDays.forEach((day, index) => {
                const isCurrentDay = isToday(day);
                html += `<th class="text-center ${isCurrentDay ? 'bg-primary text-white' : ''}">
                    ${dayNames[index]}<br>${day.getDate()}/${day.getMonth() + 1}
                </th>`;
            });
            html += '</tr></thead><tbody>';
            
            // Righe con slot orari
            timeSlots.forEach(time => {
                html += `<tr><td class="time-label">${time}</td>`;
                
                weekDays.forEach(day => {
                    const datetime = combineDatetime(day, time);
                    const bookings = getBookingsInSlot(datetime, currentPrenotazioni);
                    const isPastSlot = isPast(new Date(datetime.replace(' ', 'T')));
                    const isAvailable = bookings.length === 0 && !isPastSlot;
                    
                    html += `<td class="calendar-time-slot ${isAvailable ? 'clickable' : ''} ${isPastSlot ? 'past-slot' : ''}"
                                 data-datetime="${datetime}">`;
                    
                    // Mostra prenotazioni in questo slot
                    bookings.forEach(booking => {
                        const prenStart = new Date(booking.data_ora_inizio.replace(' ', 'T'));
                        const prenStartTime = `${String(prenStart.getHours()).padStart(2, '0')}:00`;
                        
                        if (prenStartTime === time) {
                            html += `<div class="booking-block" data-booking-id="${booking.id}">
                                <strong>${escapeHtml(booking.attivita || 'Prenotazione')}</strong><br>
                                <small>${booking.durata}h - ${escapeHtml(booking.responsabile_nome)}</small>
                            </div>`;
                        }
                    });
                    
                    html += '</td>';
                });
                
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            document.getElementById('calendarContainer').innerHTML = html;
            
            // Aggiungi event listeners
            document.querySelectorAll('.calendar-time-slot.clickable').forEach(el => {
                el.addEventListener('click', function() {
                    <?php if (isResponsabile()): ?>
                    const datetime = this.dataset.datetime;
                    openBookingModal(datetime);
                    <?php endif; ?>
                });
            });
            
            document.querySelectorAll('.booking-block').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const bookingId = this.dataset.bookingId;
                    showBookingDetails(bookingId);
                });
            });
        }

        // Naviga settimana precedente
        function navigatePrevWeek() {
            const date = document.getElementById('dateFilter').value;
            const prev = getPreviousWeek(new Date(date));
            document.getElementById('dateFilter').value = formatDateISO(prev);
        }

        // Naviga settimana successiva
        function navigateNextWeek() {
            const date = document.getElementById('dateFilter').value;
            const next = getNextWeek(new Date(date));
            document.getElementById('dateFilter').value = formatDateISO(next);
        }

        // Apri modal nuova prenotazione
        function openBookingModal(datetime = null) {
            document.getElementById('modal_sala_name').textContent = currentSala;
            document.getElementById('modal_settore_name').textContent = currentSettore;
            
            const salaSelect = document.getElementById('salaFilter');
            const selectedOption = salaSelect.options[salaSelect.selectedIndex];
            document.getElementById('modal_sala_capienza').textContent = 
                selectedOption.dataset.capienza || 'N/A';
            
            // Imposta datetime se fornito
            if (datetime) {
                const dt = new Date(datetime.replace(' ', 'T'));
                const formatted = dt.toISOString().slice(0, 16);
                document.getElementById('booking_datetime').value = formatted;
            }
            
            // Reset form
            document.getElementById('bookingForm').reset();
            
            bookingModal.show();
        }

        // Salva prenotazione
        async function saveBooking() {
            const datetime = document.getElementById('booking_datetime').value;
            const duration = parseInt(document.getElementById('booking_duration').value);
            const activity = document.getElementById('booking_activity').value;
            const criterio = document.getElementById('booking_criterio').value;
            
            if (!datetime || !duration) {
                showAlert('Compila tutti i campi obbligatori', 'warning');
                return;
            }
            
            // Converti datetime in formato MySQL
            const dt = new Date(datetime);
            const mysqlDatetime = dt.getFullYear() + '-' +
                String(dt.getMonth() + 1).padStart(2, '0') + '-' +
                String(dt.getDate()).padStart(2, '0') + ' ' +
                String(dt.getHours()).padStart(2, '0') + ':00:00';
            
            // Raccogli invitati
            const invitatiSelect = document.getElementById('booking_invitati');
            const invitati = Array.from(invitatiSelect.selectedOptions).map(opt => opt.value);
            
            const data = {
                data_ora_inizio: mysqlDatetime,
                durata: duration,
                attivita: activity || null,
                criterio: criterio,
                nome_settore: currentSettore,
                nome_sala: currentSala,
                invitati: invitati
            };
            
            try {
                showLoading();
                const response = await createPrenotazione(data);
                
                if (response.success) {
                    showAlert('Prenotazione creata con successo!', 'success');
                    bookingModal.hide();
                    loadCalendar(); // Ricarica calendario
                } else {
                    showAlert(response.error || 'Errore durante la creazione', 'danger');
                }
            } catch (error) {
                showAlert('Errore: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        // Mostra dettagli prenotazione
        async function showBookingDetails(id) {
            const booking = currentPrenotazioni.find(p => p.id == id);
            if (!booking) return;
            
            const isOwner = '<?php echo $user['email']; ?>' === booking.email_responsabile;
            
            let html = `
                <div class="mb-3">
                    <strong>Attività:</strong> ${escapeHtml(booking.attivita || 'N/A')}<br>
                    <strong>Data e Ora:</strong> ${formatDate(booking.data_ora_inizio, true)}<br>
                    <strong>Durata:</strong> ${booking.durata} ore<br>
                    <strong>Sala:</strong> ${escapeHtml(booking.nome_sala)}<br>
                    <strong>Settore:</strong> ${escapeHtml(booking.nome_settore)}<br>
                    <strong>Responsabile:</strong> ${escapeHtml(booking.responsabile_nome + ' ' + booking.responsabile_cognome)}
                </div>
            `;
            
            document.getElementById('detailsContent').innerHTML = html;
            
            // Azioni disponibili
            let actions = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>';
            
            <?php if (isResponsabile()): ?>
            if (isOwner) {
                actions += ` <button type="button" class="btn btn-danger" onclick="deleteBooking(${id})">
                    <i class="bi bi-trash"></i> Elimina
                </button>`;
            }
            <?php endif; ?>
            
            document.getElementById('detailsActions').innerHTML = actions;
            
            detailsModal.show();
        }

        // Elimina prenotazione
        async function deleteBooking(id) {
            if (!confirm('Sei sicuro di voler eliminare questa prenotazione?')) return;
            
            try {
                showLoading();
                const response = await deletePrenotazione(id);
                
                if (response.success) {
                    showAlert('Prenotazione eliminata', 'success');
                    detailsModal.hide();
                    loadCalendar();
                } else {
                    showAlert(response.error || 'Errore durante l\'eliminazione', 'danger');
                }
            } catch (error) {
                showAlert('Errore: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html>
