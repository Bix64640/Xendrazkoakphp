<?php
/**
 * =====================================================
 * Liste des actualités
 * =====================================================
 */

$pageTitle = 'Actualités';
require_once __DIR__ . '/header.php';

// Pagination
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Compter le total
$total = dbFetchOne("SELECT COUNT(*) as count FROM actualites WHERE statut = 'publie'")['count'];
$totalPages = ceil($total / $perPage);

// Récupérer les actualités
$actualites = dbFetchAll("
    SELECT a.*, u.prenom, u.nom as auteur_nom 
    FROM actualites a 
    JOIN users u ON a.created_by = u.id 
    WHERE a.statut = 'publie' 
    ORDER BY a.created_at DESC 
    LIMIT ? OFFSET ?
", [$perPage, $offset]);
?>

<div class="page-header">
    <h1>Actualités du club</h1>
    <p>Retrouvez toutes les nouvelles et événements du <?php echo SITE_NAME; ?></p>
</div>

<?php if (empty($actualites)): ?>
    <div class="empty-state">
        <div class="icon">📰</div>
        <h3>Aucune actualité pour le moment</h3>
        <p>Les actualités du club seront publiées ici. Revenez bientôt !</p>
    </div>
<?php else: ?>
    <div class="grid grid-auto">
        <?php foreach ($actualites as $actu): ?>
            <article class="card news-card">
                <?php if (!empty($actu['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . escape($actu['image']); ?>" alt="" class="card-img">
                <?php endif; ?>
                <div class="card-body">
                    <div class="news-date">
                        <?php echo formatDate($actu['created_at']); ?> 
                        par <?php echo escape($actu['prenom'] . ' ' . $actu['auteur_nom']); ?>
                    </div>
                    <h3><?php echo escape($actu['titre']); ?></h3>
                    <p><?php echo escape(truncate($actu['contenu'], 200)); ?></p>
                </div>
                <div class="card-footer">
                    <a href="actualite_view.php?id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-primary">Lire la suite</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">← Précédent</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Suivant →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
