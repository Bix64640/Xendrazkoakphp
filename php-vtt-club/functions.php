<?php
/**
 * =====================================================
 * Fonctions utilitaires générales
 * =====================================================
 */

require_once __DIR__ . '/db.php';

// =====================================================
// FONCTIONS DE SÉCURITÉ
// =====================================================

/**
 * Échapper les caractères HTML pour éviter les failles XSS
 * @param string $str Chaîne à échapper
 * @return string Chaîne échappée
 */
function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Générer un token CSRF
 * @return string Token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 * @param string $token Token à vérifier
 * @return bool True si valide
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Afficher un champ hidden CSRF dans un formulaire
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// =====================================================
// FONCTIONS DE REDIRECTION ET MESSAGES
// =====================================================

/**
 * Rediriger vers une URL
 * @param string $url URL de destination
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Définir un message flash (message temporaire)
 * @param string $type Type de message (success, error, warning, info)
 * @param string $message Contenu du message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Afficher et supprimer le message flash
 */
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $class = '';
        switch ($flash['type']) {
            case 'success': $class = 'alert-success'; break;
            case 'error': $class = 'alert-error'; break;
            case 'warning': $class = 'alert-warning'; break;
            default: $class = 'alert-info';
        }
        echo '<div class="alert ' . $class . '">' . escape($flash['message']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// =====================================================
// FONCTIONS DE VALIDATION
// =====================================================

/**
 * Valider une adresse email
 * @param string $email Email à valider
 * @return bool True si valide
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valider que la chaîne n'est pas vide
 * @param string $str Chaîne à vérifier
 * @return bool True si non vide
 */
function isNotEmpty($str) {
    return isset($str) && trim($str) !== '';
}

/**
 * Valider un nombre entier positif
 * @param mixed $value Valeur à vérifier
 * @return bool True si entier positif
 */
function isPositiveInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false && $value > 0;
}

// =====================================================
// FONCTIONS DE FORMATAGE
// =====================================================

/**
 * Formater une date en français
 * @param string $date Date au format MySQL
 * @param bool $withTime Inclure l'heure
 * @return string Date formatée
 */
function formatDate($date, $withTime = false) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($withTime) {
        return date('d/m/Y à H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Formater un prix
 * @param float $prix Prix à formater
 * @return string Prix formaté
 */
function formatPrix($prix) {
    if ($prix === null || $prix === '') return 'Non précisé';
    return number_format($prix, 2, ',', ' ') . ' €';
}

/**
 * Tronquer un texte
 * @param string $text Texte à tronquer
 * @param int $length Longueur maximale
 * @return string Texte tronqué
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// =====================================================
// FONCTIONS D'UPLOAD DE FICHIERS
// =====================================================

/**
 * Uploader une image
 * @param array $file Fichier $_FILES
 * @param string $subdir Sous-dossier de destination
 * @return string|false Chemin du fichier ou false en cas d'erreur
 */
function uploadImage($file, $subdir = '') {
    // Vérifier si un fichier a été uploadé
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Vérifier la taille
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Vérifier l'extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Créer le dossier si nécessaire
    $uploadDir = UPLOAD_DIR . ($subdir ? $subdir . '/' : '');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom unique
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ($subdir ? $subdir . '/' : '') . $filename;
    }
    
    return false;
}

/**
 * Supprimer une image
 * @param string $path Chemin relatif de l'image
 * @return bool Succès de la suppression
 */
function deleteImage($path) {
    if (empty($path)) return false;
    $fullPath = UPLOAD_DIR . $path;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

// =====================================================
// FONCTIONS DE NOTIFICATION
// =====================================================

/**
 * Créer une notification pour un utilisateur
 * @param int $userId ID de l'utilisateur
 * @param string $titre Titre de la notification
 * @param string $message Message de la notification
 */
function createNotification($userId, $titre, $message) {
    $sql = "INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)";
    dbExecute($sql, [$userId, $titre, $message]);
}

/**
 * Compter les notifications non lues d'un utilisateur
 * @param int $userId ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function countUnreadNotifications($userId) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $result = dbFetchOne($sql, [$userId]);
    return $result ? $result['count'] : 0;
}

// =====================================================
// FONCTIONS DE LOG
// =====================================================

/**
 * Enregistrer un changement de statut
 * @param string $table Table concernée
 * @param int $elementId ID de l'élément
 * @param string $ancienStatut Ancien statut
 * @param string $nouveauStatut Nouveau statut
 * @param int $changedBy ID de l'utilisateur qui a fait le changement
 */
function logStatutChange($table, $elementId, $ancienStatut, $nouveauStatut, $changedBy) {
    $sql = "INSERT INTO logs_statuts (table_cible, element_id, ancien_statut, nouveau_statut, changed_by) 
            VALUES (?, ?, ?, ?, ?)";
    dbExecute($sql, [$table, $elementId, $ancienStatut, $nouveauStatut, $changedBy]);
}

// =====================================================
// FONCTIONS CLUB INFO
// =====================================================

/**
 * Récupérer les informations du club
 * @return array|false Informations du club
 */
function getClubInfo() {
    $sql = "SELECT * FROM club_infos LIMIT 1";
    return dbFetchOne($sql);
}

/**
 * Mettre à jour l'URL YouTube
 * @param string $url Nouvelle URL
 */
function updateYoutubeUrl($url) {
    $sql = "UPDATE club_infos SET youtube_url = ? WHERE id = 1";
    dbExecute($sql, [$url]);
}
