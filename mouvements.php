<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'mouvements';
$page_title = 'Gestion des Mouvements';

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $id_piece = $_POST['id_piece'];
                $type = $_POST['type'];
                $quantite = (int)$_POST['quantite'];
                $commentaire = trim($_POST['commentaire']);
                
                if (empty($id_piece) || $quantite <= 0) {
                    $message = 'Veuillez sélectionner une pièce et saisir une quantité valide.';
                    $message_type = 'error';
                } else {
                    // Vérifier le stock disponible pour les sorties
                    if ($type === 'sortie') {
                        $stmt = $pdo->prepare("SELECT quantite FROM pieces WHERE id = ?");
                        $stmt->execute([$id_piece]);
                        $stock_actuel = $stmt->fetchColumn();
                        
                        if ($stock_actuel < $quantite) {
                            $message = 'Stock insuffisant. Stock disponible : ' . $stock_actuel;
                            $message_type = 'error';
                            break;
                        }
                    }
                    
                    // Insérer le mouvement
                    $stmt = $pdo->prepare("INSERT INTO mouvements (id_piece, type, quantite, id_utilisateur, commentaire) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$id_piece, $type, $quantite, $_SESSION['user_id'], $commentaire])) {
                        // Mettre à jour le stock de la pièce
                        if ($type === 'entree') {
                            $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite + ? WHERE id = ?");
                        } else {
                            $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite - ? WHERE id = ?");
                        }
                        $stmt->execute([$quantite, $id_piece]);
                        
                        $message = 'Mouvement enregistré avec succès !';
                        $message_type = 'success';
                    } else {
                        $message = 'Erreur lors de l\'enregistrement du mouvement.';
                        $message_type = 'error';
                    }
                }
                break;
            case 'delete':
                $id = (int)$_POST['id'];
                // Récupérer le mouvement pour ajuster le stock
                $stmt = $pdo->prepare("SELECT * FROM mouvements WHERE id = ?");
                $stmt->execute([$id]);
                $mouvement = $stmt->fetch();
                if ($mouvement) {
                    // Annuler l'effet du mouvement sur le stock
                    if ($mouvement['type'] === 'entree') {
                        $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite - ? WHERE id = ?");
                    } else {
                        $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite + ? WHERE id = ?");
                    }
                    $stmt->execute([$mouvement['quantite'], $mouvement['id_piece']]);
                    // Supprimer le mouvement
                    $stmt = $pdo->prepare("DELETE FROM mouvements WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Mouvement supprimé avec succès.';
                    $message_type = 'success';
                } else {
                    $message = 'Mouvement introuvable.';
                    $message_type = 'error';
                }
                break;
            case 'edit':
                $id = (int)$_POST['id'];
                $id_piece = $_POST['id_piece'];
                $type = $_POST['type'];
                $quantite = (int)$_POST['quantite'];
                $commentaire = trim($_POST['commentaire']);

                // Récupérer l'ancien mouvement
                $stmt = $pdo->prepare("SELECT * FROM mouvements WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetch();

                if (!$old) {
                    $message = 'Mouvement introuvable.';
                    $message_type = 'error';
                    break;
                }

                // Annuler l'effet de l'ancien mouvement
                if ($old['type'] === 'entree') {
                    $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite - ? WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite + ? WHERE id = ?");
                }
                $stmt->execute([$old['quantite'], $old['id_piece']]);

                // Appliquer le nouveau mouvement
                if ($type === 'entree') {
                    $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite + ? WHERE id = ?");
                } else {
                    // Vérifier le stock pour une sortie
                    $stmtStock = $pdo->prepare("SELECT quantite FROM pieces WHERE id = ?");
                    $stmtStock->execute([$id_piece]);
                    $stock_actuel = $stmtStock->fetchColumn();
                    if ($stock_actuel < $quantite) {
                        $message = 'Stock insuffisant pour la modification. Stock disponible : ' . $stock_actuel;
                        $message_type = 'error';
                        // Remettre l'ancien mouvement si erreur
                        if ($old['type'] === 'entree') {
                            $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite + ? WHERE id = ?");
                        } else {
                            $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite - ? WHERE id = ?");
                        }
                        $stmt->execute([$old['quantite'], $old['id_piece']]);
                        break;
                    }
                    $stmt = $pdo->prepare("UPDATE pieces SET quantite = quantite - ? WHERE id = ?");
                }
                $stmt->execute([$quantite, $id_piece]);

                // Mettre à jour le mouvement
                $stmt = $pdo->prepare("UPDATE mouvements SET id_piece = ?, type = ?, quantite = ?, commentaire = ? WHERE id = ?");
                $stmt->execute([$id_piece, $type, $quantite, $commentaire, $id]);
                $message = 'Mouvement modifié avec succès.';
                $message_type = 'success';
                break;
        }
    }
}

