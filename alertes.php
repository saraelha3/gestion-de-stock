<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'alertes';
$page_title = 'Alertes Stock';

// Récupération des alertes
$alertes = [];

// Pièces en rupture de stock
$stmt = $pdo->query("
    SELECT p.*, c.nom as categorie_nom, f.nom as fournisseur_nom 
    FROM pieces p 
    LEFT JOIN categories c ON p.id_categorie = c.id 
    LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id 
    WHERE p.quantite = 0 
    ORDER BY p.nom
");
$ruptures = $stmt->fetchAll();

// Pièces en stock faible
$stmt = $pdo->query("
    SELECT p.*, c.nom as categorie_nom, f.nom as fournisseur_nom 
    FROM pieces p 
    LEFT JOIN categories c ON p.id_categorie = c.id 
    LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id 
    WHERE p.quantite <= p.stock_minimum AND p.quantite > 0 
    ORDER BY p.quantite ASC
");
$stock_faible = $stmt->fetchAll();

// Mouvements récents pour les pièces en alerte
$pieces_ids = array_merge(
    array_column($ruptures, 'id'),
    array_column($stock_faible, 'id')
);

$mouvements_recents = [];
if (!empty($pieces_ids)) {
    $placeholders = str_repeat('?,', count($pieces_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT m.*, p.nom as piece_nom, u.nom_utilisateur 
        FROM mouvements m 
        LEFT JOIN pieces p ON m.id_piece = p.id 
        LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id 
        WHERE m.id_piece IN ($placeholders) 
        ORDER BY m.date DESC 
        LIMIT 20
    ");
    $stmt->execute($pieces_ids);
    $mouvements_recents = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-exclamation-triangle"></i> Alertes Stock
        </h2>
        <div class="alert-summary">
            <span class="badge badge-danger"><?php echo count($ruptures); ?> Ruptures</span>
            <span class="badge badge-warning"><?php echo count($stock_faible); ?> Stock Faible</span>
        </div>
    </div>

    <?php if (empty($ruptures) && empty($stock_faible)): ?>
        <div class="alert alert-success" style="margin: 20px;">
            <i class="fas fa-check-circle"></i> Aucune alerte de stock ! Tous vos stocks sont en bon état.
        </div>
    <?php else: ?>
        <!-- Ruptures de stock -->
        <?php if (!empty($ruptures)): ?>
            <div class="alert-section">
                <h3 class="alert-title">
                    <i class="fas fa-times-circle" style="color: #dc3545;"></i> 
                    Ruptures de Stock (<?php echo count($ruptures); ?>)
                </h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Pièce</th>
                                <th>Catégorie</th>
                                <th>Fournisseur</th>
                                <th>Stock Minimum</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ruptures as $piece): ?>
                                <tr class="alert-row rupture">
                                    <td>
                                        <strong><?php echo htmlspecialchars($piece['nom']); ?></strong>
                                        <?php if ($piece['description']): ?>
                                            <br><small style="color: #666;"><?php echo htmlspecialchars($piece['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($piece['categorie_nom'] ?? 'Non classé'); ?></td>
                                    <td>
                                        <?php if ($piece['fournisseur_nom']): ?>
                                            <span class="supplier-info">
                                                <?php echo htmlspecialchars($piece['fournisseur_nom']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">Non défini</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="stock-minimum"><?php echo $piece['stock_minimum']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="showAddStockModal(<?php echo htmlspecialchars(json_encode($piece)); ?>)">
                                            <i class="fas fa-plus"></i> Ajouter Stock
                                        </button>
                                        <a href="pieces.php?search=<?php echo urlencode($piece['nom']); ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stock faible -->
        <?php if (!empty($stock_faible)): ?>
            <div class="alert-section">
                <h3 class="alert-title">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> 
                    Stock Faible (<?php echo count($stock_faible); ?>)
                </h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Pièce</th>
                                <th>Catégorie</th>
                                <th>Fournisseur</th>
                                <th>Stock Actuel</th>
                                <th>Stock Minimum</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock_faible as $piece): ?>
                                <tr class="alert-row stock-faible">
                                    <td>
                                        <strong><?php echo htmlspecialchars($piece['nom']); ?></strong>
                                        <?php if ($piece['description']): ?>
                                            <br><small style="color: #666;"><?php echo htmlspecialchars($piece['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($piece['categorie_nom'] ?? 'Non classé'); ?></td>
                                    <td>
                                        <?php if ($piece['fournisseur_nom']): ?>
                                            <span class="supplier-info">
                                                <?php echo htmlspecialchars($piece['fournisseur_nom']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">Non défini</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="stock-current low"><?php echo $piece['quantite']; ?></span>
                                    </td>
                                    <td>
                                        <span class="stock-minimum"><?php echo $piece['stock_minimum']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="showAddStockModal(<?php echo htmlspecialchars(json_encode($piece)); ?>)">
                                            <i class="fas fa-plus"></i> Ajouter Stock
                                        </button>
                                        <a href="pieces.php?search=<?php echo urlencode($piece['nom']); ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Mouvements récents -->
        <?php if (!empty($mouvements_recents)): ?>
            <div class="alert-section">
                <h3 class="alert-title">
                    <i class="fas fa-history"></i> 
                    Mouvements Récents des Pièces en Alerte
                </h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Pièce</th>
                                <th>Type</th>
                                <th>Quantité</th>
                                <th>Utilisateur</th>
                                <th>Commentaire</th>
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
                                    <td>
                                        <?php if ($mouvement['commentaire']): ?>
                                            <small style="color: #666;"><?php echo htmlspecialchars($mouvement['commentaire']); ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal Ajout de Stock -->
<div id="addStockModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter du Stock</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="addStockForm" method="POST" action="mouvements.php">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="id_piece" id="pieceId">
            <input type="hidden" name="type" value="entree">
            
            <div class="form-group">
                <label for="piece_name">Pièce</label>
                <input type="text" id="piece_name" class="form-control" readonly>
            </div>
            
            <div class="form-group">
                <label for="quantite">Quantité à ajouter *</label>
                <input type="number" id="quantite" name="quantite" class="form-control" required min="1" value="1">
            </div>
            
            <div class="form-group">
                <label for="commentaire">Commentaire</label>
                <textarea id="commentaire" name="commentaire" class="form-control" rows="3" 
                          placeholder="Raison de l'ajout, numéro de bon, etc."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter le Stock</button>
            </div>
        </form>
    </div>
</div>

<style>
.alert-summary {
    display: flex;
    gap: 10px;
}

.alert-section {
    margin-bottom: 30px;
}

.alert-title {
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.alert-row.rupture {
    background-color: #f8d7da;
}

.alert-row.stock-faible {
    background-color: #fff3cd;
}

.stock-current.low {
    color: #dc3545;
    font-weight: bold;
}

.stock-minimum {
    color: #6c757d;
    font-weight: 500;
}

.supplier-info {
    color: #007bff;
    font-weight: 500;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.badge-warning {
    background: #ffc107;
    color: #212529;
}

.badge-success {
    background: #28a745;
    color: white;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100vh;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.modal-content {
    background-color: white;
    border-radius: 10px;
    width: 100%;
    max-width: 500px;
    max-height: calc(100vh - 40px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    display: flex;
    flex-direction: column;
    position: relative;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.close:hover {
    color: #333;
}

.modal form {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    max-height: calc(100vh - 200px);
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: white;
    border-radius: 0 0 10px 10px;
}
</style>

<script>
function showAddStockModal(piece) {
    document.getElementById('modalTitle').textContent = 'Ajouter du Stock - ' + piece.nom;
    document.getElementById('pieceId').value = piece.id;
    document.getElementById('piece_name').value = piece.nom;
    document.getElementById('quantite').value = 1;
    document.getElementById('commentaire').value = '';
    document.getElementById('addStockModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('addStockModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('addStockModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 