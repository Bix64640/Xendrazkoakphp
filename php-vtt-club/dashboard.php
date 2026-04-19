<?php
/**
 * =====================================================
 * Tableau de bord utilisateur
 * =====================================================
 */

$pageTitle = 'Mon espace';
require_once __DIR__ . '/auth.php';

// Vérifier la connexion et le compte validé
requireValidated();

$user = getCurrentUser();

// Récupérer les statistiques de l'utilisateur
$nbInscriptionsSorties = dbFetchOne("
    SELECT COUNT(*) as count FROM sortie_inscriptions 
    WHERE user_id = ? AND statut = 'inscrit'
", [$user['id']])['count'];

$nbAnnonces = dbFetchOne("
    SELECT COUNT(*) as count FROM annonces 
    WHERE user_id = ?
", [$user['id']])['count'];

$nbNotificationsNonLues = countUnreadNotifications($user['id']);

// Récupérer les prochaines sorties de l'utilisateur
$mesProchaineSorties = dbFetchAll("
    SELECT s.*, si.statut as inscription_statut
    FROM sorties s
    JOIN sortie_inscriptions si ON s.id = si.sortie_id
    WHERE si.user_id = ? AND si.statut = 'inscrit' AND s.date_sortie >= CURDATE()
    ORDER BY s.date_sortie ASC
    LIMIT 5
", [$user['id']]);

// Récupérer les annonces de l'utilisateur
$mesAnnonces = dbFetchAll("
    SELECT a.*, c.nom as categorie_nom
    FROM annonces a
    JOIN annonce_categories c ON a.categorie_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
", [$user['id']]);

// Récupérer les dernières notifications
$notifications = dbFetchAll("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user['id']]);

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Bienvenue, <?php echo escape($user['prenom']); ?> !</h1>
    <p>Gérez vos inscriptions aux sorties et vos annonces depuis votre espace personnel.</p>
</div>

<!-- Statistiques -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-value"><?php echo $nbInscriptionsSorties; ?></div>
        <div class="stat-label">Inscription<?php echo $nbInscriptionsSorties > 1 ? 's' : ''; ?> aux sorties</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $nbAnnonces; ?></div>
        <div class="stat-label">Annonce<?php echo $nbAnnonces > 1 ? 's' : ''; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $nbNotificationsNonLues; ?></div>
        <div class="stat-label">Notification<?php echo $nbNotificationsNonLues > 1 ? 's' : ''; ?> non lue<?php echo $nbNotificationsNonLues > 1 ? 's' : ''; ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Mes prochaines sorties -->
    <div class="card">
        <div class="card-header">
            <h3>Mes prochaines sorties</h3>
        </div>
        <div class="card-body">
            <?php if (empty($mesProchaineSorties)): ?>
                <div class="empty-state" style="padding: 20px;">
                    <p>Vous n'êtes inscrit à aucune sortie à venir.</p>
                    <a href="sorties.php" class="btn btn-sm btn-primary mt-1">Voir les sorties</a>
                </div>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($mesProchaineSorties as $sortie): ?>
                        <li class="notification-item">
                            <div class="notification-content">
                                <div class="notification-title">
                                    <a href="sortie_view.php?id=<?php echo $sortie['id']; ?>">
                                        <?php echo escape($sortie['titre']); ?>
                                    </a>
                                </div>
                                <div class="notification-time">
                                    📅 <?php echo formatDate($sortie['date_sortie']); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="sorties.php">Voir toutes les sorties →</a>
        </div>
    </div>
    
    <!-- Mes annonces -->
    <div class="card">
        <div class="card-header flex flex-between items-center">
            <h3>Mes annonces</h3>
            <a href="annonce_add.php" class="btn btn-sm btn-primary">+ Nouvelle</a>
        </div>
        <div class="card-body">
            <?php if (empty($mesAnnonces)): ?>
                <div class="empty-state" style="padding: 20px;">
                    <p>Vous n'avez pas encore créé d'annonce.</p>
                </div>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($mesAnnonces as $annonce): ?>
                        <li class="notification-item">
                            <div class="notification-content">
                                <div class="notification-title">
                                    <a href="annonce_view.php?id=<?php echo $annonce['id']; ?>">
                                        <?php echo escape($annonce['titre']); ?>
                                    </a>
                                    <span class="badge badge-<?php echo $annonce['statut'] === 'publiee' ? 'success' : 'warning'; ?>">
                                        <?php echo escape($annonce['statut']); ?>
                                    </span>
                                </div>
                                <div class="notification-time">
                                    <?php echo escape($annonce['categorie_nom']); ?> 
                                    <?php if ($annonce['prix']): ?>
                                        - <?php echo formatPrix($annonce['prix']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="annonces.php?mes_annonces=1">Voir toutes mes annonces →</a>
        </div>
    </div>
    
    <!-- Notifications récentes -->
    <div class="card">
        <div class="card-header">
            <h3>Notifications récentes</h3>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="empty-state" style="padding: 20px;">
                    <p>Aucune notification.</p>
                </div>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($notifications as $notif): ?>
                        <li class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                            <div class="notification-content">
                                <div class="notification-title"><?php echo escape($notif['titre']); ?></div>
                                <div class="notification-time"><?php echo formatDate($notif['created_at'], true); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="notifications.php">Voir toutes les notifications →</a>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="card">
        <div class="card-header">
            <h3>Actions rapides</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="sorties.php" class="btn btn-outline">📅 Voir les sorties</a>
                <a href="annonce_add.php" class="btn btn-outline">📢 Créer une annonce</a>
                <a href="annonces.php" class="btn btn-outline">🏷️ Parcourir les annonces</a>
                <a href="messages.php" class="btn btn-outline">✉️ Contacter le bureau</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
