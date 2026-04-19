<?php
/**
 * =====================================================
 * Tableau de bord - Membre du bureau
 * =====================================================
 */

$pageTitle = 'Espace Bureau';
require_once __DIR__ . '/auth.php';

requireBureau();

$user = getCurrentUser();

// Statistiques
$stats = [
    'inscriptions_en_attente' => dbFetchOne("SELECT COUNT(*) as c FROM users WHERE statut_compte = 'en_attente'")['c'],
    'membres_valides' => dbFetchOne("SELECT COUNT(*) as c FROM users WHERE statut_compte = 'valide'")['c'],
    'sorties_ouvertes' => dbFetchOne("SELECT COUNT(*) as c FROM sorties WHERE statut = 'ouverte' AND date_sortie >= CURDATE()")['c'],
    'annonces_publiees' => dbFetchOne("SELECT COUNT(*) as c FROM annonces WHERE statut = 'publiee'")['c'],
    'actualites_publiees' => dbFetchOne("SELECT COUNT(*) as c FROM actualites WHERE statut = 'publie'")['c'],
];

// Inscriptions en attente
$inscriptionsEnAttente = dbFetchAll("
    SELECT * FROM users 
    WHERE statut_compte = 'en_attente' 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Prochaines sorties
$prochainesSorties = dbFetchAll("
    SELECT s.*, 
           (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits
    FROM sorties s
    WHERE s.date_sortie >= CURDATE()
    ORDER BY s.date_sortie ASC
    LIMIT 5
");

// Messages non lus
$messagesNonLus = dbFetchAll("
    SELECT m.*, u.nom, u.prenom
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? AND m.is_read = 0
    ORDER BY m.created_at DESC
    LIMIT 5
", [$user['id']]);

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Espace Bureau</h1>
    <p>Gérez les membres, les sorties et les annonces du club</p>
</div>

<!-- Statistiques -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-value <?php echo $stats['inscriptions_en_attente'] > 0 ? 'text-warning' : ''; ?>">
            <?php echo $stats['inscriptions_en_attente']; ?>
        </div>
        <div class="stat-label">Inscription<?php echo $stats['inscriptions_en_attente'] > 1 ? 's' : ''; ?> en attente</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['membres_valides']; ?></div>
        <div class="stat-label">Membres validés</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['sorties_ouvertes']; ?></div>
        <div class="stat-label">Sorties à venir</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['annonces_publiees']; ?></div>
        <div class="stat-label">Annonces publiées</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['actualites_publiees']; ?></div>
        <div class="stat-label">Actualités</div>
    </div>
</div>

<!-- Actions rapides -->
<div class="card mb-3">
    <div class="card-header">
        <h3>Actions rapides</h3>
    </div>
    <div class="card-body">
        <div class="flex gap-1" style="flex-wrap: wrap;">
            <a href="manage_users.php?statut=en_attente" class="btn btn-primary">
                👥 Valider les inscriptions (<?php echo $stats['inscriptions_en_attente']; ?>)
            </a>
            <a href="manage_sorties.php?action=add" class="btn btn-outline">📅 Ajouter une sortie</a>
            <a href="manage_actualites.php?action=add" class="btn btn-outline">📰 Ajouter une actualité</a>
            <a href="manage_users.php" class="btn btn-outline">👤 Gérer les membres</a>
            <a href="manage_annonces.php" class="btn btn-outline">🏷️ Gérer les annonces</a>
            <a href="export_membres.php" class="btn btn-outline">📥 Exporter les membres</a>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Inscriptions en attente -->
    <div class="card">
        <div class="card-header flex flex-between items-center">
            <h3>Inscriptions en attente</h3>
            <a href="manage_users.php?statut=en_attente" class="btn btn-sm btn-outline">Voir tout</a>
        </div>
        <div class="card-body">
            <?php if (empty($inscriptionsEnAttente)): ?>
                <p class="text-muted text-center">Aucune inscription en attente.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscriptionsEnAttente as $insc): ?>
                                <tr>
                                    <td><?php echo escape($insc['prenom'] . ' ' . $insc['nom']); ?></td>
                                    <td><?php echo escape($insc['email']); ?></td>
                                    <td><?php echo formatDate($insc['created_at']); ?></td>
                                    <td class="actions">
                                        <a href="manage_users.php?action=validate&id=<?php echo $insc['id']; ?>" 
                                           class="btn btn-sm btn-primary">Valider</a>
                                        <a href="manage_users.php?action=refuse&id=<?php echo $insc['id']; ?>" 
                                           class="btn btn-sm btn-danger">Refuser</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Prochaines sorties -->
    <div class="card">
        <div class="card-header flex flex-between items-center">
            <h3>Prochaines sorties</h3>
            <a href="manage_sorties.php" class="btn btn-sm btn-outline">Gérer</a>
        </div>
        <div class="card-body">
            <?php if (empty($prochainesSorties)): ?>
                <p class="text-muted text-center">Aucune sortie prévue.</p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($prochainesSorties as $sortie): ?>
                        <li class="notification-item">
                            <div class="notification-content">
                                <div class="notification-title">
                                    <?php echo escape($sortie['titre']); ?>
                                    <span class="badge badge-<?php echo $sortie['statut'] === 'ouverte' ? 'success' : 'info'; ?>">
                                        <?php echo $sortie['statut']; ?>
                                    </span>
                                </div>
                                <div class="notification-time">
                                    📅 <?php echo formatDate($sortie['date_sortie']); ?> - 
                                    <?php echo $sortie['nb_inscrits']; ?>/<?php echo $sortie['nombre_places']; ?> inscrits
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Messages non lus -->
    <div class="card">
        <div class="card-header flex flex-between items-center">
            <h3>Messages non lus</h3>
            <a href="messages.php" class="btn btn-sm btn-outline">Voir tout</a>
        </div>
        <div class="card-body">
            <?php if (empty($messagesNonLus)): ?>
                <p class="text-muted text-center">Aucun nouveau message.</p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($messagesNonLus as $msg): ?>
                        <li class="notification-item unread">
                            <div class="notification-content">
                                <div class="notification-title"><?php echo escape($msg['sujet']); ?></div>
                                <p style="margin: 5px 0; font-size: 0.9rem;">
                                    De: <?php echo escape($msg['prenom'] . ' ' . $msg['nom']); ?>
                                </p>
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

<?php require_once __DIR__ . '/footer.php'; ?>
