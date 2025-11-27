<?php
/**
 * File: frontend/user_impegni.php
 * Visualizzazione impegni utente con possibilità di rispondere agli inviti
 */

require_once __DIR__ . '/../common/auth.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Impegni - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'components/nav.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-list-check"></i> I Miei Impegni</h2>
        
        <div id="alertContainer"></div>

        <!-- Filtro Settimana -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">Settimana</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" id="prevWeek" type="button">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <input type="date" class="form-control" id="dateFilter" value="<?php echo date('Y-m-d'); ?>">
                            <button class="btn btn-outline-secondary" id="nextWeek" type="button">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button class="btn btn-primary" id="loadImpegni">
                            <i class="bi bi-search"></i> Carica Impegni
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legenda -->
        <div class="mb-3">
            <span class="badge bg-warning text-dark me-2"><i class="bi bi-clock"></i> In attesa</span>
            <span class="badge bg-success me-2"><i class="bi bi-check"></i> Accettato</span>
            <span class="badge bg-danger"><i class="bi bi-x"></i> Rifiutato</span>
        </div>

        <!-- Lista Impegni -->
        <div id="impegniContainer">
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar3 display-1"></i>
                <p class="mt-3">Clicca "Carica Impegni" per visualizzare i tuoi impegni</p>
            </div>
        </div>
    </div>

    <!-- Modal Risposta -->
    <div class="modal fade" id="rispostaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rispondi all'invito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="rispostaInfo"></div>
                    <div class="mb-3" id="motivazioneContainer" style="display: none;">
                        <label class="form-label">Motivazione del rifiuto *</label>
                        <textarea class="form-control" id="motivazione" rows="3" placeholder="Inserisci la motivazione..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-danger" id="btnRifiuta">
                        <i class="bi bi-x-circle"></i> Rifiuta
                    </button>
                    <button type="button" class="btn btn-success" id="btnAccetta">
                        <i class="bi bi-check-circle"></i> Accetta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/calendar.js"></script>
    <script>
        const userEmail = '<?php echo htmlspecialchars($user['email']); ?>';
        let currentImpegni = [];
        let currentPrenotazioneId = null;
        let rispostaModal = null;

        document.addEventListener('DOMContentLoaded', function() {
            rispostaModal = new bootstrap.Modal(document.getElementById('rispostaModal'));
            
            document.getElementById('loadImpegni').addEventListener('click', loadImpegni);
            
            document.getElementById('prevWeek').addEventListener('click', function() {
                const date = document.getElementById('dateFilter').value;
                const prev = getPreviousWeek(new Date(date));
                document.getElementById('dateFilter').value = formatDateISO(prev);
            });
            
            document.getElementById('nextWeek').addEventListener('click', function() {
                const date = document.getElementById('dateFilter').value;
                const next = getNextWeek(new Date(date));
                document.getElementById('dateFilter').value = formatDateISO(next);
            });

            document.getElementById('btnAccetta').addEventListener('click', () => sendRisposta('si'));
            document.getElementById('btnRifiuta').addEventListener('click', () => sendRisposta('no'));

            // Carica automaticamente
            loadImpegni();
        });

        async function loadImpegni() {
            const date = document.getElementById('dateFilter').value;
            
            showLoading();
            
            try {
                const impegni = await getUserImpegni(userEmail, date);
                currentImpegni = impegni;
                
                renderImpegni(impegni);
                
            } catch (error) {
                console.error('Error loading impegni:', error);
                showAlert('Errore caricamento impegni: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        function renderImpegni(impegni) {
            const container = document.getElementById('impegniContainer');
            
            if (impegni.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x display-1"></i>
                        <p class="mt-3">Nessun impegno per questa settimana</p>
                    </div>
                `;
                return;
            }
            
            // Ordina per data
            impegni.sort((a, b) => new Date(a.data_ora_inizio) - new Date(b.data_ora_inizio));
            
            let html = '';
            impegni.forEach(imp => {
                let statusBadge, statusClass;
                
                if (imp.risposta === 'si') {
                    statusBadge = '<span class="badge bg-success"><i class="bi bi-check"></i> Accettato</span>';
                    statusClass = 'border-success';
                } else if (imp.risposta === 'no') {
                    statusBadge = '<span class="badge bg-danger"><i class="bi bi-x"></i> Rifiutato</span>';
                    statusClass = 'border-danger';
                } else {
                    statusBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> In attesa</span>';
                    statusClass = 'border-warning';
                }
                
                const dataOra = new Date(imp.data_ora_inizio.replace(' ', 'T'));
                const isPast = dataOra < new Date();
                
                html += `
                    <div class="card mb-3 ${statusClass}" style="border-left-width: 4px;">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="card-title">
                                        ${escapeHtml(imp.attivita || 'Prenotazione')}
                                        ${statusBadge}
                                    </h5>
                                    <p class="mb-1">
                                        <i class="bi bi-calendar"></i> 
                                        <strong>${formatDate(imp.data_ora_inizio, true)}</strong>
                                    </p>
                                    <p class="mb-1">
                                        <i class="bi bi-clock"></i> Durata: ${imp.durata} ore
                                    </p>
                                    <p class="mb-1">
                                        <i class="bi bi-door-open"></i> 
                                        Sala: ${escapeHtml(imp.nome_sala)} (${escapeHtml(imp.nome_settore)})
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-person"></i> 
                                        Responsabile: ${escapeHtml((imp.responsabile_nome || '') + ' ' + (imp.responsabile_cognome || ''))}
                                    </p>
                                    ${imp.motivazione ? `
                                        <p class="mb-0 mt-2 text-muted">
                                            <i class="bi bi-chat-left-text"></i> 
                                            <em>Motivazione: ${escapeHtml(imp.motivazione)}</em>
                                        </p>
                                    ` : ''}
                                </div>
                                <div class="col-md-4 text-end d-flex flex-column justify-content-center">
                                    ${!imp.risposta && !isPast ? `
                                        <button class="btn btn-primary mb-2" onclick="openRispostaModal(${imp.id})">
                                            <i class="bi bi-reply"></i> Rispondi
                                        </button>
                                    ` : ''}
                                    ${imp.risposta === 'si' && !isPast ? `
                                        <button class="btn btn-outline-danger btn-sm" onclick="rimuoviPartecipazione(${imp.id})">
                                            <i class="bi bi-x-circle"></i> Rimuovi partecipazione
                                        </button>
                                    ` : ''}
                                    ${isPast ? `
                                        <span class="text-muted"><i class="bi bi-clock-history"></i> Passato</span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function openRispostaModal(prenotazioneId) {
            const impegno = currentImpegni.find(i => i.id == prenotazioneId);
            if (!impegno) return;
            
            currentPrenotazioneId = prenotazioneId;
            
            document.getElementById('rispostaInfo').innerHTML = `
                <p><strong>Attività:</strong> ${escapeHtml(impegno.attivita || 'Prenotazione')}</p>
                <p><strong>Data:</strong> ${formatDate(impegno.data_ora_inizio, true)}</p>
                <p><strong>Sala:</strong> ${escapeHtml(impegno.nome_sala)}</p>
            `;
            
            document.getElementById('motivazione').value = '';
            document.getElementById('motivazioneContainer').style.display = 'none';
            
            rispostaModal.show();
        }

        async function sendRisposta(risposta) {
            if (!currentPrenotazioneId) return;
            
            let motivazione = null;
            
            if (risposta === 'no') {
                // Mostra campo motivazione se non visibile
                const motivazioneContainer = document.getElementById('motivazioneContainer');
                if (motivazioneContainer.style.display === 'none') {
                    motivazioneContainer.style.display = 'block';
                    document.getElementById('motivazione').focus();
                    return; // Richiedi di inserire la motivazione
                }
                
                motivazione = document.getElementById('motivazione').value.trim();
                if (!motivazione) {
                    showAlert('La motivazione è obbligatoria per i rifiuti', 'warning');
                    return;
                }
            }
            
            try {
                showLoading();
                await rispondiInvito(currentPrenotazioneId, userEmail, risposta, motivazione);
                
                showAlert('Risposta registrata con successo!', 'success');
                rispostaModal.hide();
                currentPrenotazioneId = null;
                
                await loadImpegni();
            } catch (error) {
                showAlert('Errore: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        async function rimuoviPartecipazione(prenotazioneId) {
            const motivazione = prompt('Inserisci una motivazione per la rimozione:');
            if (motivazione === null) return; // Annullato
            if (!motivazione.trim()) {
                showAlert('La motivazione è obbligatoria', 'warning');
                return;
            }
            
            try {
                showLoading();
                await rispondiInvito(prenotazioneId, userEmail, 'no', motivazione);
                
                showAlert('Partecipazione rimossa', 'success');
                await loadImpegni();
            } catch (error) {
                showAlert('Errore: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }
    </script>
</body>
</html>
