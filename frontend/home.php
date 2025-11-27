<?php
/**
 * File: frontend/home.php
 * Dashboard principale dell'utente autenticato
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
    <?php include 'components/nav.php'; ?>

    <div class="container py-4">
        <div id="alertContainer"></div>
        
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body p-4">
                        <h1 class="mb-2">Benvenuto, <?php echo htmlspecialchars($user['nome']); ?>!</h1>
                        <p class="mb-0">
                            <span class="badge bg-light text-dark">
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
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3"><i class="bi bi-lightning"></i> Azioni Rapide</h3>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-check text-primary me-3" style="font-size: 2rem;"></i>
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
                            <i class="bi bi-list-check text-success me-3" style="font-size: 2rem;"></i>
                            <h5 class="mb-0">I Miei Impegni</h5>
                        </div>
                        <p class="text-muted">Visualizza tutti i tuoi impegni e rispondi agli inviti</p>
                        <a href="user_impegni.php" class="btn btn-success w-100">
                            <i class="bi bi-arrow-right"></i> Vedi Impegni
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-circle text-info me-3" style="font-size: 2rem;"></i>
                            <h5 class="mb-0">Profilo</h5>
                        </div>
                        <p class="text-muted">Visualizza e modifica i tuoi dati personali</p>
                        <a href="profile.php" class="btn btn-info w-100">
                            <i class="bi bi-arrow-right"></i> Vai al Profilo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informazioni Account</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="fw-bold text-muted">Email:</td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Nome:</td>
                                <td><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></td>
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
                            <?php if ($user['data_inizio'] && $user['nome_ruolo'] === 'responsabile'): ?>
                            <tr>
                                <td class="fw-bold text-muted">Anni di servizio:</td>
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
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Suggerimenti</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Controlla i tuoi impegni</strong><br>
                                <small class="text-muted">Assicurati di rispondere agli inviti in tempo</small>
                            </li>
                            <?php if (isResponsabile()): ?>
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Crea prenotazioni</strong><br>
                                <small class="text-muted">Come responsabile puoi prenotare sale per il tuo settore</small>
                            </li>
                            <?php endif; ?>
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Aggiorna il profilo</strong><br>
                                <small class="text-muted">Mantieni aggiornate le tue informazioni personali</small>
                            </li>
                            <li class="mb-0">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Pianifica in anticipo</strong><br>
                                <small class="text-muted">Le prenotazioni sono disponibili dalle 09:00 alle 23:00</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isResponsabile()): ?>
        <!-- Pannello Responsabile -->
        <div class="row">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-star-fill"></i> Pannello Responsabile</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Come responsabile hai accesso a funzionalità aggiuntive:</p>
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-plus-circle text-primary" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-2">Crea Prenotazioni</h6>
                                <small class="text-muted">Gestisci le sale del tuo settore</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-people text-success" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-2">Invita Partecipanti</h6>
                                <small class="text-muted">Aggiungi collaboratori alle prenotazioni</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-pencil-square text-info" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-2">Modifica & Elimina</h6>
                                <small class="text-muted">Gestisci le tue prenotazioni</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'components/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
</body>
</html>
