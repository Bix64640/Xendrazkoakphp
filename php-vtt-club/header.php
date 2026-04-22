<?php
/**
 * =====================================================
 * Header du site - Inclus en haut de chaque page
 * =====================================================
 */

require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$unreadNotifications = $currentUser ? countUnreadNotifications($currentUser['id']) : 0;
$clubInfo = getClubInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <?php
                    // If a PNG logo is uploaded, display only the PNG (no text). Otherwise show site name.
                    $customLogoPath = __DIR__ . '/uploads/logo-xendrazkoak.png';
                    if (file_exists($customLogoPath)): ?>
                        <img src="<?php echo UPLOAD_URL . 'logo-xendrazkoak.png'; ?>" alt="<?php echo escape(SITE_NAME); ?>" class="logo-img">
                    <?php else: ?>
                        <span class="logo-text"><?php echo escape(SITE_NAME); ?></span>
                    <?php endif; ?>
                </a>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Accueil</a></li>
                        <li><a href="actualites.php" <?php echo basename($_SERVER['PHP_SELF']) == 'actualites.php' ? 'class="active"' : ''; ?>>Actualités</a></li>
                        <li><a href="sorties.php" <?php echo basename($_SERVER['PHP_SELF']) == 'sorties.php' ? 'class="active"' : ''; ?>>Sorties</a></li>
                        <li><a href="annonces.php" <?php echo basename($_SERVER['PHP_SELF']) == 'annonces.php' ? 'class="active"' : ''; ?>>Annonces</a></li>
                        
                        <?php if (isLoggedIn() && isValidated()): ?>
                            <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>Mon espace</a></li>
                            
                            <?php if (isBureau()): ?>
                                <li><a href="bureau_dashboard.php" <?php echo strpos(basename($_SERVER['PHP_SELF']), 'bureau_') === 0 ? 'class="active"' : ''; ?>>Bureau</a></li>
                            <?php endif; ?>
                            
                            <?php if (isAdmin()): ?>
                                <li><a href="admin_dashboard.php" <?php echo strpos(basename($_SERVER['PHP_SELF']), 'admin_') === 0 || strpos(basename($_SERVER['PHP_SELF']), 'manage_') === 0 ? 'class="active"' : ''; ?>>Admin</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="notifications.php" class="notification-icon" title="Notifications">
                            🔔
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge"><?php echo $unreadNotifications; ?></span>
                            <?php endif; ?>
                        </a>
                        <span class="user-name"><?php echo escape($currentUser['prenom']); ?></span>
                        <a href="logout.php" class="btn btn-sm btn-outline">Déconnexion</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-sm btn-secondary">Connexion</a>
                        <a href="signup.php" class="btn btn-sm btn-primary">S'inscrire</a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-toggle" aria-label="Menu">☰</button>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <?php displayFlash(); ?>
