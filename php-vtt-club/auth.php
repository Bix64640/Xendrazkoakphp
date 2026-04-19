<?php
/**
 * =====================================================
 * Fonctions d'authentification
 * =====================================================
 */

require_once __DIR__ . '/functions.php';

// =====================================================
// FONCTIONS DE CONNEXION / DÉCONNEXION
// =====================================================

/**
 * Vérifier si l'utilisateur est connecté
 * @return bool True si connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Obtenir l'utilisateur connecté
 * @return array|null Données de l'utilisateur ou null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Cache en session pour éviter les requêtes répétées
    if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['id'] !== $_SESSION['user_id']) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $_SESSION['user_data'] = dbFetchOne($sql, [$_SESSION['user_id']]);
    }
    
    return $_SESSION['user_data'];
}

/**
 * Rafraîchir les données utilisateur en session
 */
function refreshUserData() {
    if (isLoggedIn()) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $_SESSION['user_data'] = dbFetchOne($sql, [$_SESSION['user_id']]);
    }
}

/**
 * Connecter un utilisateur
 * @param string $email Email
 * @param string $password Mot de passe
 * @return array ['success' => bool, 'message' => string]
 */
function login($email, $password) {
    // Chercher l'utilisateur par email
    $sql = "SELECT * FROM users WHERE email = ?";
    $user = dbFetchOne($sql, [$email]);
    
    // Vérifier si l'utilisateur existe
    if (!$user) {
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
    }
    
    // Vérifier le statut du compte
    switch ($user['statut_compte']) {
        case STATUT_EN_ATTENTE:
            return ['success' => false, 'message' => 'Votre compte est en attente de validation par un membre du bureau.'];
        case STATUT_REFUSE:
            return ['success' => false, 'message' => 'Votre demande d\'inscription a été refusée.'];
        case STATUT_DESACTIVE:
            return ['success' => false, 'message' => 'Votre compte a été désactivé. Contactez un administrateur.'];
    }
    
    // Connexion réussie
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_data'] = $user;
    
    return ['success' => true, 'message' => 'Connexion réussie !'];
}

/**
 * Déconnecter l'utilisateur
 */
function logout() {
    session_destroy();
}

/**
 * Inscrire un nouvel utilisateur
 * @param array $data Données du formulaire
 * @return array ['success' => bool, 'message' => string]
 */
function register($data) {
    // Validation des champs obligatoires
    if (!isNotEmpty($data['nom']) || !isNotEmpty($data['prenom'])) {
        return ['success' => false, 'message' => 'Le nom et le prénom sont obligatoires.'];
    }
    
    if (!isValidEmail($data['email'])) {
        return ['success' => false, 'message' => 'L\'adresse email n\'est pas valide.'];
    }
    
    if (!isPositiveInt($data['age'])) {
        return ['success' => false, 'message' => 'L\'âge doit être un nombre positif.'];
    }
    
    // Responsable obligatoire si mineur
    if ($data['age'] < 18 && !isNotEmpty($data['responsable_nom_prenom'])) {
        return ['success' => false, 'message' => 'Le nom du responsable légal est obligatoire pour les mineurs.'];
    }
    
    if (!isNotEmpty($data['telephone_urgence'])) {
        return ['success' => false, 'message' => 'Le numéro d\'urgence est obligatoire.'];
    }
    
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    }
    
    if ($data['password'] !== $data['password_confirm']) {
        return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
    }
    
    // Vérifier si l'email existe déjà
    $sql = "SELECT id FROM users WHERE email = ?";
    if (dbFetchOne($sql, [$data['email']])) {
        return ['success' => false, 'message' => 'Cette adresse email est déjà utilisée.'];
    }
    
    // Hasher le mot de passe
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insérer l'utilisateur
    $sql = "INSERT INTO users (nom, prenom, email, password_hash, age, responsable_nom_prenom, telephone_urgence, commentaire, role, statut_compte) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'utilisateur', 'en_attente')";
    
    dbExecute($sql, [
        trim($data['nom']),
        trim($data['prenom']),
        trim($data['email']),
        $passwordHash,
        (int)$data['age'],
        $data['age'] < 18 ? trim($data['responsable_nom_prenom']) : null,
        trim($data['telephone_urgence']),
        trim($data['commentaire'] ?? '')
    ]);
    
    // Notifier les admins et membres du bureau
    $newUserId = dbLastInsertId();
    $admins = dbFetchAll("SELECT id FROM users WHERE role IN ('admin', 'bureau') AND statut_compte = 'valide'");
    foreach ($admins as $admin) {
        createNotification($admin['id'], 'Nouvelle inscription', 
            "Un nouvel utilisateur ({$data['prenom']} {$data['nom']}) s'est inscrit et attend validation.");
    }
    
    return ['success' => true, 'message' => 'Inscription réussie ! Votre compte est en attente de validation.'];
}

