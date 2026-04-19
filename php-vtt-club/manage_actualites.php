<?php
/**
 * =====================================================
 * Gestion des actualités
 * =====================================================
 */

$pageTitle = 'Gestion des actualités';
require_once __DIR__ . '/auth.php';

requireBureau();

$user = getCurrentUser();
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $statut = $_POST['statut'] ?? 'publie';
    
    if (empty($titre) || empty($contenu)) {
        $error = 'Le titre et le contenu sont obligatoires.';
    } else {
        // Upload image
        $imagePath = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadImage($_FILES['image'], 'actualites');
        }
        
        if ($action === 'edit' && $id) {
            // Modifier
            $sql = "UPDATE actualites SET titre = ?, contenu = ?, statut = ?";
            $params = [$titre, $contenu, $statut];
            
            if ($imagePath) {
                $sql .= ", image = ?";
                $params[] = $imagePath;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            dbExecute($sql, $params);
            setFlash('success', 'L\'actualité a été mise à jour.');
            redirect('manage_actualites.php');
            
        } else {
            // Ajouter
            $sql = "INSERT INTO actualites (titre, contenu, image, statut, created_by) VALUES (?, ?, ?, ?, ?)";
            dbExecute($sql, [$titre, $contenu, $imagePath, $statut, $user['id']]);
            setFlash('success', 'L\'actualité a été créée.');
            redirect('manage_actualites.php');
        }
    }
}

// Supprimer
if ($action === 'delete' && $id) {
    $actu = dbFetchOne("SELECT * FROM actualites WHERE id = ?", [$id]);
    if ($actu) {
        if ($actu['image']) {
            deleteImage($actu['image']);
        }
        dbExecute("DELETE FROM actualites WHERE id = ?", [$id]);
        setFlash('success', 'L\'actualité a été supprimée.');
    }
    redirect('manage_actualites.php');
}

// Archiver
if ($action === 'archive' && $id) {
    $actu = dbFetchOne("SELECT * FROM actualites WHERE id = ?", [$id]);
    if ($actu) {
        $newStatut = $actu['statut'] === 'publie' ? 'archive' : 'publie';
        dbExecute("UPDATE actualites SET statut = ? WHERE id = ?", [$newStatut, $id]);
        logStatutChange('actualites', $id, $actu['statut'], $newStatut, $user['id']);
        setFlash('success', 'Le statut a été modifié.');
    }
    redirect('manage_actualites.php');
}

// Récupérer les actualités
$actualites = dbFetchAll("
    SELECT a.*, u.prenom, u.nom as auteur_nom 
    FROM actualites a 
    JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
");

// Récupérer l'actualité à modifier
$editActu = null;
if ($action === 'edit' && $id) {
    $editActu = dbFetchOne("SELECT * FROM actualites WHERE id = ?", [$id]);
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1>Gestion des actualités</h1>
        <p><?php echo count($actualites); ?> actualité<?php echo count($actualites) > 1 ? 's' : ''; ?></p>
    </div>
    <div>
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <a href="?action=add" class="btn btn-primary">+ Nouvelle actualité</a>
        <?php endif; ?>
        <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'bureau_dashboard.php'; ?>" class="btn btn-outline">← Retour</a>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Formulaire d'ajout/modification -->
<div class="card mb-3" style="max-width: 800px;">
    <div class="card-header">
        <h3><?php echo $action === 'edit' ? 'Modifier l\'actualité' : 'Nouvelle actualité'; ?></h3>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="?action=<?php echo $action; ?><?php echo $id ? "&id=$id" : ''; ?>" enctype="multipart/form-data">
            <?php csrfField(); ?>
            
            <div class="form-group">
                <label for="titre">Titre <span class="required">*</span></label>
                <input type="text" id="titre" name="titre" class="form-control" 
                       value="<?php echo escape($editActu['titre'] ?? $_POST['titre'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="contenu">Contenu <span class="required">*</span></label>
                <textarea id="contenu" name="contenu" class="form-control" rows="8" 
                          required><?php echo escape($editActu['contenu'] ?? $_POST['contenu'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="image">Image</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    <?php if ($editActu && $editActu['image']): ?>
                        <p class="form-text">Image actuelle : 
                            <img src="<?php echo UPLOAD_URL . escape($editActu['image']); ?>" alt="" 
                                 style="height: 50px; vertical-align: middle;">
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-control">
                        <option value="publie" <?php echo ($editActu['statut'] ?? 'publie') === 'publie' ? 'selected' : ''; ?>>Publié</option>
                        <option value="archive" <?php echo ($editActu['statut'] ?? '') === 'archive' ? 'selected' : ''; ?>>Archivé</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action === 'edit' ? 'Enregistrer' : 'Publier'; ?>
                </button>
                <a href="manage_actualites.php" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Liste des actualités -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Auteur</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($actualites)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucune actualité.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($actualites as $actu): ?>
                        <tr>
                            <td>
                                <strong><?php echo escape($actu['titre']); ?></strong>
                                <?php if ($actu['image']): ?>
                                    <span title="Avec image">🖼️</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($actu['prenom'] . ' ' . $actu['auteur_nom']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $actu['statut'] === 'publie' ? 'success' : 'secondary'; ?>">
                                    <?php echo $actu['statut']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($actu['created_at']); ?></td>
                            <td class="actions">
                                <a href="actualite_view.php?id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Voir</a>
                                <a href="?action=edit&id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-outline">Modifier</a>
                                <a href="?action=archive&id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-outline">
                                    <?php echo $actu['statut'] === 'publie' ? 'Archiver' : 'Publier'; ?>
                                </a>
                                <a href="?action=delete&id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-danger"
                                   data-confirm="Supprimer cette actualité ?">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
