<?php
/**
 * =====================================================
 * Détail d'une actualité
 * =====================================================
 */

require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Actualité non trouvée.');
    redirect('actualites.php');
}

// Récupérer l'actualité
$actualite = dbFetchOne("
    SELECT a.*, u.prenom, u.nom as auteur_nom 
    FROM actualites a 
    JOIN users u ON a.created_by = u.id 
    WHERE a.id = ? AND a.statut = 'publie'
", [$id]);

if (!$actualite) {
    setFlash('error', 'Actualité non trouvée.');
    redirect('actualites.php');
}

$pageTitle = $actualite['titre'];
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <?php if (!empty($actualite['image'])): ?>
        <img src="<?php echo UPLOAD_URL . escape($actualite['image']); ?>" alt="" class="card-img" style="height: 400px;">
    <?php endif; ?>
    
    <div class="card-body">
        <div class="news-date mb-2">
            Publié le <?php echo formatDate($actualite['created_at']); ?> 
            par <?php echo escape($actualite['prenom'] . ' ' . $actualite['auteur_nom']); ?>
        </div>
        
        <h1><?php echo escape($actualite['titre']); ?></h1>
        
        <div class="mt-2" style="line-height: 1.8;">
            <?php echo nl2br(escape($actualite['contenu'])); ?>
        </div>
    </div>
    
    <div class="card-footer">
        <a href="actualites.php" class="btn btn-outline">← Retour aux actualités</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
