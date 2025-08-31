<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Traitement de la déconnexion
if (isset($_GET['logout'])) {
    logout();
}

$current_page = 'dashboard';
$page_title = 'Tableau de Bord';

// Récupération des statistiques
try {
    // Nombre total de pièces
    $stmt = $pdo->query("SELECT COUNT(*) FROM pieces");
    $total_pieces = $stmt->fetchColumn();
    
    // Nombre de catégories
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $total_categories = $stmt->fetchColumn();
    
    // Nombre de fournisseurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM fournisseurs");
    $total_fournisseurs = $stmt->fetchColumn();
    
    // Pièces en stock faible
    $stmt = $pdo->query("SELECT COUNT(*) FROM pieces WHERE quantite <= stock_minimum");
    $stock_faible = $stmt->fetchColumn();
    
    // Pièces en rupture
    $stmt = $pdo->query("SELECT COUNT(*) FROM pieces WHERE quantite = 0");
    $rupture_stock = $stmt->fetchColumn();
    
    // Mouvements récents
    $stmt = $pdo->query("SELECT m.*, p.nom as piece_nom, u.nom_utilisateur 
                        FROM mouvements m 
                        LEFT JOIN pieces p ON m.id_piece = p.id 
                        LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id 
                        ORDER BY m.date DESC LIMIT 5");
    $mouvements_recents = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-cogs"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $total_pieces; ?></div>
            <div class="stat-label">Pièces en Stock</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $total_categories; ?></div>
            <div class="stat-label">Catégories</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-truck"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $total_fournisseurs; ?></div>
            <div class="stat-label">Fournisseurs</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stock_faible; ?></div>
            <div class="stat-label">Alertes Stock Faible</div>
        </div>
    </div>
</div>

<!-- Alertes et informations -->
<?php if ($stock_faible > 0 || $rupture_stock > 0): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exclamation-triangle"></i> Alertes
        </h3>
    </div>
    
    <div class="alerts-container">
        <?php if ($rupture_stock > 0): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i>
            <strong><?php echo $rupture_stock; ?> pièce(s) en rupture de stock</strong>
            <a href="alertes.php" class="btn btn-sm btn-outline-light">Voir détails</a>
        </div>
        <?php endif; ?>
        
        <?php if ($stock_faible > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?php echo $stock_faible; ?> pièce(s) en stock faible</strong>
            <a href="alertes.php" class="btn btn-sm btn-outline-light">Voir détails</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Mouvements récents -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exchange-alt"></i> Mouvements Récents
        </h3>
        <a href="mouvements.php" class="btn btn-primary btn-sm">
            <i class="fas fa-eye"></i> Voir tout
        </a>
    </div>
    
    <?php if (empty($mouvements_recents)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">
            Aucun mouvement récent.
        </p>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pièce</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Utilisateur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mouvements_recents as $mouvement): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($mouvement['date'])); ?></td>
                            <td><?php echo htmlspecialchars($mouvement['piece_nom']); ?></td>
                            <td>
                                <?php if ($mouvement['type'] === 'entree'): ?>
                                    <span class="badge badge-success">Entrée</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sortie</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $mouvement['quantite']; ?></td>
                            <td><?php echo htmlspecialchars($mouvement['nom_utilisateur']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Actions rapides -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bolt"></i> Actions Rapides
        </h3>
    </div>
    
    <div class="quick-actions">
        <a href="pieces.php" class="quick-action-card">
            <i class="fas fa-plus"></i>
            <span>Ajouter une pièce</span>
        </a>
        
        <a href="mouvements.php" class="quick-action-card">
            <i class="fas fa-exchange-alt"></i>
            <span>Nouveau mouvement</span>
        </a>
        
        <a href="fournisseurs.php" class="quick-action-card">
            <i class="fas fa-truck"></i>
            <span>Gérer fournisseurs</span>
        </a>
        
        <a href="rapports.php" class="quick-action-card">
            <i class="fas fa-chart-bar"></i>
            <span>Générer rapport</span>
        </a>
    </div>
</div>

<style>
/* Styles pour les statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card.warning {
    border-left: 4px solid #ffc107;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.stat-card.warning .stat-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

/* Styles pour les alertes */
.alerts-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.alert {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    border-radius: 8px;
    margin: 0;
}

.alert i {
    font-size: 18px;
}

.alert strong {
    flex: 1;
}

/* Styles pour les actions rapides */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.quick-action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px 20px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.quick-action-card:hover {
    background: white;
    border-color: #667eea;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    color: #667eea;
}

.quick-action-card i {
    font-size: 32px;
    margin-bottom: 15px;
    color: #667eea;
}

.quick-action-card span {
    font-weight: 500;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-number {
        font-size: 28px;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 