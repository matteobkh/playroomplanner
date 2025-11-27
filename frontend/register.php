<?php
/**
 * File: frontend/register.php
 * Pagina di registrazione nuovo utente
 */

require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/config.php';

// Se già autenticato, redirect alla home
initSession();
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

// Recupera lista settori
$settori = [];
try {
    $conn = getDbConnection();
    $sql = "SELECT nome_settore FROM settore ORDER BY nome_settore";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $settori[] = $row['nome_settore'];
    }
    closeDbConnection($conn);
} catch (Exception $e) {
    // Ignora errori, lista vuota
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold"><i class="bi bi-diagram-3-fill text-primary"></i> Registrazione</h2>
                            <p class="text-muted">Crea un nuovo account</p>
                        </div>

                        <div id="alertContainer"></div>

                        <form id="registerForm">
                            <!-- Dati Personali -->
                            <h5 class="mb-3"><i class="bi bi-person"></i> Dati Personali</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="cognome" class="form-label">Cognome *</label>
                                    <input type="text" class="form-control" id="cognome" name="cognome" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="data_nascita" class="form-label">Data di Nascita *</label>
                                <input type="date" class="form-control" id="data_nascita" name="data_nascita" required>
                            </div>

                            <!-- Dati Accesso -->
                            <h5 class="mb-3 mt-4"><i class="bi bi-key"></i> Dati di Accesso</h5>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="tua@email.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="8" required>
                                <small class="form-text text-muted">Minimo 8 caratteri</small>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Conferma Password *</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" minlength="8" required>
                            </div>

                            <!-- Dati Organizzazione -->
                            <h5 class="mb-3 mt-4"><i class="bi bi-building"></i> Dati Organizzativi</h5>

                            <div class="mb-3">
                                <label for="nome_ruolo" class="form-label">Ruolo *</label>
                                <select class="form-select" id="nome_ruolo" name="nome_ruolo" required>
                                    <option value="">Seleziona un ruolo</option>
                                    <option value="allievo">Allievo</option>
                                    <option value="docente">Docente</option>
                                    <option value="tecnico">Tecnico</option>
                                    <option value="responsabile">Responsabile</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="nome_settore" class="form-label">Settore</label>
                                <select class="form-select" id="nome_settore" name="nome_settore">
                                    <option value="">Nessuno</option>
                                    <?php foreach ($settori as $settore): ?>
                                        <option value="<?php echo htmlspecialchars($settore); ?>">
                                            <?php echo htmlspecialchars($settore); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="dataInizioContainer" style="display: none;">
                                <label for="data_inizio" class="form-label">Data Inizio Ruolo *</label>
                                <input type="date" class="form-control" id="data_inizio" name="data_inizio">
                                <small class="form-text text-muted">Obbligatoria per i responsabili</small>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus"></i> Registrati
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Hai già un account? 
                                <a href="login.php" class="text-decoration-none">Accedi</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="../index.php" class="text-muted text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Torna alla home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        // Mostra/nascondi campo data_inizio
        document.getElementById('nome_ruolo').addEventListener('change', function() {
            const container = document.getElementById('dataInizioContainer');
            const input = document.getElementById('data_inizio');
            
            if (this.value === 'responsabile') {
                container.style.display = 'block';
                input.required = true;
            } else {
                container.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        });

        // Submit form
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                nome: document.getElementById('nome').value.trim(),
                cognome: document.getElementById('cognome').value.trim(),
                data_nascita: document.getElementById('data_nascita').value,
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
                nome_ruolo: document.getElementById('nome_ruolo').value,
                nome_settore: document.getElementById('nome_settore').value || null,
                data_inizio: document.getElementById('data_inizio').value || null
            };
            
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validazione
            if (!formData.nome || !formData.cognome || !formData.data_nascita || 
                !formData.email || !formData.password || !formData.nome_ruolo) {
                showAlert('Compila tutti i campi obbligatori', 'danger');
                return;
            }
            
            if (formData.password !== confirmPassword) {
                showAlert('Le password non corrispondono', 'danger');
                return;
            }
            
            if (formData.password.length < 8) {
                showAlert('La password deve essere di almeno 8 caratteri', 'danger');
                return;
            }
            
            if (formData.nome_ruolo === 'responsabile' && !formData.data_inizio) {
                showAlert('Per i responsabili è obbligatoria la data di inizio ruolo', 'danger');
                return;
            }
            
            try {
                showLoading();
                const response = await apiCall('/users', 'POST', formData);
                
                if (response.success) {
                    showAlert('Registrazione completata! Reindirizzamento al login...', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert(response.error || 'Errore durante la registrazione', 'danger');
                }
            } catch (error) {
                showAlert('Errore: ' + error.message, 'danger');
            } finally {
                hideLoading();
            }
        });
    </script>
</body>
</html>
