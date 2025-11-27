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
    <title>I Miei Impegni - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'components/nav.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4">I Miei Impegni</h2>
        
        <div id="alertContainer"></div>

        <!-- Filtro Settimana -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Settimana</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" id="prevWeek">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <input type="date" class="form-control" id="dateFilter" value="<?php echo date('Y-m-d'); ?>">
                            <button class="btn btn-outline-secondary" id="nextWeek">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-primary" id="loadImpegni">
                            <i class="bi bi-search"></i> Carica Impegni
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista Impegni -->
        <div id="impegniContainer">
            <p class="text-center text-muted">Clicca "Carica Impegni" per visualizzare</p>
        </div>
    </div>

    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/calendar.js"></script>
    <script>
        async function loadImpegni() {
            const date = document.getElementById('dateFilter').value;
            const email = '<?php echo $user['email']; ?>';
            
            showLoading();
            
            try {
                const impegni = await getUserImpegni(email, date);
                
                let html = '';
                if (impegni.length === 0) {
                    html = '<p class="text-center text-muted">Nessun impegno per questa settimana</p>';
                } else {
                    impegni.forEach(imp => {
                        const statusBadge = imp.risposta === 'si' ? 
                            '<span class="badge bg-success">Accettato</span>' :
                            imp.risposta === 'no' ?
                            '<span class="badge bg-danger">Rifiutato</span>' :
                            '<span class="badge bg-warning">In attesa</span>';
                        
                        html += `
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5>${imp.attivita || 'Prenotazione'}</h5>
                                            <p class="mb-1"><strong>Data e Ora:</strong> ${formatDate(imp.data_ora_inizio, true)}</p>
                                            <p class="mb-1"><strong>Durata:</strong> ${imp.durata} ore</p>
                                            <p class="mb-1"><strong>Sala:</strong> ${imp.nome_sala} (${imp.nome_settore})</p>
                                            <p class="mb-1"><strong>Responsabile:</strong> ${imp.responsabile_nome} ${imp.responsabile_cognome}</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            ${statusBadge}
                                            ${!imp.risposta ? `
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-success" onclick="rispondi(${imp.id}, 'si')">
                                                        <i class="bi bi-check"></i> Accetta
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="rispondi(${imp.id}, 'no')">
                                                        <i class="bi bi-x"></i> Rifiuta
                                                    </button>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                document.getElementById('impegniContainer').innerHTML = html;
                
            } catch (error) {
                showAlert('Errore caricamento impegni: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        }

        async function rispondi(prenotazioneId, risposta) {
            const email = '<?php echo $user['email']; ?>';
            let motivazione = null;
            
            if (risposta === 'no') {
                motivazione = prompt('Inserisci una motivazione per il rifiuto:');
                if (!motivazione || motivazione.trim() === '') {
                    showAlert('La motivazione è obbligatoria per i rifiuti', 'warning');
                    return;
                }
            }
            
            try {
                await rispondiInvito(prenotazioneId, email, risposta, motivazione);
                showAlert('Risposta registrata con successo!', 'success');
                loadImpegni();
            } catch (error) {
                showAlert('Errore: ' + error.message, 'danger');
            }
        }

        document.getElementById('loadImpegni').addEventListener('click', loadImpegni);
        
        document.getElementById('prevWeek').addEventListener('click', function() {
            const date = document.getElementById('dateFilter').value;
            const prev = getPreviousWeek(date);
            document.getElementById('dateFilter').value = formatDateISO(prev);
        });
        
        document.getElementById('nextWeek').addEventListener('click', function() {
            const date = document.getElementById('dateFilter').value;
            const next = getNextWeek(date);
            document.getElementById('dateFilter').value = formatDateISO(next);
        });

        // Carica automaticamente
        loadImpegni();
    </script>
</body>
</html>