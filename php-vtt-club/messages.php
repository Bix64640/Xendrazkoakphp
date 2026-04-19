<?php
/**
 * =====================================================
 * Messagerie interne
 * =====================================================
 */

$pageTitle = 'Messages';
require_once __DIR__ . '/auth.php';

requireValidated();

$user = getCurrentUser();
$error = '';
$success = '';

// Destinataires possibles (bureau et admin)
$destinataires = dbFetchAll("
    SELECT id, nom, prenom, role 
    FROM users 
    WHERE role IN ('bureau', 'admin') AND statut_compte = 'valide' AND id != ?
    ORDER BY role DESC, nom, prenom
", [$user['id']]);

// Pré-remplissage
$to = (int)($_GET['to'] ?? 0);
$subject = $_GET['subject'] ?? '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (!$receiver_id) {
            $error = 'Veuillez sélectionner un destinataire.';
        } elseif (empty($sujet)) {
            $error = 'Le sujet est obligatoire.';
        } elseif (empty($message)) {
            $error = 'Le message est obligatoire.';
        } else {
            // Vérifier que le destinataire existe
            $receiver = dbFetchOne("SELECT * FROM users WHERE id = ? AND role IN ('bureau', 'admin')", [$receiver_id]);
            
            if (!$receiver) {
                $error = 'Destinataire invalide.';
            } else {
                $sql = "INSERT INTO messages (sender_id, receiver_id, sujet, message) VALUES (?, ?, ?, ?)";
                dbExecute($sql, [$user['id'], $receiver_id, $sujet, $message]);
                
                // Notifier le destinataire
                createNotification($receiver_id, 'Nouveau message', 
                    "Vous avez reçu un message de {$user['prenom']} {$user['nom']} : \"$sujet\"");
                
                $success = 'Votre message a été envoyé.';
                $to = 0;
                $subject = '';
                $_POST = [];
            }
        }
    }
}

// Récupérer les messages reçus et envoyés
$messagesRecus = dbFetchAll("
    SELECT m.*, u.nom, u.prenom
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
", [$user['id']]);

$messagesEnvoyes = dbFetchAll("
    SELECT m.*, u.nom, u.prenom
    FROM messages m
    JOIN users u ON m.receiver_id = u.id
    WHERE m.sender_id = ?
    ORDER BY m.created_at DESC
", [$user['id']]);

// Marquer comme lu
if (isset($_GET['read'])) {
    $msgId = (int)$_GET['read'];
    dbExecute("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", [$msgId, $user['id']]);
    redirect('messages.php');
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Messagerie</h1>
    <p>Contactez les membres du bureau ou les administrateurs</p>
</div>

<div class="grid grid-2" style="gap: 30px;">
    <!-- Formulaire d'envoi -->
    <div class="card">
        <div class="card-header">
            <h3>Envoyer un message</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php csrfField(); ?>
                
                <div class="form-group">
                    <label for="receiver_id">Destinataire <span class="required">*</span></label>
                    <select name="receiver_id" id="receiver_id" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($destinataires as $dest): ?>
                            <option value="<?php echo $dest['id']; ?>" 
                                    <?php echo $to == $dest['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($dest['prenom'] . ' ' . $dest['nom']); ?> 
                                (<?php echo $dest['role'] === 'admin' ? 'Admin' : 'Bureau'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sujet">Sujet <span class="required">*</span></label>
                    <input type="text" id="sujet" name="sujet" class="form-control" 
                           value="<?php echo escape($_POST['sujet'] ?? $subject); ?>" 
                           maxlength="255" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message <span class="required">*</span></label>
                    <textarea id="message" name="message" class="form-control" rows="5" 
                              required><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Envoyer</button>
            </form>
        </div>
    </div>
    
    <!-- Messages reçus -->
    <div class="card">
        <div class="card-header">
            <h3>Messages reçus (<?php echo count($messagesRecus); ?>)</h3>
        </div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($messagesRecus)): ?>
                <p class="text-muted text-center">Aucun message reçu.</p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($messagesRecus as $msg): ?>
                        <li class="notification-item <?php echo !$msg['is_read'] ? 'unread' : ''; ?>">
                            <div class="notification-content">
                                <div class="notification-title">
                                    <?php if (!$msg['is_read']): ?>
                                        <a href="?read=<?php echo $msg['id']; ?>" title="Marquer comme lu">●</a>
                                    <?php endif; ?>
                                    <?php echo escape($msg['sujet']); ?>
                                </div>
                                <p style="margin: 5px 0; font-size: 0.9rem;">
                                    De: <?php echo escape($msg['prenom'] . ' ' . $msg['nom']); ?>
                                </p>
                                <p style="margin: 5px 0;"><?php echo escape(truncate($msg['message'], 100)); ?></p>
                                <div class="notification-time">
                                    <?php echo formatDate($msg['created_at'], true); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isBureau()): ?>
<!-- Messages envoyés pour bureau/admin -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Messages envoyés (<?php echo count($messagesEnvoyes); ?>)</h3>
    </div>
    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
        <?php if (empty($messagesEnvoyes)): ?>
            <p class="text-muted text-center">Aucun message envoyé.</p>
        <?php else: ?>
            <ul class="notification-list">
                <?php foreach ($messagesEnvoyes as $msg): ?>
                    <li class="notification-item">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo escape($msg['sujet']); ?></div>
                            <p style="margin: 5px 0; font-size: 0.9rem;">
                                À: <?php echo escape($msg['prenom'] . ' ' . $msg['nom']); ?>
                            </p>
                            <p style="margin: 5px 0;"><?php echo escape(truncate($msg['message'], 100)); ?></p>
                            <div class="notification-time">
                                <?php echo formatDate($msg['created_at'], true); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
