<?php
/**
 * =====================================================
 * Tableau de bord - Administrateur
 * =====================================================
 */

$pageTitle = 'Administration';
require_once __DIR__ . '/auth.php';

requireAdmin();

$user = getCurrentUser();
$clubInfo = getClubInfo();

// Statistiques complètes
$stats = [
    'total_users' => dbFetchOne("SELECT COUNT(*) as c FROM users")['c'],
    'users_valides' => dbFetchOne("SELECT COUNT(*) as c FROM users WHERE statut_compte = 'valide'")['c'],
    'users_en_attente' => dbFetchOne("SELECT COUNT(*) as c FROM users WHERE statut_compte = 'en_attente'")['c'],
    'users_bureau' => dbFetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'bureau'")['c'],
    'users_admin' => dbFetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'admin'")['c'],
    'total_sorties' => dbFetchOne("SELECT COUNT(*) as c FROM sorties")['c'],
    'sorties_ouvertes' => dbFetchOne("SELECT COUNT(*) as c FROM sorties WHERE statut = 'ouverte' AND date_sortie >= CURDATE()")['c'],
    'total_annonces' => dbFetchOne("SELECT COUNT(*) as c FROM annonces")['c'],
    'annonces_publiees' => dbFetchOne("SELECT COUNT(*) as c FROM annonces WHERE statut = 'publiee'")['c'],
    'total_actualites' => dbFetchOne("SELECT COUNT(*) as c FROM actualites")['c'],
    'total_inscriptions' => dbFetchOne("SELECT COUNT(*) as c FROM sortie_inscriptions WHERE statut = 'inscrit'")['c'],
];

// Traitement du formulaire YouTube
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['youtube_url'])) {
        $youtubeUrl = trim($_POST['youtube_url']);
        // Convertir URL YouTube standard en embed
        if (strpos($youtubeUrl, 'watch?v=') !== false) {
            $youtubeUrl = str_replace('watch?v=', 'embed/', $youtubeUrl);
            $youtubeUrl = preg_replace('/&.*/', '', $youtubeUrl);
        }
        updateYoutubeUrl($youtubeUrl);
        setFlash('success', 'L\'URL YouTube a été mise à jour.');
        redirect('admin_dashboard.php');
    }
}

// Derniers logs
$recentLogs = dbFetchAll("
    SELECT l.*, u.prenom, u.nom
    FROM logs_statuts l
    JOIN users u ON l.changed_by = u.id
    ORDER BY l.created_at DESC
    LIMIT 10
");

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Administration</h1>
    <p>Panneau d'administration complet</p>
</div>

<!-- Statistiques globales -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
        <div class="stat-label">Utilisateurs totaux</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['users_valides']; ?></div>
        <div class="stat-label">Membres actifs</div>
    </div>
    <div class="stat-card">
        <div class="stat-value <?php echo $stats['users_en_attente'] > 0 ? 'text-warning' : ''; ?>">
            <?php echo $stats['users_en_attente']; ?>
        </div>
        <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['sorties_ouvertes']; ?></div>
        <div class="stat-label">Sorties ouvertes</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_inscriptions']; ?></div>
        <div class="stat-label">Inscriptions sorties</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['annonces_publiees']; ?></div>
        <div class="stat-label">Annonces publiées</div>
    </div>
</div>

<!-- Actions d'administration -->
<div class="card mb-3">
    <div class="card-header">
        <h3>Actions d'administration</h3>
    </div>
    <div class="card-body">
        <div class="flex gap-1" style="flex-wrap: wrap;">
            <a href="manage_users.php" class="btn btn-primary">👥 Gérer les utilisateurs</a>
            <a href="manage_sorties.php" class="btn btn-outline">📅 Gérer les sorties</a>
            <a href="manage_actualites.php" class="btn btn-outline">📰 Gérer les actualités</a>
            <a href="manage_annonces.php" class="btn btn-outline">🏷️ Gérer les annonces</a>
            <a href="manage_categories.php" class="btn btn-outline">📁 Gérer les catégories</a>
            <a href="export_membres.php" class="btn btn-outline">📥 Exporter les membres</a>
        </div>
    </div>
</div>

<div class="grid grid-2" style="gap: 20px;">
    <!-- Configuration YouTube -->
    <div class="card">
        <div class="card-header">
            <h3>Vidéo YouTube de la page d'accueil</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php csrfField(); ?>
                
                <div class="form-group">
                    <label for="youtube_url">URL de la vidéo (embed)</label>
                    <input type="url" id="youtube_url" name="youtube_url" class="form-control" 
                           value="<?php echo escape($clubInfo['youtube_url'] ?? ''); ?>"
                           placeholder="https://www.youtube.com/embed/...">
                    <p class="form-text">Collez l'URL de la vidéo YouTube. Le format sera automatiquement converti en embed.</p>
                </div>
                
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </form>
            
            <?php if (!empty($clubInfo['youtube_url'])): ?>
                <div class="mt-2">
                    <p><strong>Aperçu :</strong></p>
                    <div class="video-container" style="max-width: 400px;">
                        <iframe src="<?php echo escape($clubInfo['youtube_url']); ?>" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Répartition des rôles -->
    <div class="card">
        <div class="card-header">
            <h3>Répartition des utilisateurs</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <th>Rôle</th>
                    <th>Nombre</th>
                </tr>
                <tr>
                    <td>Administrateurs</td>
                    <td><strong><?php echo $stats['users_admin']; ?></strong></td>
                </tr>
                <tr>
                    <td>Membres du bureau</td>
                    <td><strong><?php echo $stats['users_bureau']; ?></strong></td>
                </tr>
                <tr>
                    <td>Utilisateurs standard</td>
                    <td><strong><?php echo $stats['users_valides'] - $stats['users_admin'] - $stats['users_bureau']; ?></strong></td>
                </tr>
                <tr>
                    <td>En attente de validation</td>
                    <td><strong class="text-warning"><?php echo $stats['users_en_attente']; ?></strong></td>
                </tr>
            </table>
            
            <div class="mt-2">
                <a href="manage_users.php" class="btn btn-outline btn-sm">Gérer les utilisateurs →</a>
            </div>
        </div>
    </div>
</div>

<!-- Historique des modifications -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Historique des modifications de statut</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recentLogs)): ?>
            <p class="text-muted text-center">Aucun historique.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Table</th>
                            <th>Élément</th>
                            <th>Ancien statut</th>
                            <th>Nouveau statut</th>
                            <th>Par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?php echo formatDate($log['created_at'], true); ?></td>
                                <td><?php echo escape($log['table_cible']); ?></td>
                                <td>#<?php echo $log['element_id']; ?></td>
                                <td><?php echo escape($log['ancien_statut'] ?? '-'); ?></td>
                                <td><?php echo escape($log['nouveau_statut']); ?></td>
                                <td><?php echo escape($log['prenom'] . ' ' . $log['nom']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
