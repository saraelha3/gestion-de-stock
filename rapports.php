<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'rapports';
$page_title = 'Rapports et Statistiques';

// Statistiques générales
$stats = [];

// Total des pièces
$stmt = $pdo->query("SELECT COUNT(*) FROM pieces");
$stats['total_pieces'] = $stmt->fetchColumn();

// Total des catégories
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// Total des fournisseurs
$stmt = $pdo->query("SELECT COUNT(*) FROM fournisseurs");
$stats['total_fournisseurs'] = $stmt->fetchColumn();

// Pièces en rupture de stock
$stmt = $pdo->query("SELECT COUNT(*) FROM pieces WHERE quantite = 0");
$stats['pieces_rupture'] = $stmt->fetchColumn();

// Pièces en stock faible
$stmt = $pdo->query("SELECT COUNT(*) FROM pieces WHERE quantite <= stock_minimum AND quantite > 0");
$stats['pieces_stock_faible'] = $stmt->fetchColumn();

// Valeur totale du stock
$stmt = $pdo->query("SELECT SUM(quantite) FROM pieces");
$stats['total_quantite'] = $stmt->fetchColumn() ?: 0;

// Mouvements du mois
$stmt = $pdo->query("SELECT COUNT(*) FROM mouvements WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
$stats['mouvements_mois'] = $stmt->fetchColumn();

// Top 5 des pièces les plus utilisées (par mouvements de sortie)
$stmt = $pdo->query("
    SELECT p.nom, COUNT(m.id) as nb_sorties 
    FROM pieces p 
    LEFT JOIN mouvements m ON p.id = m.id_piece AND m.type = 'sortie'
    GROUP BY p.id 
    ORDER BY nb_sorties DESC 
    LIMIT 5
");
$top_pieces = $stmt->fetchAll();

// Top 5 des catégories par nombre de pièces
$stmt = $pdo->query("
    SELECT c.nom, COUNT(p.id) as nb_pieces 
    FROM categories c 
    LEFT JOIN pieces p ON c.id = p.id_categorie 
    GROUP BY c.id 
    ORDER BY nb_pieces DESC 
    LIMIT 5
");
$top_categories = $stmt->fetchAll();

// Mouvements récents (7 derniers jours)
$stmt = $pdo->query("
    SELECT m.*, p.nom as piece_nom, u.nom_utilisateur 
    FROM mouvements m 
    LEFT JOIN pieces p ON m.id_piece = p.id 
    LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id 
    WHERE m.date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    ORDER BY m.date DESC 
    LIMIT 10
");
$mouvements_recents = $stmt->fetchAll();

// Données pour les graphiques
$stmt = $pdo->query("
    SELECT c.nom, COUNT(p.id) as nb_pieces 
    FROM categories c 
    LEFT JOIN pieces p ON c.id = p.id_categorie 
    GROUP BY c.id 
    ORDER BY nb_pieces DESC
");
$categories_data = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT DATE(date) as date_mouvement, 
           COUNT(CASE WHEN type = 'entree' THEN 1 END) as entrees,
           COUNT(CASE WHEN type = 'sortie' THEN 1 END) as sorties
    FROM mouvements 
    WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY DATE(date)
    ORDER BY date_mouvement
");
$mouvements_30_jours = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-chart-bar"></i> Rapports et Statistiques
        </h2>
    </div>

    <!-- Statistiques générales -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3><?php echo $stats['total_pieces']; ?></h3>
            <p>Total Pièces</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3><?php echo $stats['total_categories']; ?></h3>
            <p>Catégories</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3><?php echo $stats['total_fournisseurs']; ?></h3>
            <p>Fournisseurs</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3><?php echo $stats['total_quantite']; ?></h3>
            <p>Quantité Totale</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3><?php echo $stats['mouvements_mois']; ?></h3>
            <p>Mouvements ce mois</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 20px; border-radius: 10px; text-align: center;">
            <h3><?php echo $stats['pieces_stock_faible'] + $stats['pieces_rupture']; ?></h3>
            <p>Alertes Stock</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($stats['pieces_rupture'] > 0 || $stats['pieces_stock_faible'] > 0): ?>
        <div class="alert alert-warning" style="margin-bottom: 30px;">
            <h4><i class="fas fa-exclamation-triangle"></i> Alertes Stock</h4>
            <?php if ($stats['pieces_rupture'] > 0): ?>
                <p><strong><?php echo $stats['pieces_rupture']; ?></strong> pièce(s) en rupture de stock</p>
            <?php endif; ?>
            <?php if ($stats['pieces_stock_faible'] > 0): ?>
                <p><strong><?php echo $stats['pieces_stock_faible']; ?></strong> pièce(s) en stock faible</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Graphiques et analyses -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <!-- Top 5 des pièces les plus utilisées -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-trophy"></i> Top 5 des Pièces les Plus Utilisées</h3>
            </div>
            <div class="card-body">
                <?php if (empty($top_pieces)): ?>
                    <p style="text-align: center; color: #666;">Aucune donnée disponible</p>
                <?php else: ?>
                    <?php foreach ($top_pieces as $index => $piece): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <div>
                                <span style="font-weight: bold; color: #007bff;">#<?php echo $index + 1; ?></span>
                                <span style="margin-left: 10px;"><?php echo htmlspecialchars($piece['nom']); ?></span>
                            </div>
                            <span class="badge badge-info"><?php echo $piece['nb_sorties']; ?> sorties</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top 5 des catégories -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-tags"></i> Top 5 des Catégories</h3>
            </div>
            <div class="card-body">
                <?php if (empty($top_categories)): ?>
                    <p style="text-align: center; color: #666;">Aucune donnée disponible</p>
                <?php else: ?>
                    <?php foreach ($top_categories as $index => $categorie): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <div>
                                <span style="font-weight: bold; color: #28a745;">#<?php echo $index + 1; ?></span>
                                <span style="margin-left: 10px;"><?php echo htmlspecialchars($categorie['nom']); ?></span>
                            </div>
                            <span class="badge badge-success"><?php echo $categorie['nb_pieces']; ?> pièces</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mouvements récents -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Mouvements Récents (7 derniers jours)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($mouvements_recents)): ?>
                <p style="text-align: center; color: #666;">Aucun mouvement récent</p>
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
    </div>

    <!-- Graphiques -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Répartition par catégories -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Répartition par Catégories</h3>
            </div>
            <div class="card-body">
                <canvas id="categoriesChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Mouvements des 30 derniers jours -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Mouvements (30 derniers jours)</h3>
            </div>
            <div class="card-body">
                <canvas id="mouvementsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Inclusion de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Graphique des catégories
const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
const categoriesData = <?php echo json_encode($categories_data); ?>;

if (categoriesData.length > 0) {
    new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: categoriesData.map(item => item.nom),
            datasets: [{
                data: categoriesData.map(item => item.nb_pieces),
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Graphique des mouvements
const mouvementsCtx = document.getElementById('mouvementsChart').getContext('2d');
const mouvementsData = <?php echo json_encode($mouvements_30_jours); ?>;

if (mouvementsData.length > 0) {
    new Chart(mouvementsCtx, {
        type: 'line',
        data: {
            labels: mouvementsData.map(item => item.date_mouvement),
            datasets: [{
                label: 'Entrées',
                data: mouvementsData.map(item => item.entrees),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.1
            }, {
                label: 'Sorties',
                data: mouvementsData.map(item => item.sorties),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
}
</script>

<style>
.stat-card h3 {
    font-size: 2.5em;
    margin: 0;
    font-weight: bold;
}

.stat-card p {
    margin: 5px 0 0 0;
    font-size: 1.1em;
    opacity: 0.9;
}

.badge-info {
    background: #17a2b8;
    color: white;
}

.badge-success {
    background: #28a745;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.badge-warning {
    background: #ffc107;
    color: #212529;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 15px;
    border-radius: 5px;
}

.alert-warning h4 {
    margin: 0 0 10px 0;
    color: #856404;
}

.alert-warning p {
    margin: 5px 0;
}
</style>

<?php include 'includes/footer.php'; ?> 