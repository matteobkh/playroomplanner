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

// Ottieni lista utenti per gli inviti
$sql = "SELECT email, nome, cognome, nome_settore FROM iscritto ORDER BY nome, cognome";
$utenti_result = $conn->query($sql);
$utenti = [];
while ($row = $utenti_result->fetch_assoc()) {
    $utenti[] = $row;
}

closeDbConnection($conn);

$isResp = isResponsabile();
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
        .time-column {
            width: 60px;
            min-width: 60px;
            background: #f8f9fa;
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
                    <div class="col-md-3 mb-2">
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
                    <div class="col-md-3 mb-2">
                        <label class="form-label fw-bold">Sala</label>
                        <select class="form-select" id="salaFilter">
                            <option value="">Seleziona prima il settore</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
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
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary w-100" id="loadCalendar">
                            <i class="bi bi-search"></i> Carica
                        </button>
                    </div>
                </div>
                <?php if ($isResp): ?>
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
                        <i class="bi bi-plus-circle"></i> <span id="modalTitle">Nuova Prenotazione</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data *</label>
                                <input type="date" class="form-control" id="booking_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ora Inizio *</label>
                                <select class="form-select" id="booking_hour" required>
                                    <?php for($h = 9; $h <= 23; $h++): ?>
                                        <option value="<?php echo sprintf('%02d', $h); ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">Prenotazioni solo ad ore intere (09:00 - 23:00)</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durata (ore) *</label>
                                <input type="number" class="form-control" id="booking_duration" 
                                       min="1" max="8" value="2" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Criterio</label>
                                <select class="form-select" id="booking_criterio">
                                    <option value="tutti">Tutti</option>
                                    <option value="settore">Solo settore</option>
                                    <option value="invito">Solo su invito</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attività</label>
                            <input type="text" class="form-control" id="booking_activity" 
                                   placeholder="Es: Prova musicale, Lezione di danza...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Invita Partecipanti (opzionale)</label>
                            <select class="form-select" id="booking_invitati" multiple size="6">
                                <?php foreach ($utenti as $u): ?>
                                    <?php if ($u['email'] !== $user['email']): ?>
                                    <option value="<?php echo htmlspecialchars($u['email']); ?>">
                                        <?php echo htmlspecialchars($u['nome'] . ' ' . $u['cognome']); ?>
                                        <?php if ($u['nome_settore']): ?>
                                            (<?php echo htmlspecialchars($u['nome_settore']); ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Tieni premuto Ctrl (Cmd su Mac) per selezionare più utenti</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Sala:</strong> <span id="modal_sala_name">-</span><br>
                            <strong>Settore:</strong> <span id="modal_settore_name">-</span><br>
                            <strong>Capienza:</strong> <span id="modal_sala_capienza">-</span> persone
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
        const isResponsabile = <?php echo $isResp ? 'true' : 'false'; ?>;
        const currentUserEmail = '<?php echo htmlspecialchars($user['email']); ?>';
        
        let currentPrenotazioni = [];
        let currentSala = null;
        let currentSettore = null;
        let currentDate = null;
        let currentCapienza = 0;
        let bookingModal = null;
        let detailsModal = null;

        // Inizializzazione
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sala prenotazioni initialized');
            
            bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
            detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

            // Event listeners
            document.getElementById('settoreFilter').addEventListener('change', onSettoreChange);
            document.getElementById('loadCalendar').addEventListener('click', loadCalendar);
            document.getElementById('prevWeek').addEventListener('click', navigatePrevWeek);
            document.getElementById('nextWeek').addEventListener('click', navigateNextWeek);
            
            if (isResponsabile) {
                document.getElementById('createBookingBtn').addEventListener('click', function() {
                    openBookingModal();
                });
                document.getElementById('saveBookingBtn').addEventListener('click', saveBooking);
            }

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
            
            // Ottieni capienza
            const salaSelect = document.getElementById('salaFilter');
            const selectedOption = salaSelect.options[salaSelect.selectedIndex];
            currentCapienza = selectedOption.dataset.capienza || 0;
            
            showLoading();
            
            try {
                const prenotazioni = await getSalaPrenotazioni(sala, settore, date);
                currentPrenotazioni = prenotazioni;
                
                renderCalendar();
                
                // Abilita pulsante crea prenotazione
                if (isResponsabile) {
                    document.getElementById('createBookingBtn').disabled = false;
                }
                
            } catch (error) {
                console.error('Error loading calendar:', error);
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
            html += '<thead><tr><th class="time-column text-center">Ora</th>';
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
                html += `<tr><td class="time-column time-label text-center align-middle">${time}</td>`;
                
                weekDays.forEach(day => {
                    const datetime = combineDatetime(day, time);
                    const bookings = getBookingsInSlot(datetime, currentPrenotazioni);
                    const slotDateTime = new Date(datetime.replace(' ', 'T'));
                    const isPastSlot = isPast(slotDateTime);
                    const isAvailable = bookings.length === 0 && !isPastSlot;
                    
                    let cellClass = 'calendar-time-slot';
                    if (isAvailable && isResponsabile) cellClass += ' clickable';
                    if (isPastSlot) cellClass += ' past-slot';
                    
                    html += `<td class="${cellClass}" data-datetime="${datetime}">`;
                    
                    // Mostra prenotazioni in questo slot
                    bookings.forEach(booking => {
                        const prenStart = new Date(booking.data_ora_inizio.replace(' ', 'T'));
                        const prenStartTime = `${String(prenStart.getHours()).padStart(2, '0')}:00`;
                        
                        if (prenStartTime === time) {
                            html += `<div class="booking-block" data-booking-id="${booking.id}">
                                <strong>${escapeHtml(booking.attivita || 'Prenotazione')}</strong><br>
                                <small>${booking.durata}h - ${escapeHtml(booking.responsabile_nome || '')}</small>
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
            if (isResponsabile) {
                document.querySelectorAll('.calendar-time-slot.clickable').forEach(el => {
                    el.addEventListener('click', function() {
                        const datetime = this.dataset.datetime;
                        openBookingModal(datetime);
                    });
                });
            }
            
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
            document.getElementById('modal_sala_name').textContent = currentSala || '-';
            document.getElementById('modal_settore_name').textContent = currentSettore || '-';
            document.getElementById('modal_sala_capienza').textContent = currentCapienza || '-';
            
            // Reset form
            document.getElementById('bookingForm').reset();
            
            // Imposta datetime se fornito
            if (datetime) {
                const parts = datetime.split(' ');
                if (parts.length >= 2) {
                    document.getElementById('booking_date').value = parts[0];
                    const hour = parts[1].split(':')[0];
                    document.getElementById('booking_hour').value = hour;
                }
            } else if (currentDate) {
                document.getElementById('booking_date').value = currentDate;
            }
            
            bookingModal.show();
        }

        // Salva prenotazione
        async function saveBooking() {
            const date = document.getElementById('booking_date').value;
            const hour = document.getElementById('booking_hour').value;
            const duration = parseInt(document.getElementById('booking_duration').value);
            const activity = document.getElementById('booking_activity').value.trim();
            const criterio = document.getElementById('booking_criterio').value;
            
            // Validazione
            if (!date || !hour || !duration) {
                showAlert('Compila tutti i campi obbligatori', 'warning');
                return;
            }
            
            if (!currentSala || !currentSettore) {
                showAlert('Seleziona prima una sala', 'warning');
                return;
            }
            
            if (duration < 1 || duration > 8) {
                showAlert('La durata deve essere tra 1 e 8 ore', 'warning');
                return;
            }
            
            // Costruisci datetime nel formato corretto
            const mysqlDatetime = `${date} ${hour}:00:00`;
            
            console.log('Saving booking with datetime:', mysqlDatetime);
            
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
            
            console.log('Booking data:', data);
            
            try {
                showLoading();
                const response = await createPrenotazione(data);
                
                console.log('Create response:', response);
                
                if (response.success) {
                    showAlert('Prenotazione creata con successo!', 'success');
                    bookingModal.hide();
                    await loadCalendar(); // Ricarica calendario
                } else {
                    showAlert(response.error || 'Errore durante la creazione', 'danger');
                }
            } catch (error) {
                console.error('Save booking error:', error);
                showAlert('Errore: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        // Mostra dettagli prenotazione
        function showBookingDetails(id) {
            const booking = currentPrenotazioni.find(p => p.id == id);
            if (!booking) {
                console.error('Booking not found:', id);
                return;
            }
            
            const isOwner = currentUserEmail === booking.email_responsabile;
            
            let html = `
                <div class="mb-3">
                    <p><strong>Attività:</strong> ${escapeHtml(booking.attivita || 'N/A')}</p>
                    <p><strong>Data e Ora:</strong> ${formatDate(booking.data_ora_inizio, true)}</p>
                    <p><strong>Durata:</strong> ${booking.durata} ore</p>
                    <p><strong>Sala:</strong> ${escapeHtml(booking.nome_sala)}</p>
                    <p><strong>Settore:</strong> ${escapeHtml(booking.nome_settore)}</p>
                    <p><strong>Responsabile:</strong> ${escapeHtml((booking.responsabile_nome || '') + ' ' + (booking.responsabile_cognome || ''))}</p>
                </div>
            `;
            
            document.getElementById('detailsContent').innerHTML = html;
            
            // Azioni disponibili
            let actions = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>';
            
            if (isResponsabile && isOwner) {
                actions += ` <button type="button" class="btn btn-danger" onclick="deleteBooking(${id})">
                    <i class="bi bi-trash"></i> Elimina
                </button>`;
            }
            
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
                    await loadCalendar();
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
