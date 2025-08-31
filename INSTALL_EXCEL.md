# Installation de l'export Excel

## Option 1 : Installation automatique avec Composer (Recommandée)

### Prérequis
- Composer installé sur votre système
- PHP 7.4 ou supérieur

### Étapes d'installation

1. **Ouvrir un terminal** dans le dossier du projet
2. **Installer les dépendances** :
   ```bash
   composer install
   ```

3. **Vérifier l'installation** :
   - L'export Excel sera automatiquement disponible
   - Les boutons "Excel" apparaîtront dans la page Export/Import

## Option 2 : Installation manuelle

### Télécharger PhpSpreadsheet

1. **Télécharger** PhpSpreadsheet depuis GitHub :
   - Aller sur : https://github.com/PHPOffice/PhpSpreadsheet
   - Cliquer sur "Code" → "Download ZIP"

2. **Extraire** le fichier ZIP dans le dossier du projet

3. **Créer** un dossier `vendor` et y placer le contenu

4. **Modifier** le fichier `export_excel.php` pour inclure l'autoloader :
   ```php
   require_once 'vendor/autoload.php';
   ```

## Vérification de l'installation

1. **Accéder** à la page Export/Import
2. **Vérifier** que les boutons "Excel" sont visibles
3. **Tester** l'export en cliquant sur un bouton Excel

## Dépannage

### Erreur "Class not found"
- Vérifier que PhpSpreadsheet est bien installé
- Vérifier que l'autoloader est inclus

### Erreur de permissions
- Vérifier les permissions du dossier `vendor`
- Vérifier que PHP peut écrire dans le dossier temporaire

### Fichier Excel corrompu
- Vérifier que l'extension PHP `zip` est activée
- Vérifier que l'extension PHP `xml` est activée

## Fonctionnalités de l'export Excel

### Formatage automatique
- **En-têtes** en bleu avec texte blanc
- **Bordures** sur toutes les cellules
- **Largeur des colonnes** optimisée
- **Alignement** centré pour les en-têtes

### Compatibilité
- **Excel 2007+** (.xlsx)
- **LibreOffice Calc**
- **Google Sheets**

### Avantages par rapport au CSV
- **Encodage UTF-8** garanti
- **Mise en forme** professionnelle
- **Caractères spéciaux** supportés
- **Formules Excel** possibles (futur)

## Support

Si vous rencontrez des problèmes :
1. Vérifier les logs PHP
2. Vérifier les permissions
3. Tester avec un export CSV en attendant 