// =====================================================
// FONCTIONS DE CONTRÔLE D'ACCÈS
// =====================================================

/**
 * Vérifier si l'utilisateur a un rôle spécifique
 * @param string|array $roles Rôle(s) autorisé(s)
 * @return bool True si l'utilisateur a le rôle
 */
function hasRole($roles) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($user['role'], $roles);
}

/**
 * Vérifier si l'utilisateur est administrateur
 * @return bool True si admin
 */
function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

/**
 * Vérifier si l'utilisateur est membre du bureau
 * @return bool True si bureau ou admin
 */
function isBureau() {
    return hasRole([ROLE_BUREAU, ROLE_ADMIN]);
}

/**
 * Vérifier si l'utilisateur a un compte validé
 * @return bool True si validé
 */
function isValidated() {
    $user = getCurrentUser();
    return $user && $user['statut_compte'] === STATUT_VALIDE;
}

/**
 * Exiger une connexion, sinon rediriger vers login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Vous devez être connecté pour accéder à cette page.');
        redirect('login.php');
    }
}

/**
 * Exiger un compte validé
 */
function requireValidated() {
    requireLogin();
    if (!isValidated()) {
        setFlash('error', 'Votre compte n\'est pas encore validé.');
        redirect('index.php');
    }
}

/**
 * Exiger le rôle bureau ou admin
 */
function requireBureau() {
    requireValidated();
    if (!isBureau()) {
        setFlash('error', 'Vous n\'avez pas les droits pour accéder à cette page.');
        redirect('dashboard.php');
    }
}

/**
 * Exiger le rôle admin
 */
function requireAdmin() {
    requireValidated();
    if (!isAdmin()) {
        setFlash('error', 'Accès réservé aux administrateurs.');
        redirect('dashboard.php');
    }
}

// =====================================================
// FONCTIONS DE GESTION DES UTILISATEURS
// =====================================================

/**
 * Obtenir un utilisateur par son ID
 * @param int $id ID de l'utilisateur
 * @return array|false Données utilisateur
 */
function getUserById($id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    return dbFetchOne($sql, [$id]);
}

/**
 * Mettre à jour le statut d'un compte
 * @param int $userId ID de l'utilisateur
 * @param string $newStatut Nouveau statut
 * @param int $changedBy ID de l'utilisateur qui fait le changement
 * @return bool Succès
 */
function updateUserStatut($userId, $newStatut, $changedBy) {
    $user = getUserById($userId);
    if (!$user) return false;
    
    $oldStatut = $user['statut_compte'];
    
    $sql = "UPDATE users SET statut_compte = ? WHERE id = ?";
    dbExecute($sql, [$newStatut, $userId]);
    
    // Logger le changement
    logStatutChange('users', $userId, $oldStatut, $newStatut, $changedBy);
    
    // Notifier l'utilisateur
    $messages = [
        STATUT_VALIDE => 'Votre compte a été validé. Bienvenue au club !',
        STATUT_REFUSE => 'Votre demande d\'inscription a été refusée.',
        STATUT_DESACTIVE => 'Votre compte a été désactivé.'
    ];
    
    if (isset($messages[$newStatut])) {
        createNotification($userId, 'Statut de votre compte', $messages[$newStatut]);
    }
    
    return true;
}

/**
 * Mettre à jour le rôle d'un utilisateur
 * @param int $userId ID de l'utilisateur
 * @param string $newRole Nouveau rôle
 * @param int $changedBy ID de l'utilisateur qui fait le changement
 * @return bool Succès
 */
function updateUserRole($userId, $newRole, $changedBy) {
    $currentUser = getCurrentUser();
    $targetUser = getUserById($userId);
    
    if (!$targetUser) return false;
    
    // Un membre du bureau ne peut pas modifier les autres bureaux ou admins
    if ($currentUser['role'] === ROLE_BUREAU) {
        if (in_array($targetUser['role'], [ROLE_BUREAU, ROLE_ADMIN])) {
            return false;
        }
    }
    
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    dbExecute($sql, [$newRole, $userId]);
    
    createNotification($userId, 'Modification de votre rôle', 
        "Votre rôle a été modifié en : $newRole");
    
    return true;
}
