<?php
/**
 * =====================================================
 * Gestion des catégories d'annonces
 * =====================================================
 */

$pageTitle = 'Gestion des catégories';
require_once __DIR__ . '/auth.php';

requireAdmin();

$user = getCurrentUser();
$error = '';
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

// Ajouter une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $nom = trim($_POST['nom'] ?? '');
    
    if (empty($nom)) {
        $error = 'Le nom de la catégorie est obligatoire.';
    } else {
        // Vérifier si elle existe déjà
        $existing = dbFetchOne("SELECT id FROM annonce_categories WHERE nom = ?", [$nom]);
        
        if ($existing && (!$id || $existing['id'] != $id)) {
            $error = 'Cette catégorie existe déjà.';
        } else {
            if ($id) {
                // Modifier
                dbExecute("UPDATE annonce_categories SET nom = ? WHERE id = ?", [$nom, $id]);
                setFlash('success', 'La catégorie a été modifiée.');
            } else {
                // Ajouter
                dbExecute("INSERT INTO annonce_categories (nom) VALUES (?)", [$nom]);
                setFlash('success', 'La catégorie a été ajoutée.');
            }
            redirect('manage_categories.php');
        }
    }
}

// Supprimer
if ($action === 'delete' && $id) {
    // Vérifier si des annonces utilisent cette catégorie
    $count = dbFetchOne("SELECT COUNT(*) as c FROM annonces WHERE categorie_id = ?", [$id])['c'];
    
    if ($count > 0) {
        setFlash('error', "Impossible de supprimer cette catégorie car $count annonce(s) l'utilisent.");
    } else {
        dbExecute("DELETE FROM annonce_categories WHERE id = ?", [$id]);
        setFlash('success', 'La catégorie a été supprimée.');
    }
    redirect('manage_categories.php');
}

// Récupérer les catégories avec le nombre d'annonces
$categories = dbFetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM annonces WHERE categorie_id = c.id) as nb_annonces
    FROM annonce_categories c
    ORDER BY c.nom
");

// Catégorie à modifier
$editCat = null;
if ($action === 'edit' && $id) {
    $editCat = dbFetchOne("SELECT * FROM annonce_categories WHERE id = ?", [$id]);
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1>Gestion des catégories</h1>
        <p>Catégories d'annonces</p>
    </div>
    <a href="admin_dashboard.php" class="btn btn-outline">← Retour</a>
</div>

<div class="grid grid-2" style="gap: 20px;">
    <!-- Formulaire -->
    <div class="card">
        <div class="card-header">
            <h3><?php echo $editCat ? 'Modifier la catégorie' : 'Ajouter une catégorie'; ?></h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo $editCat ? "?action=edit&id={$editCat['id']}" : ''; ?>">
                <?php csrfField(); ?>
                
                <div class="form-group">
                    <label for="nom">Nom de la catégorie <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control" 
                           value="<?php echo escape($editCat['nom'] ?? ''); ?>" required>
                </div>
                
                <div class="flex gap-1">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editCat ? 'Enregistrer' : 'Ajouter'; ?>
                    </button>
                    <?php if ($editCat): ?>
                        <a href="manage_categories.php" class="btn btn-outline">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Liste des catégories -->
    <div class="card">
        <div class="card-header">
            <h3>Catégories existantes (<?php echo count($categories); ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <p class="text-muted text-center">Aucune catégorie.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Annonces</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?php echo escape($cat['nom']); ?></strong></td>
                                <td><?php echo $cat['nb_annonces']; ?></td>
                                <td class="actions">
                                    <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline">Modifier</a>
                                    <?php if ($cat['nb_annonces'] == 0): ?>
                                        <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger"
                                           data-confirm="Supprimer cette catégorie ?">Supprimer</a>
                                    <?php else: ?>
                                        <span class="text-muted text-sm" title="Catégorie utilisée">🔒</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
