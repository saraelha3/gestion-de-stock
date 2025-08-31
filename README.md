# Gestion de Stock - Pièces Automobiles

Une application web professionnelle et complète pour la gestion de stock de pièces automobiles, développée en PHP, MySQL, HTML, CSS et JavaScript.

## 🚀 Fonctionnalités Principales

### ✅ Fonctionnalités Implémentées

- **🔐 Authentification sécurisée** avec gestion des rôles (admin/employé)
- **📦 Gestion complète des pièces** : CRUD, recherche, filtrage, alertes de stock
- **🏷️ Gestion des catégories** : organisation et statistiques d'utilisation
- **🚛 Gestion des fournisseurs** : informations complètes avec contacts
- **📊 Gestion des mouvements** : entrées/sorties avec historique détaillé
- **📈 Tableau de bord avancé** : statistiques temps réel et graphiques
- **⚠️ Système d'alertes** : détection automatique des stocks faibles et ruptures
- **👥 Gestion des utilisateurs** : création, modification, gestion des rôles
- **📋 Rapports et statistiques** : graphiques, analyses, top utilisations
- **💾 Export/Import CSV** : sauvegarde et restauration des données
- **👤 Profil utilisateur** : modification des informations personnelles
- **📱 Interface responsive** : adaptée à tous les appareils
- **🎨 Design moderne** : interface professionnelle avec animations

### 🔧 Fonctionnalités Techniques

- **Configuration centralisée** : fichier `config.php` pour tous les paramètres
- **Système de logs** : traçabilité des actions et erreurs
- **Validation des données** : sécurité et intégrité des informations
- **Gestion des permissions** : contrôle d'accès granulaire
- **Optimisation des performances** : requêtes optimisées et pagination

## 📋 Prérequis

- **PHP** 7.4 ou supérieur
- **MySQL** 5.7 ou supérieur  
- **Serveur web** (Apache/Nginx)
- **Extensions PHP** : PDO, PDO_MySQL, JSON

## 🛠️ Installation

### 1. Préparation
```bash
# Cloner ou télécharger le projet
git clone [url-du-projet]
cd Gestion_de_Stock
```

### 2. Configuration de la base de données
```sql
-- Créer la base de données
CREATE DATABASE gestion_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Initialisation
- **Option A** : Exécuter `setup_database.php` via le navigateur
- **Option B** : Importer le fichier `shèma_database` dans phpMyAdmin

### 4. Configuration
Modifier `config.php` selon votre environnement :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_stock');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 5. Accès
Ouvrir `http://localhost/Gestion_de_Stock/login.php`

## 🔑 Identifiants par défaut

- **Utilisateur** : `admin`
- **Mot de passe** : `admin123`
- **Rôle** : Administrateur

## 📁 Structure du projet

```
Gestion_de_Stock/
├── 📁 assets/
│   └── 📁 css/
│       ├── style.css              # Styles généraux
│       └── dashboard.css          # Styles du dashboard
├── 📁 includes/
│   ├── db.php                     # Connexion à la base de données
│   ├── auth.php                   # Fonctions d'authentification
│   ├── header.php                 # En-tête commun
│   └── footer.php                 # Pied de page commun
├── 📁 logs/                       # Fichiers de logs (créé automatiquement)
├── 🔧 config.php                  # Configuration centralisée
├── 🔐 login.php                   # Page de connexion
├── 📊 dashboard.php               # Tableau de bord principal
├── 📦 pieces.php                  # Gestion des pièces
├── 🏷️ categories.php              # Gestion des catégories
├── 🚛 fournisseurs.php            # Gestion des fournisseurs
├── 📊 mouvements.php              # Gestion des mouvements
├── ⚠️ alertes.php                 # Alertes de stock
├── 📈 rapports.php                # Rapports et statistiques
├── 👥 utilisateurs.php            # Gestion des utilisateurs (admin)
├── 💾 export_import.php           # Export/Import CSV
├── 👤 profile.php                 # Profil utilisateur
├── 🔧 setup_database.php          # Script d'initialisation
├── 📋 shèma_database              # Schéma de la base de données
└── 📖 README.md                   # Documentation
```

