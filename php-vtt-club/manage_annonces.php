<?php
/**
 * =====================================================
 * Gestion des annonces (bureau/admin)
 * =====================================================
 */

$pageTitle = 'Gestion des annonces';
require_once __DIR__ . '/auth.php';

requireBureau();

$user = getCurrentUser();
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

// Actions sur les annonces
if ($action && $id) {
    $annonce = dbFetchOne("SELECT * FROM annonces WHERE id = ?", [$id]);
    
    if ($annonce) {
        switch ($action) {
            case 'publish':
                dbExecute("UPDATE annonces SET statut = 'publiee' WHERE id = ?", [$id]);
                logStatutChange('annonces', $id, $annonce['statut'], 'publiee', $user['id']);
                createNotification($annonce['user_id'], 'Annonce publiée', "Votre annonce \"{$annonce['titre']}\" a été publiée.");
                setFlash('success', 'L\'annonce a été publiée.');
                break;
                
            case 'restrict':
                dbExecute("UPDATE annonces SET statut = 'restreinte' WHERE id = ?", [$id]);
                logStatutChange('annonces', $id, $annonce['statut'], 'restreinte', $user['id']);
                createNotification($annonce['user_id'], 'Annonce restreinte', "Votre annonce \"{$annonce['titre']}\" a été restreinte. Contactez un membre du bureau pour plus d'informations.");
                setFlash('success', 'L\'annonce a été restreinte.');
                break;
                
            case 'archive':
                dbExecute("UPDATE annonces SET statut = 'archivee' WHERE id = ?", [$id]);
                logStatutChange('annonces', $id, $annonce['statut'], 'archivee', $user['id']);
                setFlash('success', 'L\'annonce a été archivée.');
                break;
                
            case 'delete':
                // Supprimer les images
                $images = dbFetchAll("SELECT * FROM annonce_images WHERE annonce_id = ?", [$id]);
                foreach ($images as $img) {
                    deleteImage($img['image_path']);
                }
                dbExecute("DELETE FROM annonces WHERE id = ?", [$id]);
                setFlash('success', 'L\'annonce a été supprimée.');
                break;
        }
    }
    
    redirect('manage_annonces.php' . (!empty($_GET['statut']) ? '?statut=' . $_GET['statut'] : ''));
}

// Filtres
$filterStatut = $_GET['statut'] ?? '';
$filterCategorie = $_GET['categorie'] ?? '';
$search = $_GET['search'] ?? '';

$categories = dbFetchAll("SELECT * FROM annonce_categories ORDER BY nom");

// Construire la requête
$sql = "
    SELECT a.*, c.nom as categorie_nom, u.prenom, u.nom as auteur_nom
    FROM annonces a
    JOIN annonce_categories c ON a.categorie_id = c.id
    JOIN users u ON a.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($filterStatut) {
    $sql .= " AND a.statut = ?";
    $params[] = $filterStatut;
}

if ($filterCategorie) {
    $sql .= " AND a.categorie_id = ?";
    $params[] = $filterCategorie;
}

if ($search) {
    $sql .= " AND (a.titre LIKE ? OR a.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.created_at DESC";

$annonces = dbFetchAll($sql, $params);

require_once __DIR__ . '/header.php';
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1>Gestion des annonces</h1>
        <p><?php echo count($annonces); ?> annonce<?php echo count($annonces) > 1 ? 's' : ''; ?></p>
    </div>
    <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'bureau_dashboard.php'; ?>" class="btn btn-outline">← Retour</a>
</div>

<!-- Filtres -->
<div class="filters">
    <form method="GET" action="">
        <div class="form-group">
            <label for="search">Recherche</label>
            <input type="text" id="search" name="search" class="form-control" 
                   placeholder="Mot-clé..." value="<?php echo escape($search); ?>">
        </div>
        
        <div class="form-group">
            <label for="statut">Statut</label>
            <select name="statut" id="statut" class="form-control">
                <option value="">Tous</option>
                <option value="en_attente" <?php echo $filterStatut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                <option value="publiee" <?php echo $filterStatut === 'publiee' ? 'selected' : ''; ?>>Publiée</option>
                <option value="restreinte" <?php echo $filterStatut === 'restreinte' ? 'selected' : ''; ?>>Restreinte</option>
                <option value="archivee" <?php echo $filterStatut === 'archivee' ? 'selected' : ''; ?>>Archivée</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="categorie">Catégorie</label>
            <select name="categorie" id="categorie" class="form-control">
                <option value="">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategorie == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($cat['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="manage_annonces.php" class="btn btn-outline">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Auteur</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($annonces)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Aucune annonce.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($annonces as $annonce): ?>
                        <tr>
                            <td><strong><?php echo escape($annonce['titre']); ?></strong></td>
                            <td><?php echo escape($annonce['categorie_nom']); ?></td>
                            <td><?php echo $annonce['prix'] ? formatPrix($annonce['prix']) : '-'; ?></td>
                            <td><?php echo escape($annonce['prenom'] . ' ' . $annonce['auteur_nom']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $annonce['statut'] === 'publiee' ? 'success' : 
                                        ($annonce['statut'] === 'en_attente' ? 'warning' : 
                                        ($annonce['statut'] === 'restreinte' ? 'error' : 'secondary')); 
                                ?>">
                                    <?php echo $annonce['statut']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($annonce['created_at']); ?></td>
                            <td class="actions">
                                <a href="annonce_view.php?id=<?php echo $annonce['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Voir</a>
                                
                                <?php if ($annonce['statut'] !== 'publiee'): ?>
                                    <a href="?action=publish&id=<?php echo $annonce['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                       class="btn btn-sm btn-primary">Publier</a>
                                <?php endif; ?>
                                
                                <?php if ($annonce['statut'] === 'publiee'): ?>
                                    <a href="?action=restrict&id=<?php echo $annonce['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                       class="btn btn-sm btn-warning">Restreindre</a>
                                <?php endif; ?>
                                
                                <?php if ($annonce['statut'] !== 'archivee'): ?>
                                    <a href="?action=archive&id=<?php echo $annonce['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                       class="btn btn-sm btn-outline">Archiver</a>
                                <?php endif; ?>
                                
                                <a href="?action=delete&id=<?php echo $annonce['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                   class="btn btn-sm btn-danger" data-confirm="Supprimer cette annonce ?">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
