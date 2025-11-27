<?php
/**
 * File: frontend/login.php
 * Percorso: playroomplanner/frontend/login.php
 * Scopo: Pagina di login utente
 * Dipendenze: common/auth.php
 */

require_once __DIR__ . '/../common/auth.php';

// Se già autenticato, redirect alla home
session_start();
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

// Recupera eventuale parametro redirect
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'home.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <!-- Logo / Titolo -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Play Room Planner</h2>
                            <p class="text-muted">Accedi al tuo account</p>
                        </div>

                        <!-- Alert per messaggi -->
                        <div id="alertContainer"></div>

                        <!-- Form Login -->
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="tua@email.com" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Minimo 8 caratteri</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Accedi
                                </button>
                            </div>
                        </form>

                        <!-- Link registrazione -->
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Non hai un account? 
                                <a href="register.php" class="text-decoration-none">Registrati</a>
                            </p>
                        </div>

                        <!-- Credenziali demo -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <p class="mb-2"><strong>Account di test:</strong></p>
                            <small class="d-block">Responsabile Musica: supermario@bbldrizzy.it / 12345678</small>
                            <small class="d-block">Docente: luca@bbldrizzy.it / 12345678</small>
                            <small class="d-block">Allievo: carlotta.peda@bbldrizzy.it / password</small>
                        </div>
                    </div>
                </div>

                <!-- Link torna alla home -->
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
        // Toggle visibilità password
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Gestione submit form login
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const alertContainer = document.getElementById('alertContainer');
            
            // Validazione client-side
            if (!email || !password) {
                showAlert('Inserisci email e password', 'danger');
                return;
            }
            
            if (password.length < 8) {
                showAlert('La password deve essere di almeno 8 caratteri', 'danger');
                return;
            }
            
            try {
                // Chiamata API login
                const response = await apiCall('../backend/api.php/login', 'POST', {
                    email: email,
                    password: password
                });
                
                if (response.success) {
                    showAlert('Login effettuato con successo! Reindirizzamento...', 'success');
                    
                    // Redirect dopo 1 secondo
                    setTimeout(() => {
                        window.location.href = '<?php echo htmlspecialchars($redirect); ?>';
                    }, 1000);
                } else {
                    showAlert(response.error || 'Credenziali non valide', 'danger');
                }
            } catch (error) {
                showAlert('Errore di connessione: ' + error.message, 'danger');
            }
        });

        /**
         * Mostra un alert nella pagina
         * @param {string} message Messaggio da mostrare
         * @param {string} type Tipo di alert (success, danger, warning, info)
         */
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    </script>
</body>
</html>
