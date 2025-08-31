<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'pieces';
$page_title = 'Gestion des Pièces';

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nom = trim($_POST['nom']);
                $id_categorie = $_POST['id_categorie'];
                $id_fournisseur = $_POST['id_fournisseur'] ?: null;
                $quantite = (int)$_POST['quantite'];
                $stock_minimum = (int)$_POST['stock_minimum'];
                $description = trim($_POST['description']);
                
                if (empty($nom)) {
                    $message = 'Le nom de la pièce est requis.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO pieces (nom, id_categorie, id_fournisseur, quantite, stock_minimum, description) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$nom, $id_categorie, $id_fournisseur, $quantite, $stock_minimum, $description])) {
                        $message = 'Pièce ajoutée avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de l\'ajout de la pièce.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $nom = trim($_POST['nom']);
                $id_categorie = $_POST['id_categorie'];
                $id_fournisseur = $_POST['id_fournisseur'] ?: null;
                $quantite = (int)$_POST['quantite'];
                $stock_minimum = (int)$_POST['stock_minimum'];
                $description = trim($_POST['description']);
                
                if (empty($nom)) {
                    $message = 'Le nom de la pièce est requis.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("UPDATE pieces SET nom = ?, id_categorie = ?, id_fournisseur = ?, quantite = ?, stock_minimum = ?, description = ? WHERE id = ?");
                    if ($stmt->execute([$nom, $id_categorie, $id_fournisseur, $quantite, $stock_minimum, $description, $id])) {
                        $message = 'Pièce modifiée avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de la modification de la pièce.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM pieces WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = 'Pièce supprimée avec succès !';
                    $message_type = 'success';
                } else {
                    $message = 'Erreur lors de la suppression de la pièce.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Récupération des données
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Requête pour les pièces
$sql = "SELECT p.*, c.nom as categorie_nom, f.nom as fournisseur_nom 
        FROM pieces p 
        LEFT JOIN categories c ON p.id_categorie = c.id 
        LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND p.nom LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $sql .= " AND p.id_categorie = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY p.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pieces = $stmt->fetchAll();

// Récupération des catégories pour le filtre
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
$categories = $stmt->fetchAll();

// Récupération des fournisseurs
$stmt = $pdo->query("SELECT * FROM fournisseurs ORDER BY nom");
$fournisseurs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-cogs"></i> Gestion des Pièces
        </h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Ajouter une pièce
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="filters" style="margin-bottom: 20px;">
        <form method="GET" class="row" style="display: flex; gap: 15px; align-items: end;">
            <div class="form-group" style="flex: 1;">
                <label>Rechercher</label>
                <input type="text" name="search" class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Nom de la pièce...">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Catégorie</label>
                <select name="category" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="pieces.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Effacer
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des pièces -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Fournisseur</th>
                    <th>Quantité</th>
                    <th>Stock Minimum</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pieces)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666;">
                            Aucune pièce trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pieces as $piece): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($piece['nom']); ?></strong>
                                <?php if ($piece['description']): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars($piece['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($piece['categorie_nom'] ?? 'Non classé'); ?></td>
                            <td><?php echo htmlspecialchars($piece['fournisseur_nom'] ?? 'Non défini'); ?></td>
                            <td>
                                <span class="quantity <?php echo $piece['quantite'] <= $piece['stock_minimum'] ? 'low-stock' : ''; ?>">
                                    <?php echo $piece['quantite']; ?>
                                </span>
                            </td>
                            <td><?php echo $piece['stock_minimum']; ?></td>
                            <td>
                                <?php if ($piece['quantite'] <= $piece['stock_minimum']): ?>
                                    <span class="badge badge-warning">Stock faible</span>
                                <?php elseif ($piece['quantite'] == 0): ?>
                                    <span class="badge badge-danger">Rupture</span>
                                <?php else: ?>
                                    <span class="badge badge-success">En stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($piece)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deletePiece(<?php echo $piece['id']; ?>, '<?php echo htmlspecialchars($piece['nom']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout/Modification -->
<div id="pieceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter une pièce</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="pieceForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="pieceId">
            
            <div class="form-group">
                <label for="nom">Nom de la pièce *</label>
                <input type="text" id="nom" name="nom" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="id_categorie">Catégorie *</label>
                <select id="id_categorie" name="id_categorie" class="form-control" required>
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_fournisseur">Fournisseur</label>
                <select id="id_fournisseur" name="id_fournisseur" class="form-control">
                    <option value="">Sélectionner un fournisseur</option>
                    <?php foreach ($fournisseurs as $four): ?>
                        <option value="<?php echo $four['id']; ?>">
                            <?php echo htmlspecialchars($four['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="row">
                <div class="form-group" style="flex: 1;">
                    <label for="quantite">Quantité en stock</label>
                    <input type="number" id="quantite" name="quantite" class="form-control" value="0" min="0">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="stock_minimum">Stock minimum</label>
                    <input type="number" id="stock_minimum" name="stock_minimum" class="form-control" value="0" min="0">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulaire de suppression -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<style>
/* Styles pour les badges */
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

/* Styles pour les quantités */
.quantity.low-stock {
    color: #dc3545;
    font-weight: bold;
}

/* Styles pour les boutons */
.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

/* Styles pour le modal */
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
    max-width: 600px;
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

.row {
    display: flex;
    gap: 15px;
}
</style>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter une pièce';
    document.getElementById('formAction').value = 'add';
    document.getElementById('pieceForm').reset();
    document.getElementById('pieceId').value = '';
    document.getElementById('pieceModal').style.display = 'block';
}

function showEditModal(piece) {
    document.getElementById('modalTitle').textContent = 'Modifier la pièce';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('pieceId').value = piece.id;
    document.getElementById('nom').value = piece.nom;
    document.getElementById('id_categorie').value = piece.id_categorie;
    document.getElementById('id_fournisseur').value = piece.id_fournisseur || '';
    document.getElementById('quantite').value = piece.quantite;
    document.getElementById('stock_minimum').value = piece.stock_minimum;
    document.getElementById('description').value = piece.description || '';
    document.getElementById('pieceModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('pieceModal').style.display = 'none';
}

function deletePiece(id, nom) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la pièce "${nom}" ?`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('pieceModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 