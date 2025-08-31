<?php
// Script d'initialisation de la base de données
require_once 'includes/db.php';

try {
    // Création des tables
    $sql = "
    -- Table des utilisateurs
    CREATE TABLE IF NOT EXISTS utilisateurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom_utilisateur VARCHAR(50) NOT NULL UNIQUE,
        mot_de_passe VARCHAR(255) NOT NULL,
        role ENUM('admin', 'employe') NOT NULL
    );

    -- Table des catégories de pièces
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL
    );

    -- Table des fournisseurs
    CREATE TABLE IF NOT EXISTS fournisseurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        contact VARCHAR(100),
        telephone VARCHAR(20),
        email VARCHAR(100)
    );

    -- Table des pièces
    CREATE TABLE IF NOT EXISTS pieces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        id_categorie INT,
        id_fournisseur INT,
        quantite INT DEFAULT 0,
        stock_minimum INT DEFAULT 0,
        description TEXT,
        FOREIGN KEY (id_categorie) REFERENCES categories(id),
        FOREIGN KEY (id_fournisseur) REFERENCES fournisseurs(id)
    );

    -- Table des mouvements de stock (entrées/sorties)
    CREATE TABLE IF NOT EXISTS mouvements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_piece INT NOT NULL,
        type ENUM('entree', 'sortie') NOT NULL,
        quantite INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        id_utilisateur INT,
        commentaire TEXT,
        FOREIGN KEY (id_piece) REFERENCES pieces(id),
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
    );

    -- Table des logs (historique des actions)
    CREATE TABLE IF NOT EXISTS logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(255) NOT NULL,
        id_utilisateur INT,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
    );
    ";

    $pdo->exec($sql);
    echo "✅ Tables créées avec succès !<br>";

    // Vérifier si l'utilisateur admin existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;

    if (!$adminExists) {
        // Créer l'utilisateur admin par défaut
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $adminPassword, 'admin']);
        echo "✅ Utilisateur admin créé !<br>";
        echo "📝 Identifiants de connexion :<br>";
        echo "   Nom d'utilisateur: <strong>admin</strong><br>";
        echo "   Mot de passe: <strong>admin123</strong><br>";
    } else {
        echo "ℹ️ L'utilisateur admin existe déjà.<br>";
    }

    // Insérer quelques catégories de base
    $categories = ['Moteur', 'Freins', 'Suspension', 'Électricité', 'Carrosserie', 'Transmission'];
    foreach ($categories as $categorie) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (nom) VALUES (?)");
        $stmt->execute([$categorie]);
    }
    echo "✅ Catégories de base ajoutées !<br>";

    echo "<br>🎉 Base de données initialisée avec succès !<br>";
    echo "<a href='login.php' style='color: #667eea; text-decoration: none;'>→ Aller à la page de connexion</a>";

} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 