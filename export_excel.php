<?php
// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'vendor/autoload.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté et est admin
requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// Vérifier si PhpSpreadsheet est installé
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    // Si PhpSpreadsheet n'est pas installé, rediriger vers l'export CSV
    header('Location: export_import.php');
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$action = $_GET['action'] ?? '';

if ($action === 'export_pieces_excel') {
    try {
        // Export des pièces en Excel
        $stmt = $pdo->query("
            SELECT p.nom, c.nom as categorie, f.nom as fournisseur, p.quantite, p.stock_minimum, p.description
            FROM pieces p 
            LEFT JOIN categories c ON p.id_categorie = c.id 
            LEFT JOIN fournisseurs f ON p.id_fournisseur = f.id 
            ORDER BY p.nom
        ");
        $pieces = $stmt->fetchAll();
        
        // Créer un nouveau document Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Titre du document
        $sheet->setTitle('Pièces');
        
        // En-têtes
        $headers = ['Nom', 'Catégorie', 'Fournisseur', 'Quantité', 'Stock Minimum', 'Description'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Style des en-têtes
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Données
        $row = 2;
        foreach ($pieces as $piece) {
            $sheet->setCellValue('A' . $row, $piece['nom']);
            $sheet->setCellValue('B' . $row, $piece['categorie'] ?? '');
            $sheet->setCellValue('C' . $row, $piece['fournisseur'] ?? '');
            $sheet->setCellValue('D' . $row, $piece['quantite']);
            $sheet->setCellValue('E' . $row, $piece['stock_minimum']);
            $sheet->setCellValue('F' . $row, $piece['description'] ?? '');
            $row++;
        }
        
        // Style des données
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:F' . $lastRow)->applyFromArray($dataStyle);
        }
        
        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(40);
        
        // Créer le fichier Excel
        $writer = new Xlsx($spreadsheet);
        $filename = 'pieces_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Définir les propriétés du document pour le rendre modifiable
        $spreadsheet->getProperties()
            ->setCreator('Gestion de Stock')
            ->setLastModifiedBy('Utilisateur')
            ->setTitle('Export des pièces')
            ->setSubject('Inventaire des pièces automobiles')
            ->setDescription('Export des pièces du système de gestion de stock')
            ->setKeywords('inventaire, pièces, automobiles')
            ->setCategory('Inventaire');
        
        // Nettoyer tout output avant les headers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        
        $writer->save('php://output');
        exit();
        
    } catch (Exception $e) {
        // En cas d'erreur, rediriger vers l'export CSV
        header('Location: export_import.php?error=excel_failed');
        exit();
    }
    
} elseif ($action === 'export_mouvements_excel') {
    try {
        // Export des mouvements en Excel
        $stmt = $pdo->query("
            SELECT m.date, p.nom as piece, m.type, m.quantite, u.nom_utilisateur, m.commentaire
            FROM mouvements m 
            LEFT JOIN pieces p ON m.id_piece = p.id 
            LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id 
            ORDER BY m.date DESC
        ");
        $mouvements = $stmt->fetchAll();
        
        // Créer un nouveau document Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Titre du document
        $sheet->setTitle('Mouvements');
        
        // En-têtes
        $headers = ['Date', 'Pièce', 'Type', 'Quantité', 'Utilisateur', 'Commentaire'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Style des en-têtes
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Données
        $row = 2;
        foreach ($mouvements as $mouvement) {
            $sheet->setCellValue('A' . $row, $mouvement['date']);
            $sheet->setCellValue('B' . $row, $mouvement['piece']);
            $sheet->setCellValue('C' . $row, $mouvement['type']);
            $sheet->setCellValue('D' . $row, $mouvement['quantite']);
            $sheet->setCellValue('E' . $row, $mouvement['nom_utilisateur']);
            $sheet->setCellValue('F' . $row, $mouvement['commentaire'] ?? '');
            $row++;
        }
        
        // Style des données
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:F' . $lastRow)->applyFromArray($dataStyle);
        }
        
        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(40);
        
        // Créer le fichier Excel
        $writer = new Xlsx($spreadsheet);
        $filename = 'mouvements_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Définir les propriétés du document pour le rendre modifiable
        $spreadsheet->getProperties()
            ->setCreator('Gestion de Stock')
            ->setLastModifiedBy('Utilisateur')
            ->setTitle('Export des mouvements')
            ->setSubject('Mouvements de stock')
            ->setDescription('Export des mouvements du système de gestion de stock')
            ->setKeywords('mouvements, stock, inventaire')
            ->setCategory('Mouvements');
        
        // Nettoyer tout output avant les headers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        
        $writer->save('php://output');
        exit();
        
    } catch (Exception $e) {
        // En cas d'erreur, rediriger vers l'export CSV
        header('Location: export_import.php?error=excel_failed');
        exit();
    }
}

// Si aucune action valide, rediriger
header('Location: export_import.php');
exit();
?> 