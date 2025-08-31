<?php
// Redirige vers le dashboard si déjà connecté
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue - Gestion de Stock</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .welcome-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
            padding: 40px 30px 30px 30px;
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }
        .welcome-container .fa-car {
            color: #667eea;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .welcome-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }
        .welcome-desc {
            color: #666;
            margin-bottom: 30px;
        }
        .btn-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        .btn-welcome:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }
        .welcome-footer {
            margin-top: 30px;
            color: #aaa;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <i class="fas fa-car"></i>
        <div class="welcome-title">Gestion de Stock</div>
        <div class="welcome-desc">Bienvenue sur la plateforme de gestion des pièces automobiles.<br>Veuillez vous connecter pour accéder à votre espace.</div>
        <a href="login.php">
            <button class="btn-welcome">
                <i class="fas fa-sign-in-alt"></i> Accéder à la connexion
            </button>
        </a>
        <div class="welcome-footer">
            &copy; 2025 Gestion de Stock - Pièces Automobiles
        </div>
    </div>
</body>
</html>