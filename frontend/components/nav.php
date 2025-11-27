<?php
/**
 * Navigation component - Richiede che common/auth.php sia già incluso
 */
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="home.php">
            <i class="bi bi-diagram-3-fill text-primary"></i> Play Room Planner
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'home.php' ? 'active' : ''; ?>" href="home.php">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'sala_prenotazioni.php' ? 'active' : ''; ?>" href="sala_prenotazioni.php">
                        <i class="bi bi-calendar-check"></i> Prenotazioni
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'user_impegni.php' ? 'active' : ''; ?>" href="user_impegni.php">
                        <i class="bi bi-list-check"></i> I Miei Impegni
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                        <i class="bi bi-person-circle"></i> Profilo
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                       data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> 
                        <?php echo htmlspecialchars($user['nome'] ?? 'Utente'); ?>
                        <?php if (isResponsabile()): ?>
                            <span class="badge bg-danger ms-1">R</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <small class="text-muted"><?php echo htmlspecialchars($user['email'] ?? ''); ?></small>
                            </span>
                        </li>
                        <?php if ($user['nome_ruolo'] ?? ''): ?>
                        <li>
                            <span class="dropdown-item-text">
                                <span class="badge ruolo-<?php echo htmlspecialchars($user['nome_ruolo']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($user['nome_ruolo'])); ?>
                                </span>
                            </span>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-gear"></i> Impostazioni
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" data-action="logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
