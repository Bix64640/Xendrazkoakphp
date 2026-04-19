<?php
/**
 * =====================================================
 * Centre de notifications
 * =====================================================
 */

$pageTitle = 'Notifications';
require_once __DIR__ . '/auth.php';

requireValidated();

$user = getCurrentUser();

// Marquer comme lues
if (isset($_GET['mark_read'])) {
    $notifId = (int)$_GET['mark_read'];
    if ($notifId === 0) {
        // Marquer toutes comme lues
        dbExecute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$user['id']]);
        setFlash('success', 'Toutes les notifications ont été marquées comme lues.');
    } else {
        dbExecute("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notifId, $user['id']]);
    }
    redirect('notifications.php');
}

// Supprimer une notification
if (isset($_GET['delete'])) {
    $notifId = (int)$_GET['delete'];
    dbExecute("DELETE FROM notifications WHERE id = ? AND user_id = ?", [$notifId, $user['id']]);
    setFlash('success', 'Notification supprimée.');
    redirect('notifications.php');
}

// Récupérer les notifications
$notifications = dbFetchAll("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
", [$user['id']]);

$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1>Notifications</h1>
        <p><?php echo count($notifications); ?> notification<?php echo count($notifications) > 1 ? 's' : ''; ?>
           (<?php echo $unreadCount; ?> non lue<?php echo $unreadCount > 1 ? 's' : ''; ?>)</p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <a href="?mark_read=0" class="btn btn-outline">Tout marquer comme lu</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($notifications)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="icon">🔔</div>
                <h3>Aucune notification</h3>
                <p>Vous n'avez pas encore de notification.</p>
            </div>
        </div>
    <?php else: ?>
        <ul class="notification-list">
            <?php foreach ($notifications as $notif): ?>
                <li class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                    <div class="notification-content">
                        <div class="notification-title">
                            <?php if (!$notif['is_read']): ?>
                                <span style="color: var(--primary-color);">●</span>
                            <?php endif; ?>
                            <?php echo escape($notif['titre']); ?>
                        </div>
                        <p style="margin: 5px 0;"><?php echo escape($notif['message']); ?></p>
                        <div class="notification-time">
                            <?php echo formatDate($notif['created_at'], true); ?>
                        </div>
                    </div>
                    <div class="notification-actions" style="display: flex; gap: 10px;">
                        <?php if (!$notif['is_read']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline">Marquer lu</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $notif['id']; ?>" class="btn btn-sm btn-danger"
                           data-confirm="Supprimer cette notification ?">×</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
