<?php
/**
 * =====================================================
 * Modifier une annonce
 * =====================================================
 */

$pageTitle = 'Modifier l\'annonce';
require_once __DIR__ . '/auth.php';

requireValidated();

$user = getCurrentUser();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Annonce non trouvée.');
    redirect('annonces.php');
}

// Récupérer l'annonce
$annonce = dbFetchOne("SELECT * FROM annonces WHERE id = ?", [$id]);

if (!$annonce) {
    setFlash('error', 'Annonce non trouvée.');
    redirect('annonces.php');
}

// Vérifier les droits (propriétaire ou bureau/admin)
if ($annonce['user_id'] != $user['id'] && !isBureau()) {
    setFlash('error', 'Vous n\'avez pas le droit de modifier cette annonce.');
    redirect('annonces.php');
}

$error = '';
$categories = dbFetchAll("SELECT * FROM annonce_categories ORDER BY nom");
$images = dbFetchAll("SELECT * FROM annonce_images WHERE annonce_id = ?", [$id]);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $titre = trim($_POST['titre'] ?? '');
        $prix = $_POST['prix'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $categorie_id = (int)($_POST['categorie_id'] ?? 0);
        
        if (empty($titre)) {
            $error = 'Le titre est obligatoire.';
        } elseif (!$categorie_id) {
            $error = 'Veuillez sélectionner une catégorie.';
        } else {
            // Mettre à jour l'annonce
            $sql = "UPDATE annonces SET categorie_id = ?, titre = ?, prix = ?, description = ? WHERE id = ?";
            dbExecute($sql, [
                $categorie_id,
                $titre,
                $prix !== '' ? (float)$prix : null,
                $description,
                $id
            ]);
            
            // Supprimer les images cochées
            if (!empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $imageId) {
                    $img = dbFetchOne("SELECT * FROM annonce_images WHERE id = ? AND annonce_id = ?", [$imageId, $id]);
                    if ($img) {
                        deleteImage($img['image_path']);
                        dbExecute("DELETE FROM annonce_images WHERE id = ?", [$imageId]);
                    }
                }
            }
            
            // Ajouter de nouvelles images
            $currentImageCount = dbFetchOne("SELECT COUNT(*) as c FROM annonce_images WHERE annonce_id = ?", [$id])['c'];
            if (!empty($_FILES['images']['name'][0]) && $currentImageCount < MAX_IMAGES_PER_ANNONCE) {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if ($currentImageCount >= MAX_IMAGES_PER_ANNONCE) break;
                    if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                    
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'tmp_name' => $tmpName,
                        'size' => $_FILES['images']['size'][$key],
                        'error' => $_FILES['images']['error'][$key]
                    ];
                    
                    $path = uploadImage($file, 'annonces');
                    if ($path) {
                        dbExecute("INSERT INTO annonce_images (annonce_id, image_path) VALUES (?, ?)", [$id, $path]);
                        $currentImageCount++;
                    }
                }
            }
            
            setFlash('success', 'L\'annonce a été mise à jour.');
            redirect('annonce_view.php?id=' . $id);
        }
    }
} else {
    $_POST = $annonce;
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Modifier l'annonce</h1>
</div>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <?php csrfField(); ?>
            
            <div class="form-group">
                <label for="titre">Titre <span class="required">*</span></label>
                <input type="text" id="titre" name="titre" class="form-control" 
                       value="<?php echo escape($_POST['titre'] ?? ''); ?>" 
                       maxlength="255" required>
            </div>
            
            <div class="form-group">
                <label for="categorie_id">Catégorie <span class="required">*</span></label>
                <select name="categorie_id" id="categorie_id" class="form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($_POST['categorie_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($cat['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="prix">Prix (€)</label>
                <input type="number" id="prix" name="prix" class="form-control" 
                       value="<?php echo escape($_POST['prix'] ?? ''); ?>" 
                       min="0" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" 
                          maxlength="2000"><?php echo escape($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <?php if (!empty($images)): ?>
            <div class="form-group">
                <label>Photos actuelles</label>
                <div class="annonce-images" style="margin-bottom: 10px;">
                    <?php foreach ($images as $img): ?>
                        <div style="display: inline-block; text-align: center; margin-right: 10px;">
                            <img src="<?php echo UPLOAD_URL . escape($img['image_path']); ?>" alt="">
                            <br>
                            <label>
                                <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>">
                                Supprimer
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="images">Ajouter des photos (max <?php echo MAX_IMAGES_PER_ANNONCE - count($images); ?>)</label>
                <input type="file" id="images" name="images[]" class="form-control" 
                       accept="image/*" multiple <?php echo count($images) >= MAX_IMAGES_PER_ANNONCE ? 'disabled' : ''; ?>>
            </div>
            
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="annonce_view.php?id=<?php echo $id; ?>" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
