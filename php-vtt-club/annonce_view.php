<?php
/**
 * =====================================================
 * Détail d'une annonce
 * =====================================================
 */

require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Annonce non trouvée.');
    redirect('annonces.php');
}

// Récupérer l'annonce
$annonce = dbFetchOne("
    SELECT a.*, c.nom as categorie_nom, u.prenom, u.nom as auteur_nom, u.id as auteur_id
    FROM annonces a
    JOIN annonce_categories c ON a.categorie_id = c.id
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ?
", [$id]);

if (!$annonce) {
    setFlash('error', 'Annonce non trouvée.');
    redirect('annonces.php');
}

$user = getCurrentUser();

// Vérifier l'accès (publiée ou propre annonce ou bureau/admin)
if ($annonce['statut'] !== 'publiee' && (!$user || ($annonce['user_id'] != $user['id'] && !isBureau()))) {
    setFlash('error', 'Cette annonce n\'est pas accessible.');
    redirect('annonces.php');
}

// Récupérer les images
$images = dbFetchAll("SELECT * FROM annonce_images WHERE annonce_id = ?", [$id]);

$pageTitle = $annonce['titre'];
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header flex flex-between items-center">
        <span class="badge badge-info"><?php echo escape($annonce['categorie_nom']); ?></span>
        <span class="badge badge-<?php 
            echo $annonce['statut'] === 'publiee' ? 'success' : 
                ($annonce['statut'] === 'en_attente' ? 'warning' : 'secondary'); 
        ?>">
            <?php echo ucfirst($annonce['statut']); ?>
        </span>
    </div>
    
    <div class="card-body">
        <div class="grid grid-2" style="gap: 30px;">
            <div>
                <?php if (!empty($images)): ?>
                    <div class="annonce-gallery">
                        <img src="<?php echo UPLOAD_URL . escape($images[0]['image_path']); ?>" 
                             alt="<?php echo escape($annonce['titre']); ?>"
                             style="width: 100%; border-radius: var(--radius); margin-bottom: 10px;">
                        
                        <?php if (count($images) > 1): ?>
                            <div class="annonce-images">
                                <?php foreach ($images as $img): ?>
                                    <img src="<?php echo UPLOAD_URL . escape($img['image_path']); ?>" 
                                         alt="" style="cursor: pointer;"
                                         onclick="document.querySelector('.annonce-gallery > img').src = this.src">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 40px; background: #f5f5f5; border-radius: var(--radius);">
                        <div class="icon">📷</div>
                        <p>Aucune photo</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h1><?php echo escape($annonce['titre']); ?></h1>
                
                <?php if ($annonce['prix']): ?>
                    <div class="annonce-price" style="font-size: 2rem; margin: 15px 0;">
                        <?php echo formatPrix($annonce['prix']); ?>
                    </div>
                <?php endif; ?>
                
                <table class="table" style="margin-bottom: 20px;">
                    <tr>
                        <th>Catégorie</th>
                        <td><?php echo escape($annonce['categorie_nom']); ?></td>
                    </tr>
                    <tr>
                        <th>Publié par</th>
                        <td><?php echo escape($annonce['prenom'] . ' ' . $annonce['auteur_nom']); ?></td>
                    </tr>
                    <tr>
                        <th>Date de publication</th>
                        <td><?php echo formatDate($annonce['created_at']); ?></td>
                    </tr>
                </table>
                
                <?php if (!empty($annonce['description'])): ?>
                    <h3>Description</h3>
                    <p style="line-height: 1.8;"><?php echo nl2br(escape($annonce['description'])); ?></p>
                <?php endif; ?>
                
                <div class="mt-3">
                    <?php if ($user && $user['id'] != $annonce['auteur_id']): ?>
                        <a href="messages.php?to=<?php echo $annonce['auteur_id']; ?>&subject=Annonce: <?php echo urlencode($annonce['titre']); ?>" 
                           class="btn btn-primary">
                            ✉️ Contacter le vendeur
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($user && $annonce['user_id'] == $user['id']): ?>
                        <a href="annonce_edit.php?id=<?php echo $annonce['id']; ?>" class="btn btn-outline">Modifier</a>
                        <a href="annonce_delete.php?id=<?php echo $annonce['id']; ?>" class="btn btn-danger"
                           data-confirm="Voulez-vous vraiment supprimer cette annonce ?">Supprimer</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-footer">
        <a href="annonces.php" class="btn btn-outline">← Retour aux annonces</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
