<?php
require_once 'auth.php';
requireLogin();
// Connexion DB pour compter les alertes à afficher dans le menu
require_once 'db.php';

// Compteurs d'alertes (ruptures et stock faible)
$num_ruptures = 0;
$num_stock_faible = 0;
try {
    $num_ruptures = (int)$pdo->query("SELECT COUNT(*) FROM pieces WHERE quantite = 0")->fetchColumn();
    $num_stock_faible = (int)$pdo->query("SELECT COUNT(*) FROM pieces WHERE quantite > 0 AND quantite <= stock_minimum")->fetchColumn();
} catch (Exception $e) {
    // En cas d'erreur DB, on masque simplement les badges
}
$num_alertes_total = $num_ruptures + $num_stock_faible;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Gestion de Stock'; ?> - Pièces Automobiles</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo @filemtime(__DIR__.'/../assets/css/style.css'); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo @filemtime(__DIR__.'/../assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-car"></i>
            <h3>Gestion Stock</h3>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de Bord</span>
                    </a>
                </li>
                <li class="<?php echo $current_page === 'pieces' ? 'active' : ''; ?>">
                    <a href="pieces.php">
                        <i class="fas fa-cogs"></i>
                        <span>Pièces</span>
                    </a>
                </li>
                <li class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">
                    <a href="categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Catégories</span>
                    </a>
                </li>
                <li class="<?php echo $current_page === 'fournisseurs' ? 'active' : ''; ?>">
                    <a href="fournisseurs.php">
                        <i class="fas fa-truck"></i>
                        <span>Fournisseurs</span>
                    </a>
                </li>
                                            <li class="<?php echo $current_page === 'mouvements' ? 'active' : ''; ?>">
                                <a href="mouvements.php">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>Mouvements</span>
                                </a>
                            </li>
                            <li class="<?php echo $current_page === 'alertes' ? 'active' : ''; ?>">
                                <a href="alertes.php">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Alertes</span>
                                    <?php if ($num_alertes_total > 0): ?>
                                        <span class="menu-badge <?php echo $num_ruptures > 0 ? 'menu-badge-danger' : 'menu-badge-warning'; ?> menu-badge--pulse">
                                            <?php echo $num_alertes_total; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="<?php echo $current_page === 'rapports' ? 'active' : ''; ?>">
                                <a href="rapports.php">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Rapports</span>
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li class="<?php echo $current_page === 'export_import' ? 'active' : ''; ?>">
                                <a href="export_import.php">
                                    <i class="fas fa-download"></i>
                                    <span>Export/Import</span>
                                </a>
                            </li>
                            <li class="<?php echo $current_page === 'utilisateurs' ? 'active' : ''; ?>">
                                <a href="utilisateurs.php">
                                    <i class="fas fa-users"></i>
                                    <span>Utilisateurs</span>
                                </a>
                            </li>
                            <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo $page_title ?? 'Gestion de Stock'; ?></h1>
            </div>
            
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <small>(<?php echo $_SESSION['role'] === 'admin' ? 'Admin' : 'Employé'; ?>)</small>
                    </div>
                    <div class="user-dropdown">
                        <a href="profile.php">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a href="?logout=1">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content"> 