// Récupération des mouvements
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_filter = $_GET['date'] ?? '';

$sql = "SELECT m.*, p.nom as piece_nom, c.nom as categorie_nom, u.nom_utilisateur 
        FROM mouvements m 
        LEFT JOIN pieces p ON m.id_piece = p.id 
        LEFT JOIN categories c ON p.id_categorie = c.id 
        LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND p.nom LIKE ?";
    $params[] = "%$search%";
}

if (!empty($type_filter)) {
    $sql .= " AND m.type = ?";
    $params[] = $type_filter;
}

if (!empty($date_filter)) {
    $sql .= " AND DATE(m.date) = ?";
    $params[] = $date_filter;
}

$sql .= " ORDER BY m.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mouvements = $stmt->fetchAll();

// Récupération des pièces pour le formulaire
$stmt = $pdo->query("SELECT p.*, c.nom as categorie_nom FROM pieces p LEFT JOIN categories c ON p.id_categorie = c.id ORDER BY p.nom");
$pieces = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-exchange-alt"></i> Gestion des Mouvements
        </h2>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Nouveau mouvement
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
                <label>Rechercher par pièce</label>
                <input type="text" name="search" class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Nom de la pièce...">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Type de mouvement</label>
                <select name="type" class="form-control">
                    <option value="">Tous les types</option>
                    <option value="entree" <?php echo $type_filter === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                    <option value="sortie" <?php echo $type_filter === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Date</label>
                <input type="date" name="date" class="form-control" 
                       value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="mouvements.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Effacer
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des mouvements -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Pièce</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Utilisateur</th>
                    <th>Commentaire</th>
                    <th>Actions</th> <!-- Ajout -->
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mouvements)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #666;">
                            Aucun mouvement trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mouvements as $mouvement): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($mouvement['date'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($mouvement['piece_nom']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($mouvement['categorie_nom'] ?? '-'); ?></td>
                            <td>
                                <?php if ($mouvement['type'] === 'entree'): ?>
                                    <span class="badge badge-success">Entrée</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sortie</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="quantity <?php echo $mouvement['type'] === 'entree' ? 'positive' : 'negative'; ?>">
                                    <?php echo $mouvement['type'] === 'entree' ? '+' : '-'; ?><?php echo $mouvement['quantite']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($mouvement['nom_utilisateur']); ?></td>
                            <td>
                                <?php if ($mouvement['commentaire']): ?>
                                    <small style="color: #666;"><?php echo htmlspecialchars($mouvement['commentaire']); ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <button class="icon-btn edit-btn" onclick="editMouvement(<?php echo $mouvement['id']; ?>)" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce mouvement ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $mouvement['id']; ?>">
                                        <button type="submit" class="icon-btn delete-btn" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout de mouvement -->
<div id="mouvementModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nouveau mouvement</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="mouvementForm" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="id_piece">Pièce *</label>
                <select id="id_piece" name="id_piece" class="form-control" required>
                    <option value="">Sélectionner une pièce</option>
                    <?php foreach ($pieces as $piece): ?>
                        <option value="<?php echo $piece['id']; ?>" data-stock="<?php echo $piece['quantite']; ?>">
                            <?php echo htmlspecialchars($piece['nom']); ?> 
                            (Stock: <?php echo $piece['quantite']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="type">Type de mouvement *</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="">Sélectionner le type</option>
                    <option value="entree">Entrée de stock</option>
                    <option value="sortie">Sortie de stock</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantite">Quantité *</label>
                <input type="number" id="quantite" name="quantite" class="form-control" required min="1">
                <small id="stockInfo" style="color: #666;"></small>
            </div>
            
            <div class="form-group">
                <label for="commentaire">Commentaire</label>
                <textarea id="commentaire" name="commentaire" class="form-control" rows="3" 
                          placeholder="Raison du mouvement, numéro de bon, etc."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<style>
.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.quantity.positive {
    color: #28a745;
    font-weight: bold;
}

.quantity.negative {
    color: #dc3545;
    font-weight: bold;
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

.icon-btn {
    border: none;
    background: #f1f1f1;
    color: #444;
    border-radius: 8px;
    padding: 6px 10px;
    margin: 0 2px;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.icon-btn:focus {
    outline: none;
}
.edit-btn {
    background: #e9ecef;
    color:rgb(12, 84, 96);
}
.edit-btn:hover {
    background: #007bff;
    color: #fff;
}
.delete-btn {
    background: #f8d7da;
    color: #c82333;
}
.delete-btn:hover {
    background: #dc3545;
    color: #fff;
}
</style>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Nouveau mouvement';
    document.getElementById('mouvementForm').reset();
    document.getElementById('stockInfo').textContent = '';
    document.getElementById('mouvementModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('mouvementModal').style.display = 'none';
}

// Mettre à jour les informations de stock
document.getElementById('id_piece').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stockInfo = document.getElementById('stockInfo');
    
    if (selectedOption.value) {
        const stock = selectedOption.getAttribute('data-stock');
        stockInfo.textContent = `Stock actuel : ${stock}`;
    } else {
        stockInfo.textContent = '';
    }
});

// Validation de la quantité pour les sorties
document.getElementById('type').addEventListener('change', function() {
    const quantiteInput = document.getElementById('quantite');
    const pieceSelect = document.getElementById('id_piece');
    
    if (this.value === 'sortie' && pieceSelect.value) {
        const selectedOption = pieceSelect.options[pieceSelect.selectedIndex];
        const stock = parseInt(selectedOption.getAttribute('data-stock'));
        quantiteInput.max = stock;
        quantiteInput.placeholder = `Max: ${stock}`;
    } else {
        quantiteInput.removeAttribute('max');
        quantiteInput.placeholder = '';
    }
});

window.onclick = function(event) {
    const modal = document.getElementById('mouvementModal');
    if (event.target === modal) {
        closeModal();
    }
}

function editMouvement(id) {
    // Récupérer les infos du mouvement dans le tableau (DOM)
    const row = [...document.querySelectorAll('tbody tr')].find(tr =>
        tr.querySelector('form input[name="id"]').value == id
    );
    if (!row) return;

    // Remplir le formulaire modal avec les valeurs existantes
    const cells = row.querySelectorAll('td');
    document.getElementById('modalTitle').textContent = 'Modifier le mouvement';
    document.getElementById('id_piece').value = [...document.getElementById('id_piece').options].find(
        opt => opt.textContent.trim().startsWith(cells[1].textContent.trim())
    )?.value || '';
    document.getElementById('type').value = cells[3].textContent.trim() === 'Entrée' ? 'entree' : 'sortie';
    document.getElementById('quantite').value = Math.abs(parseInt(cells[4].textContent));
    document.getElementById('commentaire').value = cells[6].textContent.trim() !== '-' ? cells[6].textContent.trim() : '';
    document.getElementById('mouvementForm').action = '';
    // Ajoute un champ caché pour l'id
    let idInput = document.getElementById('edit_id');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.id = 'edit_id';
        document.getElementById('mouvementForm').appendChild(idInput);
    }
    idInput.value = id;
    // Change l'action du formulaire
    document.querySelector('input[name="action"]').value = 'edit';
    document.getElementById('mouvementModal').style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>