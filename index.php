<?php
/**
 * File: index.php
 * Percorso: playroomplanner/index.php
 * Scopo: Entry point dell'applicazione. Reindirizza alla home se autenticato, altrimenti mostra pagina iniziale con link a login/registrazione
 * Dipendenze: common/auth.php
 */

// Inclusione del sistema di autenticazione
require_once __DIR__ . '/common/auth.php';

// Avvio della sessione
session_start();

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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Hero Section -->
    <div class="container-fluid hero-section">
        <div class="row min-vh-100 align-items-center">
            <div class="col-12 text-center">
                <div class="welcome-box">
                    <h1 class="display-3 mb-4">Play Room Planner</h1>
                    <p class="lead mb-5">Sistema di gestione prenotazioni sale per settori artistici</p>
                    
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="frontend/login.php" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-box-arrow-in-right"></i> Accedi
                        </a>
                        <a href="frontend/register.php" class="btn btn-outline-primary btn-lg px-5">
                            <i class="bi bi-person-plus"></i> Registrati
                        </a>
                    </div>
                    
                    <!-- Info Box -->
                    <div class="row mt-5 pt-5">
                        <div class="col-md-4 mb-3">
                            <div class="info-card">
                                <h3>Prenota Sale</h3>
                                <p>Gestisci le prenotazioni delle sale per il tuo settore in modo semplice ed efficiente</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-card">
                                <h3>Gestisci Inviti</h3>
                                <p>Invita collaboratori e gestisci le risposte alle tue prenotazioni</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-card">
                                <h3>Visualizza Impegni</h3>
                                <p>Tieni traccia di tutti i tuoi impegni settimanali in un unico posto</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>