## 🎯 Fonctionnalités détaillées

### 🔐 Authentification et Sécurité
- **Connexion sécurisée** avec hachage bcrypt
- **Gestion des sessions** avec timeout configurable
- **Protection CSRF** et validation des données
- **Gestion des rôles** : admin (accès complet) / employé (accès limité)
- **Logs de sécurité** : connexions, tentatives d'accès

### 📦 Gestion des Pièces
- **CRUD complet** avec validation des données
- **Recherche avancée** : par nom, catégorie, fournisseur
- **Filtres multiples** : statut de stock, catégorie
- **Indicateurs visuels** : badges de statut, couleurs
- **Modal responsive** pour ajout/modification
- **Protection contre la suppression** de pièces utilisées

### 🏷️ Gestion des Catégories
- **Organisation hiérarchique** des pièces
- **Statistiques d'utilisation** par catégorie
- **Protection contre la suppression** de catégories utilisées
- **Interface intuitive** avec modals

### 🚛 Gestion des Fournisseurs
- **Informations complètes** : nom, contact, téléphone, email
- **Liens cliquables** : téléphone et email
- **Statistiques** : nombre de pièces fournies
- **Recherche** par nom, contact ou email

### 📊 Gestion des Mouvements
- **Entrées et sorties** de stock avec validation
- **Historique complet** : date, utilisateur, commentaires
- **Protection contre les sorties** sans stock suffisant
- **Filtrage avancé** : par type, date, pièce
- **Interface intuitive** avec sélection de pièces

### ⚠️ Système d'Alertes
- **Détection automatique** des stocks faibles et ruptures
- **Interface dédiée** avec actions rapides
- **Historique des mouvements** pour les pièces en alerte
- **Actions directes** : ajout de stock depuis les alertes

### 📈 Rapports et Statistiques
- **Tableau de bord** avec métriques temps réel
- **Graphiques interactifs** : répartition par catégories, mouvements
- **Top utilisations** : pièces et catégories les plus utilisées
- **Mouvements récents** avec filtres
- **Alertes visuelles** sur le dashboard

### 👥 Gestion des Utilisateurs (Admin)
- **CRUD complet** des utilisateurs
- **Gestion des rôles** : admin/employé
- **Modification sécurisée** des mots de passe
- **Protection** contre la suppression de son propre compte
- **Statistiques** par utilisateur

### 💾 Export/Import CSV
- **Export complet** : pièces, mouvements
- **Import sécurisé** : validation et création automatique
- **Gestion des erreurs** : rapport détaillé des importations
- **Modèle de fichier** fourni
- **Support UTF-8** pour les caractères spéciaux

### 👤 Profil Utilisateur
- **Modification** des informations personnelles
- **Changement sécurisé** du mot de passe
- **Statistiques personnelles** : mouvements effectués
- **Historique** des dernières actions

## 🎨 Design et Interface

### Caractéristiques visuelles
- **Design responsive** : mobile-first approach
- **Interface moderne** : gradients, ombres, animations CSS
- **Navigation intuitive** : sidebar avec icônes FontAwesome
- **Palette cohérente** : couleurs professionnelles
- **Typographie optimisée** : lisibilité maximale

### Composants UI
- **Sidebar rétractable** : navigation principale
- **Header dynamique** : informations utilisateur
- **Cards modulaires** : conteneurs flexibles
- **Modals centrés** : formulaires popup
- **Tables interactives** : tri, filtrage, pagination
- **Formulaires validés** : feedback en temps réel

## 🔒 Sécurité

### Mesures implémentées
- **Hachage sécurisé** : `password_hash()` avec bcrypt
- **Protection SQL** : PDO avec requêtes préparées
- **Validation stricte** : côté serveur et client
- **Échappement des sorties** : `htmlspecialchars()`
- **Gestion des sessions** : timeout, régénération d'ID
- **Contrôle d'accès** : vérification des permissions
- **Logs de sécurité** : traçabilité complète

