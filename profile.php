<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$current_page = 'profile';
$page_title = 'Mon Profil';

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $nom_utilisateur = trim($_POST['nom_utilisateur']);
                $mot_de_passe_actuel = $_POST['mot_de_passe_actuel'];
                $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'];
                $confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'];
                
                if (empty($nom_utilisateur)) {
                    $message = 'Le nom d\'utilisateur est requis.';
                    $message_type = 'error';
                } else {
                    // Vérifier si le nom d'utilisateur existe déjà (sauf pour l'utilisateur actuel)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ? AND id != ?");
                    $stmt->execute([$nom_utilisateur, $_SESSION['user_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = 'Ce nom d\'utilisateur existe déjà.';
                        $message_type = 'error';
                    } else {
                        // Si on veut changer le mot de passe
                        if (!empty($nouveau_mot_de_passe)) {
                            // Vérifier le mot de passe actuel
                            if (!password_verify($mot_de_passe_actuel, $user['mot_de_passe'])) {
                                $message = 'Le mot de passe actuel est incorrect.';
                                $message_type = 'error';
                            } elseif ($nouveau_mot_de_passe !== $confirmer_mot_de_passe) {
                                $message = 'Les nouveaux mots de passe ne correspondent pas.';
                                $message_type = 'error';
                            } else {
                                // Mettre à jour nom d'utilisateur et mot de passe
                                $nouveau_mot_de_passe_hash = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, mot_de_passe = ? WHERE id = ?");
                                if ($stmt->execute([$nom_utilisateur, $nouveau_mot_de_passe_hash, $_SESSION['user_id']])) {
                                    $_SESSION['username'] = $nom_utilisateur;
                                    $message = 'Profil mis à jour avec succès !';
                                    $message_type = 'success';
                                } else {
                                    $message = 'Erreur lors de la mise à jour du profil.';
                                    $message_type = 'error';
                                }
                            }
                        } else {
                            // Mettre à jour seulement le nom d'utilisateur
                            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom_utilisateur = ? WHERE id = ?");
                            if ($stmt->execute([$nom_utilisateur, $_SESSION['user_id']])) {
                                $_SESSION['username'] = $nom_utilisateur;
                                $message = 'Nom d\'utilisateur mis à jour avec succès !';
                                $message_type = 'success';
                            } else {
                                $message = 'Erreur lors de la mise à jour du nom d\'utilisateur.';
                                $message_type = 'error';
                            }
                        }
                    }
                }
                break;
        }
    }
}

// Récupérer les statistiques de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) FROM mouvements WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_mouvements = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM mouvements WHERE id_utilisateur = ? AND DATE(date) = CURRENT_DATE()");
$stmt->execute([$_SESSION['user_id']]);
$mouvements_aujourd_hui = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT m.*, p.nom as piece_nom 
    FROM mouvements m 
    LEFT JOIN pieces p ON m.id_piece = p.id 
    WHERE m.id_utilisateur = ? 
    ORDER BY m.date DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$derniers_mouvements = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="profile-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- Informations du profil -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-user"></i> Informations du Profil
            </h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="nom_utilisateur">Nom d'utilisateur *</label>
                    <input type="text" id="nom_utilisateur" name="nom_utilisateur" 
                           class="form-control" required 
                           value="<?php echo htmlspecialchars($user['nom_utilisateur']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="role">Rôle</label>
                    <input type="text" class="form-control" readonly 
                           value="<?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Employé'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe_actuel">Mot de passe actuel</label>
                    <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" 
                           class="form-control" placeholder="Laissez vide si pas de changement">
                    <small style="color: #666;">Requis uniquement pour changer le mot de passe</small>
                </div>
                
                <div class="form-group">
                    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" 
                           class="form-control" placeholder="Laissez vide si pas de changement">
                </div>
                
                <div class="form-group">
                    <label for="confirmer_mot_de_passe">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" 
                           class="form-control" placeholder="Confirmez le nouveau mot de passe">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour le profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques personnelles -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-chart-line"></i> Mes Statistiques
            </h2>
        </div>

        <div class="card-body">
            <div class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3><?php echo $total_mouvements; ?></h3>
                    <p>Total Mouvements</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3><?php echo $mouvements_aujourd_hui; ?></h3>
                    <p>Aujourd'hui</p>
                </div>
            </div>

            <h4><i class="fas fa-history"></i> Mes Derniers Mouvements</h4>
            <?php if (empty($derniers_mouvements)): ?>
                <p style="text-align: center; color: #666;">Aucun mouvement enregistré</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Pièce</th>
                                <th>Type</th>
                                <th>Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniers_mouvements as $mouvement): ?>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
}

.stat-card h3 {
    font-size: 2em;
    margin: 0;
    font-weight: bold;
}

.stat-card p {
    margin: 5px 0 0 0;
    font-size: 0.9em;
    opacity: 0.9;
}

.badge-success {
    background: #28a745;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Validation du formulaire
document.querySelector('form').addEventListener('submit', function(e) {
    const nouveauMotDePasse = document.getElementById('nouveau_mot_de_passe').value;
    const confirmerMotDePasse = document.getElementById('confirmer_mot_de_passe').value;
    const motDePasseActuel = document.getElementById('mot_de_passe_actuel').value;
    
    if (nouveauMotDePasse && !motDePasseActuel) {
        e.preventDefault();
        alert('Veuillez saisir votre mot de passe actuel pour le changer.');
        return;
    }
    
    if (nouveauMotDePasse !== confirmerMotDePasse) {
        e.preventDefault();
        alert('Les nouveaux mots de passe ne correspondent pas.');
        return;
    }
    
    if (nouveauMotDePasse && nouveauMotDePasse.length < 6) {
        e.preventDefault();
        alert('Le nouveau mot de passe doit contenir au moins 6 caractères.');
        return;
    }
});
</script>

<?php include 'includes/footer.php'; ?> 