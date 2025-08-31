<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'fournisseurs';
$page_title = 'Gestion des Fournisseurs';

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nom = trim($_POST['nom']);
                $contact = trim($_POST['contact']);
                $telephone = trim($_POST['telephone']);
                $email = trim($_POST['email']);
                
                if (empty($nom)) {
                    $message = 'Le nom du fournisseur est requis.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom, contact, telephone, email) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$nom, $contact, $telephone, $email])) {
                        $message = 'Fournisseur ajouté avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de l\'ajout du fournisseur.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $nom = trim($_POST['nom']);
                $contact = trim($_POST['contact']);
                $telephone = trim($_POST['telephone']);
                $email = trim($_POST['email']);
                
                if (empty($nom)) {
                    $message = 'Le nom du fournisseur est requis.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("UPDATE fournisseurs SET nom = ?, contact = ?, telephone = ?, email = ? WHERE id = ?");
                    if ($stmt->execute([$nom, $contact, $telephone, $email, $id])) {
                        $message = 'Fournisseur modifié avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de la modification du fournisseur.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Vérifier si le fournisseur est utilisé par des pièces
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM pieces WHERE id_fournisseur = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'Impossible de supprimer ce fournisseur car il est utilisé par des pièces.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM fournisseurs WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = 'Fournisseur supprimé avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de la suppression du fournisseur.';
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// Récupération des fournisseurs avec statistiques
$search = $_GET['search'] ?? '';

$sql = "SELECT f.*, COUNT(p.id) as nb_pieces 
        FROM fournisseurs f 
        LEFT JOIN pieces p ON f.id = p.id_fournisseur 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (f.nom LIKE ? OR f.contact LIKE ? OR f.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY f.id ORDER BY f.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fournisseurs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-truck"></i> Gestion des Fournisseurs
        </h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Ajouter un fournisseur
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
                       placeholder="Nom, contact ou email...">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="fournisseurs.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Effacer
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des fournisseurs -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Contact</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Pièces fournies</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fournisseurs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #666;">
                            Aucun fournisseur trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fournisseurs as $fournisseur): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($fournisseur['nom']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($fournisseur['contact'] ?? '-'); ?></td>
                            <td>
                                <?php if ($fournisseur['telephone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($fournisseur['telephone']); ?>">
                                        <?php echo htmlspecialchars($fournisseur['telephone']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($fournisseur['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($fournisseur['email']); ?>">
                                        <?php echo htmlspecialchars($fournisseur['email']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $fournisseur['nb_pieces']; ?> pièce(s)</span>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($fournisseur)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($fournisseur['nb_pieces'] == 0): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteFournisseur(<?php echo $fournisseur['id']; ?>, '<?php echo htmlspecialchars($fournisseur['nom']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" disabled title="Impossible de supprimer - fournisseur utilisé">
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
<div id="fournisseurModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter un fournisseur</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="fournisseurForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="fournisseurId">
            
            <div class="form-group">
                <label for="nom">Nom du fournisseur *</label>
                <input type="text" id="nom" name="nom" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="contact">Personne de contact</label>
                <input type="text" id="contact" name="contact" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control">
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
    document.getElementById('modalTitle').textContent = 'Ajouter un fournisseur';
    document.getElementById('formAction').value = 'add';
    document.getElementById('fournisseurForm').reset();
    document.getElementById('fournisseurId').value = '';
    document.getElementById('fournisseurModal').style.display = 'block';
}

function showEditModal(fournisseur) {
    document.getElementById('modalTitle').textContent = 'Modifier le fournisseur';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('fournisseurId').value = fournisseur.id;
    document.getElementById('nom').value = fournisseur.nom;
    document.getElementById('contact').value = fournisseur.contact || '';
    document.getElementById('telephone').value = fournisseur.telephone || '';
    document.getElementById('email').value = fournisseur.email || '';
    document.getElementById('fournisseurModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('fournisseurModal').style.display = 'none';
}

function deleteFournisseur(id, nom) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le fournisseur "${nom}" ?`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('fournisseurModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 