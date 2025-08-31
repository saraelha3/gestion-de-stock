<?php
session_start();
require_once 'db.php';

// Fonction de connexion
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, nom_utilisateur, mot_de_passe, role FROM utilisateurs WHERE nom_utilisateur = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['nom_utilisateur'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Fonction de déconnexion
function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?> 