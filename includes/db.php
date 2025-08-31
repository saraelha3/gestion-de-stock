<?php
require_once __DIR__ . '/../config.php';

// Configuration de la base de données
$host = getConfig('db_host');
$dbname = getConfig('db_name');
$username = getConfig('db_user');
$password = getConfig('db_pass');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Logger la connexion réussie
    logEvent('INFO', 'Connexion à la base de données réussie');
} catch(PDOException $e) {
    logEvent('ERROR', 'Erreur de connexion à la base de données: ' . $e->getMessage());
    die(getErrorMessage('db_connection') . " : " . $e->getMessage());
}
?> 