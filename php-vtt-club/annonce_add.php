<?php
/**
 * =====================================================
 * Créer une annonce
 * =====================================================
 */

$pageTitle = 'Nouvelle annonce';
require_once __DIR__ . '/auth.php';

// Vérifier la connexion et le compte validé
requireValidated();

$user = getCurrentUser();
$error = '';

// Récupérer les catégories
$categories = dbFetchAll("SELECT * FROM annonce_categories ORDER BY nom");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $titre = trim($_POST['titre'] ?? '');
        $prix = $_POST['prix'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $categorie_id = (int)($_POST['categorie_id'] ?? 0);
        
        // Validation
        if (empty($titre)) {
            $error = 'Le titre est obligatoire.';
        } elseif (!$categorie_id) {
            $error = 'Veuillez sélectionner une catégorie.';
        } else {
            // Insérer l'annonce
            $sql = "INSERT INTO annonces (user_id, categorie_id, titre, prix, description, statut) 
                    VALUES (?, ?, ?, ?, ?, 'publiee')";
            dbExecute($sql, [
                $user['id'],
                $categorie_id,
                $titre,
                $prix !== '' ? (float)$prix : null,
                $description
            ]);
            
            $annonceId = dbLastInsertId();
            
            // Upload des images (max 3)
            if (!empty($_FILES['images']['name'][0])) {
                $uploadCount = 0;
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if ($uploadCount >= MAX_IMAGES_PER_ANNONCE) break;
                    if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                    
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'tmp_name' => $tmpName,
                        'size' => $_FILES['images']['size'][$key],
                        'error' => $_FILES['images']['error'][$key]
                    ];
                    
                    $path = uploadImage($file, 'annonces');
                    if ($path) {
                        dbExecute("INSERT INTO annonce_images (annonce_id, image_path) VALUES (?, ?)", 
                            [$annonceId, $path]);
                        $uploadCount++;
                    }
                }
            }
            
            setFlash('success', 'Votre annonce a été publiée !');
            redirect('annonce_view.php?id=' . $annonceId);
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Déposer une annonce</h1>
    <p>Partagez votre annonce avec les autres membres du club</p>
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
                       min="0" step="0.01" placeholder="Laisser vide si non applicable">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" 
                          maxlength="2000"><?php echo escape($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="images">Photos (max 3)</label>
                <input type="file" id="images" name="images[]" class="form-control" 
                       accept="image/*" multiple>
                <p class="form-text">Formats acceptés : JPG, PNG, GIF, WebP. Taille max : 5 Mo par image.</p>
            </div>
            
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary">Publier l'annonce</button>
                <a href="annonces.php" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
