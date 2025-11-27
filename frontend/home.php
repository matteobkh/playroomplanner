<?php
/**
 * File: frontend/home.php
 * Percorso: playroomplanner/frontend/home.php
 * Scopo: Dashboard principale dell'utente autenticato
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
    <title>Dashboard - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'components/nav.php'; ?>

    <div class="container py-5">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-header p-4 bg-gradient rounded shadow">
                    <h1 class="text-white mb-2">Benvenuto, <?php echo htmlspecialchars($user['nome']); ?>!</h1>
                    <p class="text-white mb-0">
                        <span class="badge ruolo-<?php echo htmlspecialchars($user['nome_ruolo']); ?>">
                            <?php echo ucfirst(htmlspecialchars($user['nome_ruolo'])); ?>
                        </span>
                        <?php if ($user['nome_settore']): ?>
                            <span class="badge bg-light text-dark ms-2">
                                Settore: <?php echo htmlspecialchars($user['nome_settore']); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">Azioni Rapide</h3>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-check dashboard-icon me-3"></i>
                            <h5 class="mb-0">Prenotazioni Sale</h5>
                        </div>
                        <p class="text-muted">Visualizza il calendario delle prenotazioni e gestisci le tue</p>
                        <a href="sala_prenotazioni.php" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right"></i> Vai al Calendario
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-list-check dashboard-icon me-3"></i>
                            <h5 class="mb-0">I Miei Impegni</h5>
                        </div>
                        <p class="text-muted">Visualizza tutti i tuoi impegni e rispondi agli inviti</p>
                        <a href="user_impegni.php" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right"></i> Vedi Impegni
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-circle dashboard-icon me-3"></i>
                            <h5 class="mb-0">Profilo</h5>
                        </div>
                        <p class="text-muted">Visualizza e modifica i tuoi dati personali</p>
                        <a href="profile.php" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right"></i> Vai al Profilo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Info Summary -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informazioni Account</h5>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-item">
                            <span class="profile-info-label">Email:</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Nome Completo:</span>
                            <span><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></span>
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
                            <span class="profile-info-label">Data Inizio:</span>
                            <span><?php echo date('d/m/Y', strtotime($user['data_inizio'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Suggerimenti</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Controlla i tuoi impegni:</strong> Assicurati di rispondere agli inviti in tempo
                            </li>
                            <?php if (isResponsabile()): ?>
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Crea prenotazioni:</strong> Come responsabile puoi prenotare sale per il tuo settore
                            </li>
                            <?php endif; ?>
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Aggiorna il profilo:</strong> Mantieni aggiornate le tue informazioni personali
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Pianifica in anticipo:</strong> Le prenotazioni devono essere fatte con anticipo
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isResponsabile()): ?>
        <!-- Statistiche per Responsabili -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Pannello Responsabile</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Come responsabile hai accesso a funzionalità aggiuntive:</p>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <div class="stat-card">
                                    <div class="stat-icon text-primary mb-2">
                                        <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="stat-label">Crea Prenotazioni</div>
                                    <small class="text-muted">Gestisci le sale del tuo settore</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="stat-card">
                                    <div class="stat-icon text-success mb-2">
                                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="stat-label">Invita Partecipanti</div>
                                    <small class="text-muted">Aggiungi collaboratori alle tue prenotazioni</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="stat-card">
                                    <div class="stat-icon text-info mb-2">
                                        <i class="bi bi-pencil-square" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="stat-label">Modifica & Elimina</div>
                                    <small class="text-muted">Gestisci le tue prenotazioni</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
</body>
</html>
