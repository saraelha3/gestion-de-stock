<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
// Charger PhpSpreadsheet pour l'import Excel si disponible
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
use PhpOffice\PhpSpreadsheet\IOFactory;

// Vérifier si l'utilisateur est connecté et est admin
requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$current_page = 'export_import';
$page_title = 'Export / Import';

// Traitement des actions
$message = '';
$message_type = '';

// Gestion des erreurs
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'excel_failed':
            $message = 'L\'export Excel a échoué. Utilisez l\'export CSV à la place.';
            $message_type = 'warning';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
                
            case 'import_pieces':
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['csv_file']['tmp_name'];
                    $originalName = $_FILES['csv_file']['name'] ?? '';
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    // Brancher selon l'extension: Excel (xlsx/xls) ou CSV
                    if (in_array($extension, ['xlsx', 'xls'])) {
                        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                            $message = "L'import Excel nécessite PhpSpreadsheet. Veuillez l'installer via Composer (phpoffice/phpspreadsheet) ou utilisez un CSV.";
                            $message_type = 'error';
                            break;
                        }

                        $imported = 0;
                        $errors = [];
                        $lineNumber = 1; // 1 = en-tête

                        try {
                            $spreadsheet = IOFactory::load($file);
                            $worksheet = $spreadsheet->getActiveSheet();
                            $highestRow = $worksheet->getHighestRow();
                            $highestColumn = $worksheet->getHighestColumn();

                            // Lecture à partir de la ligne 2 (après l'en-tête)
                            for ($row = 2; $row <= $highestRow; $row++) {
                                $lineNumber = $row;
                                $nom = trim((string)$worksheet->getCellByColumnAndRow(1, $row)->getValue());
                                $categorie_nom = trim((string)$worksheet->getCellByColumnAndRow(2, $row)->getValue());
                                $fournisseur_nom = trim((string)$worksheet->getCellByColumnAndRow(3, $row)->getValue());
                                $quantite = (int)$worksheet->getCellByColumnAndRow(4, $row)->getCalculatedValue();
                                $stock_minimum = (int)$worksheet->getCellByColumnAndRow(5, $row)->getCalculatedValue();
                                $description = trim((string)$worksheet->getCellByColumnAndRow(6, $row)->getValue());

                                // Ligne vide? on saute
                                if ($nom === '' && $categorie_nom === '' && $fournisseur_nom === '' && $quantite === 0 && $stock_minimum === 0 && $description === '') {
                                    continue;
                                }

                                if ($nom === '') {
                                    $errors[] = "Ligne $lineNumber ignorée : nom de pièce manquant";
                                    continue;
                                }

                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pieces WHERE nom = ?");
                                    $stmt->execute([$nom]);
                                    if ($stmt->fetchColumn() > 0) {
                                        $errors[] = "Pièce '$nom' déjà existante, ignorée";
                                        continue;
                                    }

                                    $id_categorie = null;
                                    if ($categorie_nom !== '') {
                                        $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
                                        $stmt->execute([$categorie_nom]);
                                        $id_categorie = $stmt->fetchColumn();
                                        if (!$id_categorie) {
                                            $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                                            $stmt->execute([$categorie_nom]);
                                            $id_categorie = $pdo->lastInsertId();
                                        }
                                    }

                                    $id_fournisseur = null;
                                    if ($fournisseur_nom !== '') {
                                        $stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
                                        $stmt->execute([$fournisseur_nom]);
                                        $id_fournisseur = $stmt->fetchColumn();
                                        if (!$id_fournisseur) {
                                            $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom) VALUES (?)");
                                            $stmt->execute([$fournisseur_nom]);
                                            $id_fournisseur = $pdo->lastInsertId();
                                        }
                                    }

                                    $stmt = $pdo->prepare("INSERT INTO pieces (nom, id_categorie, id_fournisseur, quantite, stock_minimum, description) VALUES (?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([$nom, $id_categorie, $id_fournisseur, $quantite, $stock_minimum, $description]);
                                    $imported++;
                                } catch (Exception $e) {
                                    $errors[] = "Erreur à la ligne $lineNumber pour '$nom' : " . $e->getMessage();
                                }
                            }

                            $message = "$imported pièce(s) importée(s) avec succès !";
                            $message_type = 'success';
                            if (!empty($errors)) {
                                $message .= " Erreurs : " . implode(', ', array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $message .= " et " . (count($errors) - 5) . " autres...";
                                }
                            }
                        } catch (Exception $e) {
                            $message = "Erreur de lecture du fichier Excel : " . $e->getMessage();
                            $message_type = 'error';
                        }
                    } else {
                        // Traitement CSV (détection séparateur + encodage)
                        $handle = fopen($file, 'r');
                        if ($handle) {
                        // Détection automatique du séparateur (virgule, point-virgule, tabulation)
                        $firstLine = fgets($handle);
                        $commaCount = substr_count($firstLine, ',');
                        $semicolonCount = substr_count($firstLine, ';');
                        $tabCount = substr_count($firstLine, "\t");
                        $delimiter = ',';
                        if ($semicolonCount > $commaCount && $semicolonCount >= $tabCount) {
                            $delimiter = ';';
                        } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                            $delimiter = "\t";
                        }

                        // Revenir au début du fichier
                        rewind($handle);

                        // Lire et ignorer l'en-tête
                        $header = fgetcsv($handle, 0, $delimiter);

                        $imported = 0;
                        $errors = [];
                        $lineNumber = 1; // après l'en-tête

                        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                            $lineNumber++;
                            if (is_array($data)) {
                                // Normaliser encodage et espaces
                                foreach ($data as $idx => $value) {
                                    $value = is_string($value) ? trim($value) : $value;
                                    $data[$idx] = is_string($value)
                                        ? mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252')
                                        : $value;
                                }
                            }

                            if (count($data) < 4) {
                                $errors[] = "Ligne $lineNumber ignorée : colonnes insuffisantes (séparateur détecté: '" . ($delimiter === "\t" ? 'TAB' : $delimiter) . "')";
                                continue;
                            }

                            $nom = trim((string)$data[0]);
                            $categorie_nom = trim((string)$data[1]);
                            $fournisseur_nom = trim((string)$data[2]);
                            $quantite = (int)$data[3];
                            $stock_minimum = isset($data[4]) && $data[4] !== '' ? (int)$data[4] : 0;
                            $description = isset($data[5]) ? trim((string)$data[5]) : '';

                            if ($nom === '') {
                                $errors[] = "Ligne $lineNumber ignorée : nom de pièce manquant";
                                continue;
                            }

                            try {
                                // Vérifier si la pièce existe déjà
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM pieces WHERE nom = ?");
                                $stmt->execute([$nom]);
                                if ($stmt->fetchColumn() > 0) {
                                    $errors[] = "Pièce '$nom' déjà existante, ignorée";
                                    continue;
                                }

                                // Récupérer ou créer la catégorie
                                $id_categorie = null;
                                if ($categorie_nom !== '') {
                                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
                                    $stmt->execute([$categorie_nom]);
                                    $id_categorie = $stmt->fetchColumn();

                                    if (!$id_categorie) {
                                        $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                                        $stmt->execute([$categorie_nom]);
                                        $id_categorie = $pdo->lastInsertId();
                                    }
                                }

                                // Récupérer ou créer le fournisseur
                                $id_fournisseur = null;
                                if ($fournisseur_nom !== '') {
                                    $stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
                                    $stmt->execute([$fournisseur_nom]);
                                    $id_fournisseur = $stmt->fetchColumn();

                                    if (!$id_fournisseur) {
                                        $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom) VALUES (?)");
                                        $stmt->execute([$fournisseur_nom]);
                                        $id_fournisseur = $pdo->lastInsertId();
                                    }
                                }

                                // Insérer la pièce
                                $stmt = $pdo->prepare("INSERT INTO pieces (nom, id_categorie, id_fournisseur, quantite, stock_minimum, description) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$nom, $id_categorie, $id_fournisseur, $quantite, $stock_minimum, $description]);

                                $imported++;
                            } catch (Exception $e) {
                                $errors[] = "Erreur à la ligne $lineNumber pour '$nom' : " . $e->getMessage();
                            }
                        }

                        fclose($handle);

                        $message = "$imported pièce(s) importée(s) avec succès !";
                        $message_type = 'success';

                        if (!empty($errors)) {
                            $message .= " Erreurs : " . implode(', ', array_slice($errors, 0, 5));
                            if (count($errors) > 5) {
                                $message .= " et " . (count($errors) - 5) . " autres...";
                            }
                        }
                        } else {
                            $message = 'Erreur lors de la lecture du fichier.';
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = 'Veuillez sélectionner un fichier CSV valide.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-download"></i> Export / Import de Données
        </h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Export -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-download"></i> Export des données</h3>
            </div>
            <div class="card-body">
                <p style="color: #666; margin-bottom: 20px;">
                    Exportez vos données en format Excel (.xlsx) avec formatage professionnel.
                </p>
                
                <div style="display: grid; gap: 15px;">
                    <a href="export_excel.php?action=export_pieces_excel" class="btn btn-primary">
                        <i class="fas fa-file-excel"></i> Exporter les pièces en Excel (.xlsx)
                    </a>
                    
                    <a href="export_excel.php?action=export_mouvements_excel" class="btn btn-primary">
                        <i class="fas fa-file-excel"></i> Exporter les mouvements en Excel (.xlsx)
                    </a>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border-radius: 5px; border-left: 4px solid #007bff;">
                    <h4 style="margin: 0 0 10px 0; color: #007bff;">
                        <i class="fas fa-info-circle"></i> Avantages de l'export Excel
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #555;">
                        <li>Formatage professionnel avec bordures et couleurs</li>
                        <li>En-têtes en bleu avec texte blanc</li>
                        <li>Largeurs de colonnes optimisées</li>
                        <li>Caractères français parfaitement supportés</li>
                        <li>Fichier directement modifiable dans Excel</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Import -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-upload"></i> Import de Données</h3>
            </div>
            <div class="card-body">
                <p style="color: #666; margin-bottom: 20px;">
                    Importez des pièces depuis un fichier CSV. Les catégories et fournisseurs seront créés automatiquement si nécessaire.
                </p>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_pieces">
                    
                    <div class="form-group">
                        <label for="csv_file">Fichier à importer (CSV ou Excel .xlsx)</label>
                        <input type="file" id="csv_file" name="csv_file" class="form-control" accept=".csv,.xlsx,.xls" required>
                        <small style="color: #666;">Format attendu : Nom, Catégorie, Fournisseur, Quantité, Stock Minimum, Description</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-upload"></i> Importer les Pièces
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">
                        <i class="fas fa-exclamation-triangle"></i> Notes importantes :
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #856404;">
                        <li>Les pièces existantes seront ignorées</li>
                        <li>Les catégories et fournisseurs seront créés automatiquement</li>
                        <li>Séparateur accepté pour CSV : virgule, point-virgule ou tabulation</li>
                        <li>Vous pouvez aussi importer directement un fichier Excel (.xlsx)</li>
                        <li>La première ligne doit contenir les en-têtes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modèle de fichier CSV -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> Modèle de Fichier CSV</h3>
        </div>
        <div class="card-body">
            <p style="color: #666; margin-bottom: 15px;">
                Voici un exemple de fichier CSV pour l'import des pièces :
            </p>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 14px;">
                <div style="color: #007bff; margin-bottom: 10px;">Nom,Catégorie,Fournisseur,Quantité,Stock Minimum,Description</div>
                <div>Plaquettes de frein avant,Freins,AutoParts Plus,50,10,Plaquettes pour véhicules légers</div>
                <div>Filtre à air,Moteur,Mecanique Pro,25,5,Filtre à air haute performance</div>
                <div>Batterie 12V 60Ah,Électricité,Batteries Express,15,3,Batterie de démarrage</div>
                <div>Huile moteur 5W30,Lubrifiants,OilMaster,100,20,Huile synthétique</div>
            </div>
            
            <div style="margin-top: 15px;">
                <button class="btn btn-outline-secondary" onclick="copyToClipboard()">
                    <i class="fas fa-copy"></i> Copier le modèle
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #1e7e34;
}

.btn-outline-secondary {
    background: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
}

@media (max-width: 768px) {
    .card > div {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function copyToClipboard() {
    const model = `Nom,Catégorie,Fournisseur,Quantité,Stock Minimum,Description
Plaquettes de frein avant,Freins,AutoParts Plus,50,10,Plaquettes pour véhicules légers
Filtre à air,Moteur,Mecanique Pro,25,5,Filtre à air haute performance
Batterie 12V 60Ah,Électricité,Batteries Express,15,3,Batterie de démarrage
Huile moteur 5W30,Lubrifiants,OilMaster,100,20,Huile synthétique`;
    
    navigator.clipboard.writeText(model).then(function() {
        alert('Modèle copié dans le presse-papiers !');
    }, function(err) {
        console.error('Erreur lors de la copie : ', err);
        alert('Erreur lors de la copie. Veuillez copier manuellement.');
    });
}
</script>

<?php include 'includes/footer.php'; ?> 