<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté et est admin
requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$current_page = 'utilisateurs';
$page_title = 'Gestion des Utilisateurs';

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nom_utilisateur = trim($_POST['nom_utilisateur']);
                $mot_de_passe = $_POST['mot_de_passe'];
                $role = $_POST['role'];
                
                if (empty($nom_utilisateur) || empty($mot_de_passe)) {
                    $message = 'Le nom d\'utilisateur et le mot de passe sont requis.';
                    $message_type = 'error';
                } else {
                    // Vérifier si l'utilisateur existe déjà
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
                    $stmt->execute([$nom_utilisateur]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = 'Ce nom d\'utilisateur existe déjà.';
                        $message_type = 'error';
                    } else {
                        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role) VALUES (?, ?, ?)");
                        if ($stmt->execute([$nom_utilisateur, $mot_de_passe_hash, $role])) {
                            $message = 'Utilisateur ajouté avec succès !';
                            $message_type = 'success';
                        } else {
                            $message = 'Erreur lors de l\'ajout de l\'utilisateur.';
                            $message_type = 'error';
                        }
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $nom_utilisateur = trim($_POST['nom_utilisateur']);
                $mot_de_passe = $_POST['mot_de_passe'];
                $role = $_POST['role'];
                
                if (empty($nom_utilisateur)) {
                    $message = 'Le nom d\'utilisateur est requis.';
                    $message_type = 'error';
                } else {
                    // Vérifier si l'utilisateur existe déjà (sauf celui qu'on modifie)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ? AND id != ?");
                    $stmt->execute([$nom_utilisateur, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = 'Ce nom d\'utilisateur existe déjà.';
                        $message_type = 'error';
                    } else {
                        if (!empty($mot_de_passe)) {
                            $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, mot_de_passe = ?, role = ? WHERE id = ?");
                            $stmt->execute([$nom_utilisateur, $mot_de_passe_hash, $role, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, role = ? WHERE id = ?");
                            $stmt->execute([$nom_utilisateur, $role, $id]);
                        }
                        
                        if ($stmt->rowCount() > 0) {
                            $message = 'Utilisateur modifié avec succès !';
                            $message_type = 'success';
                        } else {
                            $message = 'Erreur lors de la modification de l\'utilisateur.';
                            $message_type = 'error';
                        }
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Empêcher la suppression de son propre compte
                if ($id == $_SESSION['user_id']) {
                    $message = 'Vous ne pouvez pas supprimer votre propre compte.';
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = 'Utilisateur supprimé avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de la suppression de l\'utilisateur.';
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// Récupération des utilisateurs
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$sql = "SELECT * FROM utilisateurs WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND nom_utilisateur LIKE ?";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY nom_utilisateur";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-users"></i> Gestion des Utilisateurs
        </h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Ajouter un utilisateur
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
                       placeholder="Nom d'utilisateur...">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Rôle</label>
                <select name="role" class="form-control">
                    <option value="">Tous les rôles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                    <option value="employe" <?php echo $role_filter === 'employe' ? 'selected' : ''; ?>>Employé</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="utilisateurs.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Effacer
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom d'utilisateur</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilisateurs)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666;">
                            Aucun utilisateur trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?></strong>
                                <?php if ($utilisateur['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge badge-primary">Vous</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($utilisateur['role'] === 'admin'): ?>
                                    <span class="badge badge-danger">Administrateur</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Employé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($utilisateur)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($utilisateur['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUtilisateur(<?php echo $utilisateur['id']; ?>, '<?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" disabled title="Impossible de supprimer votre propre compte">
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
<div id="utilisateurModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ajouter un utilisateur</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="utilisateurForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="utilisateurId">
            
            <div class="form-group">
                <label for="nom_utilisateur">Nom d'utilisateur *</label>
                <input type="text" id="nom_utilisateur" name="nom_utilisateur" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe <span id="passwordNote">*</span></label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control">
                <small id="passwordHelp" style="color: #666;">Laissez vide pour conserver le mot de passe actuel (modification uniquement)</small>
            </div>
            
            <div class="form-group">
                <label for="role">Rôle *</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">Sélectionner un rôle</option>
                    <option value="admin">Administrateur</option>
                    <option value="employe">Employé</option>
                </select>
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
.badge-primary {
    background: #007bff;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.badge-secondary {
    background: #6c757d;
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
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un utilisateur';
    document.getElementById('formAction').value = 'add';
    document.getElementById('utilisateurForm').reset();
    document.getElementById('utilisateurId').value = '';
    document.getElementById('mot_de_passe').required = true;
    document.getElementById('passwordNote').textContent = '*';
    document.getElementById('passwordHelp').style.display = 'none';
    document.getElementById('utilisateurModal').style.display = 'block';
}

function showEditModal(utilisateur) {
    document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('utilisateurId').value = utilisateur.id;
    document.getElementById('nom_utilisateur').value = utilisateur.nom_utilisateur;
    document.getElementById('role').value = utilisateur.role;
    document.getElementById('mot_de_passe').value = '';
    document.getElementById('mot_de_passe').required = false;
    document.getElementById('passwordNote').textContent = '';
    document.getElementById('passwordHelp').style.display = 'block';
    document.getElementById('utilisateurModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('utilisateurModal').style.display = 'none';
}

function deleteUtilisateur(id, nom) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${nom}" ?`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('utilisateurModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 