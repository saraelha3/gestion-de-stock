<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'categories';
$page_title = 'Gestion des Catégories';

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nom = trim($_POST['nom']);
                
                if (empty($nom)) {
                    $message = 'Le nom de la catégorie est requis.';
                    $message_type = 'error';
                } else {
                    // Vérifier si la catégorie existe déjà
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE nom = ?");
                    $stmt->execute([$nom]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = 'Cette catégorie existe déjà.';
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                        if ($stmt->execute([$nom])) {
                            $message = 'Catégorie ajoutée avec succès !';
                            $message_type = 'success';
                        } else {
                            $message = 'Erreur lors de l\'ajout de la catégorie.';
                            $message_type = 'error';
                        }
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $nom = trim($_POST['nom']);
                
                if (empty($nom)) {
                    $message = 'Le nom de la catégorie est requis.';
                    $message_type = 'error';
                } else {
                    // Vérifier si la catégorie existe déjà (sauf celle qu'on modifie)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE nom = ? AND id != ?");
                    $stmt->execute([$nom, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = 'Cette catégorie existe déjà.';
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("UPDATE categories SET nom = ? WHERE id = ?");
                        if ($stmt->execute([$nom, $id])) {
                            $message = 'Catégorie modifiée avec succès !';
                            $message_type = 'success';
                        } else {
                            $message = 'Erreur lors de la modification de la catégorie.';
                            $message_type = 'error';
                        }
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Vérifier si la catégorie est utilisée par des pièces
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM pieces WHERE id_categorie = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'Impossible de supprimer cette catégorie car elle est utilisée par des pièces.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = 'Catégorie supprimée avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de la suppression de la catégorie.';
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// Récupération des catégories avec statistiques
$search = $_GET['search'] ?? '';

$sql = "SELECT c.*, COUNT(p.id) as nb_pieces 
        FROM categories c 
        LEFT JOIN pieces p ON c.id = p.id_categorie 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND c.nom LIKE ?";
    $params[] = "%$search%";
}

$sql .= " GROUP BY c.id ORDER BY c.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-tags"></i> Gestion des Catégories
        </h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Ajouter une catégorie
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
                       placeholder="Nom de la catégorie...">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Effacer
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des catégories -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Nombre de pièces</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666;">
                            Aucune catégorie trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $categorie): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($categorie['nom']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $categorie['nb_pieces']; ?> pièce(s)</span>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($categorie)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($categorie['nb_pieces'] == 0): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCategorie(<?php echo $categorie['id']; ?>, '<?php echo htmlspecialchars($categorie['nom']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" disabled title="Impossible de supprimer - catégorie utilisée">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout/Modification -->
<div id="categorieModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter une catégorie</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="categorieForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="categorieId">
            
            <div class="form-group">
                <label for="nom">Nom de la catégorie *</label>
                <input type="text" id="nom" name="nom" class="form-control" required 
                       placeholder="Ex: Moteur, Freins, Suspension...">
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
.badge-info {
    background: #d1ecf1;
    color: #0c5460;
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
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter une catégorie';
    document.getElementById('formAction').value = 'add';
    document.getElementById('categorieForm').reset();
    document.getElementById('categorieId').value = '';
    document.getElementById('categorieModal').style.display = 'block';
}

function showEditModal(categorie) {
    document.getElementById('modalTitle').textContent = 'Modifier la catégorie';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('categorieId').value = categorie.id;
    document.getElementById('nom').value = categorie.nom;
    document.getElementById('categorieModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('categorieModal').style.display = 'none';
}

function deleteCategorie(id, nom) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la catégorie "${nom}" ?`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('categorieModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 