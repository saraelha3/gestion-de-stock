<?php
// Configuration de l'application de gestion de stock

// Informations de l'application
define('APP_NAME', 'Gestion de Stock - Pièces Automobiles');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Votre Nom');

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_stock');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration des sessions
define('SESSION_LIFETIME', 3600); // 1 heure
define('SESSION_NAME', 'gestion_stock_session');

// Configuration des alertes
define('ALERT_STOCK_FAIBLE_PERCENTAGE', 20); // Pourcentage pour considérer un stock comme faible
define('ALERT_EMAIL_ENABLED', false); // Activer les alertes par email
define('ALERT_EMAIL_FROM', 'noreply@votreentreprise.com');

// Configuration des exports
define('EXPORT_MAX_ROWS', 10000); // Nombre maximum de lignes pour l'export
define('EXPORT_DATE_FORMAT', 'd/m/Y H:i');

// Configuration de l'interface
define('ITEMS_PER_PAGE', 20); // Nombre d'éléments par page
define('MAX_SEARCH_RESULTS', 100); // Nombre maximum de résultats de recherche

// Configuration de sécurité
define('PASSWORD_MIN_LENGTH', 6);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Configuration des logs
define('LOG_ENABLED', true);
define('LOG_FILE', 'logs/app.log');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Configuration des uploads
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['csv', 'txt']);

// Messages d'erreur personnalisés
$ERROR_MESSAGES = [
    'db_connection' => 'Erreur de connexion à la base de données',
    'db_query' => 'Erreur lors de l\'exécution de la requête',
    'login_failed' => 'Nom d\'utilisateur ou mot de passe incorrect',
    'access_denied' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
    'file_upload' => 'Erreur lors du téléchargement du fichier',
    'validation_failed' => 'Les données saisies ne sont pas valides',
    'stock_insufficient' => 'Stock insuffisant pour cette opération',
    'item_not_found' => 'L\'élément demandé n\'a pas été trouvé',
    'duplicate_entry' => 'Cet élément existe déjà dans la base de données'
];

// Configuration des graphiques
define('CHART_COLORS', [
    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
]);

// Configuration des notifications
define('NOTIFICATION_TYPES', [
    'stock_low' => 'Stock faible',
    'stock_out' => 'Rupture de stock',
    'movement_added' => 'Mouvement ajouté',
    'user_login' => 'Connexion utilisateur'
]);

// Configuration des rôles
define('ROLES', [
    'admin' => 'Administrateur',
    'employe' => 'Employé'
]);

// Configuration des types de mouvements
define('MOVEMENT_TYPES', [
    'entree' => 'Entrée',
    'sortie' => 'Sortie'
]);

// Configuration des statuts de stock
define('STOCK_STATUS', [
    'normal' => 'Normal',
    'low' => 'Faible',
    'out' => 'Rupture'
]);

// Fonction pour obtenir la configuration
function getConfig($key, $default = null) {
    global $ERROR_MESSAGES;
    
    switch ($key) {
        case 'error_messages':
            return $ERROR_MESSAGES;
        case 'db_host':
            return DB_HOST;
        case 'db_name':
            return DB_NAME;
        case 'db_user':
            return DB_USER;
        case 'db_pass':
            return DB_PASS;
        case 'app_name':
            return APP_NAME;
        case 'app_version':
            return APP_VERSION;
        case 'session_lifetime':
            return SESSION_LIFETIME;
        case 'items_per_page':
            return ITEMS_PER_PAGE;
        case 'chart_colors':
            return CHART_COLORS;
        case 'roles':
            return ROLES;
        case 'movement_types':
            return MOVEMENT_TYPES;
        case 'stock_status':
            return STOCK_STATUS;
        default:
            return $default;
    }
}

// Fonction pour obtenir un message d'erreur
function getErrorMessage($key) {
    $messages = getConfig('error_messages');
    return isset($messages[$key]) ? $messages[$key] : 'Erreur inconnue';
}

// Fonction pour valider les données
function validateData($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        if (!isset($data[$field]) || empty($data[$field])) {
            if (isset($rule['required']) && $rule['required']) {
                $errors[$field] = "Le champ '$field' est requis.";
            }
        } else {
            $value = $data[$field];
            
            // Validation de la longueur
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Le champ '$field' doit contenir au moins {$rule['min_length']} caractères.";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Le champ '$field' ne peut pas dépasser {$rule['max_length']} caractères.";
            }
            
            // Validation du type
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Le champ '$field' doit être une adresse email valide.";
                        }
                        break;
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$field] = "Le champ '$field' doit être un nombre.";
                        }
                        break;
                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field] = "Le champ '$field' doit être un nombre entier.";
                        }
                        break;
                }
            }
        }
    }
    
    return $errors;
}

// Fonction pour logger les événements
function logEvent($level, $message, $context = []) {
    if (!LOG_ENABLED) {
        return;
    }
    
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message";
    
    if (!empty($context)) {
        $logEntry .= ' ' . json_encode($context);
    }
    
    $logEntry .= PHP_EOL;
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Fonction pour nettoyer les données
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction pour formater les dates
function formatDate($date, $format = null) {
    if (!$format) {
        $format = EXPORT_DATE_FORMAT;
    }
    
    return date($format, strtotime($date));
}

// Fonction pour vérifier les permissions
function hasPermission($userRole, $requiredRole) {
    $roleHierarchy = [
        'employe' => 1,
        'admin' => 2
    ];
    
    $userLevel = isset($roleHierarchy[$userRole]) ? $roleHierarchy[$userRole] : 0;
    $requiredLevel = isset($roleHierarchy[$requiredRole]) ? $roleHierarchy[$requiredRole] : 0;
    
    return $userLevel >= $requiredLevel;
}
?> 