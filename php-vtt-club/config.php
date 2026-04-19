<?php
/**
 * =====================================================
 * Configuration générale du site Xendrazkoak VTT
 * =====================================================
 * Ce fichier contient toutes les constantes de configuration
 */

// Mode debug (à mettre à false en production)
define('DEBUG_MODE', true);

// Affichage des erreurs en mode debug
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// =====================================================
// Configuration de la base de données
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'Xendrazkoak_vtt');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// Configuration du site
// =====================================================
define('SITE_NAME', 'Xendrazkoak VTT');
define('SITE_URL', 'http://localhost/php-vtt-club');

// =====================================================
// Configuration des uploads
// =====================================================
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('MAX_IMAGES_PER_ANNONCE', 3);

// =====================================================
// Configuration des rôles
// =====================================================
define('ROLE_VISITEUR', 'visiteur');
define('ROLE_UTILISATEUR', 'utilisateur');
define('ROLE_BUREAU', 'bureau');
define('ROLE_ADMIN', 'admin');

// =====================================================
// Configuration des statuts de compte
// =====================================================
define('STATUT_EN_ATTENTE', 'en_attente');
define('STATUT_VALIDE', 'valide');
define('STATUT_REFUSE', 'refuse');
define('STATUT_DESACTIVE', 'desactive');

// =====================================================
// Démarrage de la session
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// Fuseau horaire
// =====================================================
date_default_timezone_set('Europe/Paris');

// =====================================================
// Flags et credentiels utilitaires (sécurité)
// =====================================================
// Par défaut, la création automatique d'un compte admin via
// `creat_admin.php` est désactivée pour des raisons de sécurité.
// Pour l'activer en environnement de développement, définissez
// ADMIN_CREDENTIALS_VISIBLE à true (et fournissez ADMIN_EMAIL/ADMIN_PASS).
// NE JAMAIS activer ceci en production.
if (!defined('ADMIN_CREDENTIALS_VISIBLE')) {
    // Par défaut, on l'active uniquement si DEBUG_MODE est true
    define('ADMIN_CREDENTIALS_VISIBLE', DEBUG_MODE === true);
}

if (!defined('ADMIN_EMAIL')) {
    // Valeurs par défaut vides ; modifiez si besoin en dev
    define('ADMIN_EMAIL', 'admin@example.local');
}

if (!defined('ADMIN_PASS')) {
    // Mot de passe par défaut (déconseillé). Changez en dev si utile.
    define('ADMIN_PASS', 'changeme');
}
