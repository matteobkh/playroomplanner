<?php
/**
 * File: frontend/profile.php
 * Visualizzazione e modifica profilo utente
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
        <div class="card bg-primary text-white mb-4">
            <div class="card-body text-center py-5">
                <?php if ($user['foto']): ?>
                    <img src="<?php echo htmlspecialchars($user['foto']); ?>" 
                         alt="Avatar" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid white;" 
                         id="avatarImage">
                <?php else: ?>
                    <div class="rounded-circle bg-light text-primary d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 120px; height: 120px; font-size: 3rem;">
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>
                <h2 class="mb-1"><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></h2>
                <p class="mb-0">
                    <span class="badge bg-light text-dark"><?php echo ucfirst(htmlspecialchars($user['nome_ruolo'])); ?></span>
                    <?php if ($user['nome_settore']): ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($user['nome_settore']); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="row">
            <!-- Informazioni Profilo -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Informazioni Personali</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted" style="width: 40%;">Email:</td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Nome:</td>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Cognome:</td>
                                <td><?php echo htmlspecialchars($user['cognome']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Data di Nascita:</td>
                                <td><?php echo date('d/m/Y', strtotime($user['data_nascita'])); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Ruolo:</td>
                                <td>
                                    <span class="badge ruolo-<?php echo htmlspecialchars($user['nome_ruolo']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['nome_ruolo'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if ($user['nome_settore']): ?>
                            <tr>
                                <td class="fw-bold text-muted">Settore:</td>
                                <td><?php echo htmlspecialchars($user['nome_settore']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($user['data_inizio']): ?>
                            <tr>
                                <td class="fw-bold text-muted">Data Inizio Ruolo:</td>
                                <td><?php echo date('d/m/Y', strtotime($user['data_inizio'])); ?></td>
                            </tr>
                            <?php if ($user['nome_ruolo'] === 'responsabile'): ?>
                            <tr>
                                <td class="fw-bold text-muted">Anni di Servizio:</td>
                                <td>
                                    <?php 
                                    $inizio = new DateTime($user['data_inizio']);
                                    $oggi = new DateTime();
                                    $anni = $oggi->diff($inizio)->y;
                                    echo $anni . ' ' . ($anni == 1 ? 'anno' : 'anni');
                                    ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modifica Profilo -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Modifica Profilo</h5>
                    </div>
                    <div class="card-body">
                        <form id="updateProfileForm">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="cognome" class="form-label">Cognome</label>
                                <input type="text" class="form-control" id="cognome" name="cognome" 
                                       value="<?php echo htmlspecialchars($user['cognome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nuova Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Lascia vuoto per non modificare" minlength="8">
                                <small class="form-text text-muted">Minimo 8 caratteri. Lascia vuoto per mantenere la password attuale.</small>
                            </div>

                            <div class="mb-3">
                                <label for="foto" class="form-label">URL Foto Profilo</label>
                                <input type="url" class="form-control" id="foto" name="foto" 
                                       value="<?php echo htmlspecialchars($user['foto'] ?? ''); ?>"
                                       placeholder="https://esempio.com/foto.jpg">
                                <small class="form-text text-muted">URL di un'immagine online</small>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Salva Modifiche
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        const userEmail = '<?php echo htmlspecialchars($user['email']); ?>';
        
        document.getElementById('updateProfileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                nome: document.getElementById('nome').value.trim(),
                cognome: document.getElementById('cognome').value.trim()
            };
            
            const foto = document.getElementById('foto').value.trim();
            if (foto) {
                formData.foto = foto;
            }
            
            const password = document.getElementById('password').value;
            if (password) {
                if (password.length < 8) {
                    showAlert('La password deve essere di almeno 8 caratteri', 'danger');
                    return;
                }
                formData.password = password;
            }
            
            if (!formData.nome || !formData.cognome) {
                showAlert('Nome e cognome sono obbligatori', 'danger');
                return;
            }
            
            try {
                showLoading();
                const response = await apiCall(`/users/${encodeURIComponent(userEmail)}`, 'PUT', formData);
                
                if (response.success) {
                    showAlert('Profilo aggiornato con successo!', 'success');
                    
                    // Aggiorna avatar se cambiato
                    const avatarImg = document.getElementById('avatarImage');
                    if (avatarImg && formData.foto) {
                        avatarImg.src = formData.foto;
                    }
                    
                    // Ricarica dopo 1.5 secondi
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(response.error || 'Errore durante l\'aggiornamento', 'danger');
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