### Bonnes pratiques
- **Configuration centralisée** : paramètres sécurisés
- **Validation des données** : règles strictes
- **Gestion des erreurs** : messages sécurisés
- **Protection des fichiers** : accès restreint

## 📊 Performance et Optimisation

### Optimisations implémentées
- **Requêtes optimisées** : JOINs et index appropriés
- **Pagination** : limitation des résultats
- **Cache des requêtes** : réduction des appels DB
- **Compression CSS/JS** : chargement optimisé
- **Images optimisées** : formats modernes

### Monitoring
- **Logs détaillés** : performance et erreurs
- **Statistiques d'utilisation** : métriques temps réel
- **Alertes automatiques** : détection des problèmes

## 🚀 Fonctionnalités avancées

### Système de notifications
- **Alertes en temps réel** : stocks faibles, ruptures
- **Notifications visuelles** : badges, couleurs
- **Historique des alertes** : suivi des actions

### Recherche et filtrage
- **Recherche en temps réel** : autocomplétion
- **Filtres multiples** : combinaison de critères
- **Tri avancé** : colonnes personnalisables
- **Sauvegarde des filtres** : préférences utilisateur

### Export et reporting
- **Formats multiples** : CSV, PDF (prévu)
- **Templates personnalisables** : rapports sur mesure
- **Planification** : exports automatiques (prévu)

## 🔧 Configuration

### Fichier config.php
```php
// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_stock');
define('DB_USER', 'root');
define('DB_PASS', '');

// Sécurité
define('SESSION_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 6);

// Interface
define('ITEMS_PER_PAGE', 20);
define('MAX_SEARCH_RESULTS', 100);

// Alertes
define('ALERT_STOCK_FAIBLE_PERCENTAGE', 20);
define('ALERT_EMAIL_ENABLED', false);
```

### Personnalisation
- **Couleurs** : modification de la palette dans les CSS
- **Logo** : remplacement dans le header
- **Langue** : traduction des messages
- **Modules** : activation/désactivation des fonctionnalités

## 📈 Évolutions prévues

### Fonctionnalités futures
- **📧 Notifications par email** : alertes automatiques
- **📄 Génération PDF** : rapports et factures
- **🔗 API REST** : intégration externe
- **📱 Application mobile** : accès mobile natif
- **🔄 Synchronisation** : multi-sites
- **📊 Graphiques avancés** : analyses prédictives
- **🤖 Intelligence artificielle** : suggestions automatiques

### Optimisations prévues
- **⚡ Cache avancé** : Redis/Memcached
- **🔍 Recherche full-text** : Elasticsearch
- **📦 CDN** : distribution des assets
- **🔄 Webhooks** : intégrations tierces

## 🛠️ Maintenance

### Tâches régulières
- **Sauvegarde** : base de données et fichiers
- **Mise à jour** : sécurité et fonctionnalités
- **Monitoring** : logs et performances
- **Optimisation** : requêtes et cache

### Support
- **Documentation** : guides utilisateur
- **Formation** : sessions d'apprentissage
- **Assistance** : support technique
- **Évolutions** : demandes de fonctionnalités

## 📞 Support et Contribution

### Comment contribuer
1. **Fork** le projet
2. **Créer** une branche pour votre fonctionnalité
3. **Développer** avec les bonnes pratiques
4. **Tester** exhaustivement
5. **Soumettre** une pull request

### Contact
- **Issues** : bugs et demandes de fonctionnalités
- **Discussions** : questions et suggestions
- **Documentation** : améliorations et corrections

## 📄 Licence

Ce projet est sous licence **MIT**. Vous êtes libre de :
- ✅ Utiliser le code pour des projets commerciaux
- ✅ Modifier et adapter selon vos besoins
- ✅ Distribuer et partager
- ✅ Utiliser en privé

## 🙏 Remerciements

- **FontAwesome** : icônes et interface
- **Chart.js** : graphiques interactifs
- **Communauté PHP** : outils et bonnes pratiques
- **Contributeurs** : améliorations et suggestions

---

**Version** : 1.0.0  
**Dernière mise à jour** : Décembre 2024  
**Auteur** : Votre Nom  
**Statut** : Production Ready ✅ 