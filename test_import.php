<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté et est admin
requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

echo "<h1>Test d'Import CSV - Débogage</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        echo "<h2>Analyse du fichier : " . $_FILES['csv_file']['name'] . "</h2>";
        
        // Lire le contenu brut du fichier
        $raw_content = file_get_contents($file);
        echo "<h3>Contenu brut du fichier :</h3>";
        echo "<pre>" . htmlspecialchars($raw_content) . "</pre>";
        
        // Analyser l'encodage
        $encoding = mb_detect_encoding($raw_content, ['UTF-8', 'ISO-8859-1', 'Windows-1252']);
        echo "<h3>Encodage détecté : " . $encoding . "</h3>";
        
        // Ouvrir le fichier pour analyse
        $handle = fopen($file, 'r');
        if ($handle) {
            echo "<h3>Analyse ligne par ligne :</h3>";
            
            $line_number = 0;
            while (($line = fgets($handle)) !== false) {
                $line_number++;
                echo "<h4>Ligne $line_number :</h4>";
                echo "<p><strong>Contenu brut :</strong> " . htmlspecialchars($line) . "</p>";
                
                // Analyser avec fgetcsv
                rewind($handle);
                for ($i = 0; $i < $line_number; $i++) {
                    $csv_data = fgetcsv($handle);
                }
                
                if ($csv_data !== false) {
                    echo "<p><strong>Données CSV parsées :</strong></p>";
                    echo "<ul>";
                    foreach ($csv_data as $index => $value) {
                        echo "<li>Colonne $index : '" . htmlspecialchars($value) . "' (longueur: " . strlen($value) . ")</li>";
                    }
                    echo "</ul>";
                    echo "<p><strong>Nombre de colonnes :</strong> " . count($csv_data) . "</p>";
                }
                
                if ($line_number >= 3) break; // Analyser seulement les 3 premières lignes
            }
            
            fclose($handle);
        }
        
        // Test d'import réel
        echo "<h3>Test d'import réel :</h3>";
        $handle = fopen($file, 'r');
        if ($handle) {
            // Ignorer l'en-tête
            $header = fgetcsv($handle);
            echo "<p><strong>En-tête détecté :</strong> " . implode(', ', $header) . "</p>";
            
            $imported = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle)) !== false) {
                echo "<p><strong>Ligne de données :</strong> " . implode(', ', $data) . "</p>";
                
                if (count($data) >= 4) {
                    $nom = trim($data[0]);
                    $categorie_nom = trim($data[1]);
                    $fournisseur_nom = trim($data[2]);
                    $quantite = (int)$data[3];
                    $stock_minimum = isset($data[4]) ? (int)$data[4] : 0;
                    $description = isset($data[5]) ? trim($data[5]) : '';
                    
                    echo "<ul>";
                    echo "<li>Nom: '$nom'</li>";
                    echo "<li>Catégorie: '$categorie_nom'</li>";
                    echo "<li>Fournisseur: '$fournisseur_nom'</li>";
                    echo "<li>Quantité: $quantite</li>";
                    echo "<li>Stock minimum: $stock_minimum</li>";
                    echo "<li>Description: '$description'</li>";
                    echo "</ul>";
                    
                    if (empty($nom)) {
                        $errors[] = "Ligne ignorée : nom de pièce manquant";
                        echo "<p style='color: red;'>❌ Nom manquant - ligne ignorée</p>";
                        continue;
                    }
                    
                    try {
                        // Vérifier si la pièce existe déjà
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pieces WHERE nom = ?");
                        $stmt->execute([$nom]);
                        if ($stmt->fetchColumn() > 0) {
                            $errors[] = "Pièce '$nom' déjà existante, ignorée";
                            echo "<p style='color: orange;'>⚠️ Pièce '$nom' déjà existante - ignorée</p>";
                            continue;
                        }
                        
                        // Récupérer ou créer la catégorie
                        $id_categorie = null;
                        if (!empty($categorie_nom)) {
                            $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
                            $stmt->execute([$categorie_nom]);
                            $id_categorie = $stmt->fetchColumn();
                            
                            if (!$id_categorie) {
                                $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                                $stmt->execute([$categorie_nom]);
                                $id_categorie = $pdo->lastInsertId();
                                echo "<p style='color: green;'>✅ Catégorie '$categorie_nom' créée (ID: $id_categorie)</p>";
                            } else {
                                echo "<p style='color: blue;'>ℹ️ Catégorie '$categorie_nom' existante (ID: $id_categorie)</p>";
                            }
                        }
                        
                        // Récupérer ou créer le fournisseur
                        $id_fournisseur = null;
                        if (!empty($fournisseur_nom)) {
                            $stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
                            $stmt->execute([$fournisseur_nom]);
                            $id_fournisseur = $stmt->fetchColumn();
                            
                            if (!$id_fournisseur) {
                                $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom) VALUES (?)");
                                $stmt->execute([$fournisseur_nom]);
                                $id_fournisseur = $pdo->lastInsertId();
                                echo "<p style='color: green;'>✅ Fournisseur '$fournisseur_nom' créé (ID: $id_fournisseur)</p>";
                            } else {
                                echo "<p style='color: blue;'>ℹ️ Fournisseur '$fournisseur_nom' existant (ID: $id_fournisseur)</p>";
                            }
                        }
                        
                        // Insérer la pièce
                        $stmt = $pdo->prepare("INSERT INTO pieces (nom, id_categorie, id_fournisseur, quantite, stock_minimum, description) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nom, $id_categorie, $id_fournisseur, $quantite, $stock_minimum, $description]);
                        
                        $imported++;
                        echo "<p style='color: green;'>✅ Pièce '$nom' importée avec succès !</p>";
                        
                    } catch (Exception $e) {
                        $errors[] = "Erreur lors de l'import de '$nom' : " . $e->getMessage();
                        echo "<p style='color: red;'>❌ Erreur lors de l'import de '$nom' : " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Ligne ignorée : nombre de colonnes insuffisant (" . count($data) . " au lieu de 4 minimum)</p>";
                }
            }
            
            fclose($handle);
            
            echo "<h3>Résultat final :</h3>";
            echo "<p><strong>$imported pièce(s) importée(s) avec succès !</strong></p>";
            
            if (!empty($errors)) {
                echo "<h4>Erreurs rencontrées :</h4>";
                echo "<ul>";
                foreach ($errors as $error) {
                    echo "<li style='color: red;'>$error</li>";
                }
                echo "</ul>";
            }
        }
    } else {
        echo "<p style='color: red;'>Erreur lors du téléchargement du fichier.</p>";
        echo "<p>Code d'erreur : " . $_FILES['csv_file']['error'] . "</p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h2>Télécharger un fichier CSV pour test :</h2>
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Analyser le fichier</button>
</form>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style> 