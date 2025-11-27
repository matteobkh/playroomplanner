<?php
/**
 * File: index.php
 * Percorso: playroomplanner/index.php
 * Scopo: Entry point dell'applicazione
 * Dipendenze: common/auth.php
 */

require_once __DIR__ . '/common/auth.php';

// Avvio della sessione
initSession();

// Se l'utente è già autenticato, reindirizza alla home
if (isLoggedIn()) {
    header('Location: frontend/home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Room Planner - Benvenuto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Hero Section -->
    <div class="container-fluid hero-section min-vh-100">
        <div class="row min-vh-100 align-items-center">
            <div class="col-12 text-center">
                <div class="welcome-box">
                    <h1 class="display-3 mb-4">
                        <i class="bi bi-diagram-3-fill"></i> Play Room Planner
                    </h1>
                    <p class="lead mb-5">Sistema di gestione prenotazioni sale per settori artistici</p>
                    
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="frontend/login.php" class="btn btn-light btn-lg px-5">
                            <i class="bi bi-box-arrow-in-right"></i> Accedi
                        </a>
                        <a href="frontend/register.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-person-plus"></i> Registrati
                        </a>
                    </div>
                    
                    <!-- Info Box -->
                    <div class="row mt-5 pt-5">
                        <div class="col-md-4 mb-3">
                            <div class="info-card">
                                <h3><i class="bi bi-calendar-check"></i> Prenota Sale</h3>
                                <p>Gestisci le prenotazioni delle sale per il tuo settore in modo semplice ed efficiente</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-card">
                                <h3><i class="bi bi-people"></i> Gestisci Inviti</h3>
                                <p>Invita collaboratori e gestisci le risposte alle tue prenotazioni</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-card">
                                <h3><i class="bi bi-list-check"></i> Visualizza Impegni</h3>
                                <p>Tieni traccia di tutti i tuoi impegni settimanali in un unico posto</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
