<?php
require_once 'includes/auth.php';

$error = '';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        if (login($username, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion de Stock</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-header">
            <i class="fas fa-car fa-2x" style="margin-bottom: 15px;"></i>
            <h1>Gestion de Stock</h1>
            <p>Pièces Automobiles</p>
        </div>
        
        <form class="login-form" method="POST" action="">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Nom d'utilisateur
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    placeholder="Entrez votre nom d'utilisateur"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Mot de passe
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Entrez votre mot de passe"
                    required
                >
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
            
            <a href="index.php" style="text-decoration:none;">
                <button type="button" class="btn-login" style="margin-top:10px;background: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </button>
            </a>
        </form>
        
        <div class="login-footer">
            <p>&copy; 2025 Gestion de Stock - Pièces Automobiles</p>
        </div>
    </div>

    <script>
        // Animation et validation côté client
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            const inputs = form.querySelectorAll('input');
            
            // Animation des champs
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
            
            // Validation en temps réel
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                
                if (!username || !password) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>