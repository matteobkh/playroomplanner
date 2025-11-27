<?php
/**
 * File: frontend/profile.php
 * Percorso: playroomplanner/frontend/profile.php
 * Scopo: Visualizzazione e modifica profilo utente
 * Dipendenze: common/auth.php
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
    <title>Profilo - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'components/nav.php'; ?>

    <div class="container py-4">
        <div id="alertContainer"></div>

        <!-- Profile Header -->
        <div class="profile-header text-center mb-4">
            <div class="container">
                <img src="<?php echo $user['foto'] ?? '../images/placeholder.png'; ?>" 
                     alt="Avatar" class="profile-avatar mb-3" id="avatarImage">
                <h2 class="profile-name">
                    <?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?>
                </h2>
                <p class="mb-0">
                    <span class="badge ruolo-<?php echo htmlspecialchars($user['nome_ruolo']); ?>">
                        <?php echo ucfirst(htmlspecialchars($user['nome_ruolo'])); ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="row">
            <!-- Informazioni Profilo -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Informazioni Personali</h5>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-item">
                            <span class="profile-info-label">Email:</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Nome:</span>
                            <span><?php echo htmlspecialchars($user['nome']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Cognome:</span>
                            <span><?php echo htmlspecialchars($user['cognome']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Data di Nascita:</span>
                            <span><?php echo date('d/m/Y', strtotime($user['data_nascita'])); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Ruolo:</span>
                            <span>
                                <span class="badge ruolo-<?php echo htmlspecialchars($user['nome_ruolo']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($user['nome_ruolo'])); ?>
                                </span>
                            </span>
                        </div>
                        <?php if ($user['nome_settore']): ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Settore:</span>
                            <span><?php echo htmlspecialchars($user['nome_settore']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($user['data_inizio']): ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Data Inizio Ruolo:</span>
                            <span><?php echo date('d/m/Y', strtotime($user['data_inizio'])); ?></span>
                        </div>
                        <?php if ($user['nome_ruolo'] === 'responsabile'): ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Anni di Servizio:</span>
                            <span>
                                <?php 
                                $inizio = new DateTime($user['data_inizio']);
                                $oggi = new DateTime();
                                $anni = $oggi->diff($inizio)->y;
                                echo $anni . ' ' . ($anni == 1 ? 'anno' : 'anni');
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modifica Profilo -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Modifica Profilo</h5>
                    </div>
                    <div class="card-body">
                        <form id="updateProfileForm">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($user['nome']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="cognome" class="form-label">Cognome</label>
                                <input type="text" class="form-control" id="cognome" name="cognome" 
                                       value="<?php echo htmlspecialchars($user['cognome']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nuova Password (lascia vuoto per non modificare)</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Minimo 8 caratteri">
                                <small class="form-text text-muted">Inserisci solo se vuoi cambiare password</small>
                            </div>

                            <div class="mb-3">
                                <label for="foto" class="form-label">URL Foto Profilo</label>
                                <input type="url" class="form-control" id="foto" name="foto" 
                                       value="<?php echo htmlspecialchars($user['foto'] ?? ''); ?>"
                                       placeholder="https://esempio.com/foto.jpg">
                                <small class="form-text text-muted">Inserisci l'URL di un'immagine online</small>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Salva Modifiche
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Zona Pericolosa</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Le seguenti azioni sono irreversibili. Procedi con cautela.</p>
                        <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                            <i class="bi bi-trash"></i> Elimina Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        // Update profile form handler
        document.getElementById('updateProfileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                nome: document.getElementById('nome').value.trim(),
                cognome: document.getElementById('cognome').value.trim(),
                foto: document.getElementById('foto').value.trim() || null
            };
            
            const password = document.getElementById('password').value;
            if (password) {
                if (password.length < 8) {
                    showAlert('La password deve essere di almeno 8 caratteri', 'danger');
                    return;
                }
                formData.password = password;
            }
            
            try {
                const email = '<?php echo $user['email']; ?>';
                const response = await apiCall(`/playroomplanner/backend/api.php/users/${encodeURIComponent(email)}`, 
                                              'PUT', formData);
                
                if (response.success) {
                    showAlert('Profilo aggiornato con successo!', 'success');
                    
                    // Update avatar if changed
                    if (formData.foto) {
                        document.getElementById('avatarImage').src = formData.foto;
                    }
                    
                    // Reload page after 1.5 seconds to reflect changes
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.error || 'Errore durante l\'aggiornamento', 'danger');
                }
            } catch (error) {
                showAlert('Errore di connessione: ' + error.message, 'danger');
            }
        });

        // Delete account confirmation
        function confirmDeleteAccount() {
            showConfirmModal(
                'Conferma Eliminazione Account',
                'Sei sicuro di voler eliminare il tuo account? Questa azione è irreversibile e perderai tutti i tuoi dati.',
                async function() {
                    try {
                        const email = '<?php echo $user['email']; ?>';
                        const response = await apiCall(`/playroomplanner/backend/api.php/users/${encodeURIComponent(email)}`, 
                                                      'DELETE');
                        
                        if (response.success) {
                            alert('Account eliminato. Sarai reindirizzato alla pagina di login.');
                            await logout();
                        } else {
                            showAlert(response.error || 'Errore durante l\'eliminazione', 'danger');
                        }
                    } catch (error) {
                        showAlert('Errore: ' + error.message, 'danger');
                    }
                }
            );
        }
    </script>
</body>
</html>
