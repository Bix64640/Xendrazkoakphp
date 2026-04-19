<?php
/**
 * =====================================================
 * Liste des annonces
 * =====================================================
 */

$pageTitle = 'Annonces';
require_once __DIR__ . '/header.php';

$user = getCurrentUser();

// Filtres
$categorie = $_GET['categorie'] ?? '';
$search = $_GET['search'] ?? '';
$mesAnnonces = isset($_GET['mes_annonces']) && $user;

// Récupérer les catégories
$categories = dbFetchAll("SELECT * FROM annonce_categories ORDER BY nom");

// Construire la requête
$sql = "
    SELECT a.*, c.nom as categorie_nom, u.prenom, u.nom as auteur_nom,
           (SELECT GROUP_CONCAT(image_path) FROM annonce_images WHERE annonce_id = a.id) as images
    FROM annonces a
    JOIN annonce_categories c ON a.categorie_id = c.id
    JOIN users u ON a.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($mesAnnonces) {
    $sql .= " AND a.user_id = ?";
    $params[] = $user['id'];
} else {
    $sql .= " AND a.statut = 'publiee'";
}

if ($categorie) {
    $sql .= " AND a.categorie_id = ?";
    $params[] = $categorie;
}

if ($search) {
    $sql .= " AND (a.titre LIKE ? OR a.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.created_at DESC";

$annonces = dbFetchAll($sql, $params);
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1><?php echo $mesAnnonces ? 'Mes annonces' : 'Annonces'; ?></h1>
        <p>Vente, prêt et recherche de matériel entre membres</p>
    </div>
    <?php if ($user && isValidated()): ?>
        <a href="annonce_add.php" class="btn btn-primary">+ Déposer une annonce</a>
    <?php endif; ?>
</div>

<!-- Filtres -->
<div class="filters">
    <form method="GET" action="">
        <?php if ($mesAnnonces): ?>
            <input type="hidden" name="mes_annonces" value="1">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="search">Recherche</label>
            <input type="text" id="search" name="search" class="form-control" 
                   placeholder="Mot-clé..." value="<?php echo escape($search); ?>">
        </div>
        
        <div class="form-group">
            <label for="categorie">Catégorie</label>
            <select name="categorie" id="categorie" class="form-control">
                <option value="">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categorie == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($cat['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="annonces.php<?php echo $mesAnnonces ? '?mes_annonces=1' : ''; ?>" class="btn btn-outline">Réinitialiser</a>
        </div>
        
        <?php if ($user): ?>
        <div class="form-group">
            <label>&nbsp;</label>
            <?php if ($mesAnnonces): ?>
                <a href="annonces.php" class="btn btn-outline">Toutes les annonces</a>
            <?php else: ?>
                <a href="annonces.php?mes_annonces=1" class="btn btn-outline">Mes annonces</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($annonces)): ?>
    <div class="empty-state">
        <div class="icon">🏷️</div>
        <h3>Aucune annonce</h3>
        <p><?php echo $mesAnnonces ? 'Vous n\'avez pas encore créé d\'annonce.' : 'Aucune annonce ne correspond à vos critères.'; ?></p>
        <?php if ($user && isValidated()): ?>
            <a href="annonce_add.php" class="btn btn-primary mt-2">Déposer une annonce</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid grid-auto">
        <?php foreach ($annonces as $annonce): ?>
            <?php $imagesList = $annonce['images'] ? explode(',', $annonce['images']) : []; ?>
            <article class="card annonce-card">
                <?php if (!empty($imagesList[0])): ?>
                    <img src="<?php echo UPLOAD_URL . escape($imagesList[0]); ?>" alt="" class="card-img">
                <?php endif; ?>
                
                <div class="card-body">
                    <span class="badge badge-info annonce-category"><?php echo escape($annonce['categorie_nom']); ?></span>
                    
                    <?php if ($mesAnnonces): ?>
                        <span class="badge badge-<?php 
                            echo $annonce['statut'] === 'publiee' ? 'success' : 
                                ($annonce['statut'] === 'en_attente' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo ucfirst($annonce['statut']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <h3><?php echo escape($annonce['titre']); ?></h3>
                    
                    <?php if ($annonce['prix']): ?>
                        <div class="annonce-price"><?php echo formatPrix($annonce['prix']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($annonce['description'])): ?>
                        <p class="text-muted"><?php echo escape(truncate($annonce['description'], 100)); ?></p>
                    <?php endif; ?>
                    
                    <p class="text-muted" style="font-size: 0.875rem; margin-top: 10px;">
                        Par <?php echo escape($annonce['prenom']); ?> - <?php echo formatDate($annonce['created_at']); ?>
                    </p>
                </div>
                
                <div class="card-footer flex flex-between items-center">
                    <a href="annonce_view.php?id=<?php echo $annonce['id']; ?>" class="btn btn-sm btn-outline">Voir détails</a>
                    
                    <?php if ($user && $annonce['user_id'] == $user['id']): ?>
                        <div>
                            <a href="annonce_edit.php?id=<?php echo $annonce['id']; ?>" class="btn btn-sm btn-outline">Modifier</a>
                            <a href="annonce_delete.php?id=<?php echo $annonce['id']; ?>" class="btn btn-sm btn-danger"
                               data-confirm="Voulez-vous vraiment supprimer cette annonce ?">Supprimer</a>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
