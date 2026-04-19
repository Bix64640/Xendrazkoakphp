<?php
/**
 * =====================================================
 * Gestion des sorties
 * =====================================================
 */

$pageTitle = 'Gestion des sorties';
require_once __DIR__ . '/auth.php';

requireBureau();

$user = getCurrentUser();
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_sortie = $_POST['date_sortie'] ?? '';
    $nombre_places = (int)($_POST['nombre_places'] ?? 20);
    $encadrants = trim($_POST['encadrants'] ?? '');
    $accompagnateurs = trim($_POST['accompagnateurs'] ?? '');
    $statut = $_POST['statut'] ?? 'ouverte';
    
    if (empty($titre) || empty($date_sortie)) {
        $error = 'Le titre et la date sont obligatoires.';
    } elseif ($nombre_places < 1) {
        $error = 'Le nombre de places doit être au moins 1.';
    } else {
        if ($action === 'edit' && $id) {
            // Modifier
            $sql = "UPDATE sorties SET titre = ?, description = ?, date_sortie = ?, nombre_places = ?, 
                    encadrants = ?, accompagnateurs = ?, statut = ? WHERE id = ?";
            dbExecute($sql, [$titre, $description, $date_sortie, $nombre_places, $encadrants, $accompagnateurs, $statut, $id]);
            setFlash('success', 'La sortie a été mise à jour.');
            redirect('manage_sorties.php');
        } else {
            // Ajouter
            $sql = "INSERT INTO sorties (titre, description, date_sortie, nombre_places, encadrants, accompagnateurs, statut, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            dbExecute($sql, [$titre, $description, $date_sortie, $nombre_places, $encadrants, $accompagnateurs, $statut, $user['id']]);
            setFlash('success', 'La sortie a été créée.');
            redirect('manage_sorties.php');
        }
    }
}

// Supprimer
if ($action === 'delete' && $id) {
    dbExecute("DELETE FROM sorties WHERE id = ?", [$id]);
    setFlash('success', 'La sortie a été supprimée.');
    redirect('manage_sorties.php');
}

// Changer statut
if ($action === 'status' && $id && isset($_GET['status'])) {
    $newStatus = $_GET['status'];
    $validStatuses = ['ouverte', 'complete', 'annulee', 'archivee'];
    
    if (in_array($newStatus, $validStatuses)) {
        $sortie = dbFetchOne("SELECT statut FROM sorties WHERE id = ?", [$id]);
        if ($sortie) {
            dbExecute("UPDATE sorties SET statut = ? WHERE id = ?", [$newStatus, $id]);
            logStatutChange('sorties', $id, $sortie['statut'], $newStatus, $user['id']);
            setFlash('success', 'Le statut a été modifié.');
        }
    }
    redirect('manage_sorties.php');
}

// Récupérer les sorties
$sorties = dbFetchAll("
    SELECT s.*, u.prenom, u.nom as organisateur_nom,
           (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits
    FROM sorties s 
    JOIN users u ON s.created_by = u.id 
    ORDER BY s.date_sortie DESC
");

// Récupérer la sortie à modifier
$editSortie = null;
if ($action === 'edit' && $id) {
    $editSortie = dbFetchOne("SELECT * FROM sorties WHERE id = ?", [$id]);
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1>Gestion des sorties</h1>
        <p><?php echo count($sorties); ?> sortie<?php echo count($sorties) > 1 ? 's' : ''; ?></p>
    </div>
    <div>
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <a href="?action=add" class="btn btn-primary">+ Nouvelle sortie</a>
        <?php endif; ?>
        <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'bureau_dashboard.php'; ?>" class="btn btn-outline">← Retour</a>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Formulaire d'ajout/modification -->
<div class="card mb-3" style="max-width: 800px;">
    <div class="card-header">
        <h3><?php echo $action === 'edit' ? 'Modifier la sortie' : 'Nouvelle sortie'; ?></h3>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="?action=<?php echo $action; ?><?php echo $id ? "&id=$id" : ''; ?>">
            <?php csrfField(); ?>
            
            <div class="form-group">
                <label for="titre">Titre <span class="required">*</span></label>
                <input type="text" id="titre" name="titre" class="form-control" 
                       value="<?php echo escape($editSortie['titre'] ?? $_POST['titre'] ?? ''); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_sortie">Date <span class="required">*</span></label>
                    <input type="date" id="date_sortie" name="date_sortie" class="form-control" 
                           value="<?php echo escape($editSortie['date_sortie'] ?? $_POST['date_sortie'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nombre_places">Nombre de places <span class="required">*</span></label>
                    <input type="number" id="nombre_places" name="nombre_places" class="form-control" 
                           value="<?php echo escape($editSortie['nombre_places'] ?? $_POST['nombre_places'] ?? '20'); ?>" 
                           min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-control">
                        <option value="ouverte" <?php echo ($editSortie['statut'] ?? 'ouverte') === 'ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                        <option value="complete" <?php echo ($editSortie['statut'] ?? '') === 'complete' ? 'selected' : ''; ?>>Complète</option>
                        <option value="annulee" <?php echo ($editSortie['statut'] ?? '') === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                        <option value="archivee" <?php echo ($editSortie['statut'] ?? '') === 'archivee' ? 'selected' : ''; ?>>Archivée</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" 
                          rows="4"><?php echo escape($editSortie['description'] ?? $_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="encadrants">Encadrants</label>
                    <input type="text" id="encadrants" name="encadrants" class="form-control" 
                           value="<?php echo escape($editSortie['encadrants'] ?? $_POST['encadrants'] ?? ''); ?>"
                           placeholder="Noms séparés par des virgules">
                </div>
                
                <div class="form-group">
                    <label for="accompagnateurs">Accompagnateurs</label>
                    <input type="text" id="accompagnateurs" name="accompagnateurs" class="form-control" 
                           value="<?php echo escape($editSortie['accompagnateurs'] ?? $_POST['accompagnateurs'] ?? ''); ?>"
                           placeholder="Noms séparés par des virgules">
                </div>
            </div>
            
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action === 'edit' ? 'Enregistrer' : 'Créer'; ?>
                </button>
                <a href="manage_sorties.php" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Liste des sorties -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Titre</th>
                    <th>Places</th>
                    <th>Statut</th>
                    <th>Organisateur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sorties)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucune sortie.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sorties as $sortie): ?>
                        <tr>
                            <td><?php echo formatDate($sortie['date_sortie']); ?></td>
                            <td><strong><?php echo escape($sortie['titre']); ?></strong></td>
                            <td>
                                <?php echo $sortie['nb_inscrits']; ?>/<?php echo $sortie['nombre_places']; ?>
                                <?php if ($sortie['nb_inscrits'] >= $sortie['nombre_places']): ?>
                                    <span class="badge badge-info">Complet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $sortie['statut'] === 'ouverte' ? 'success' : 
                                        ($sortie['statut'] === 'complete' ? 'info' : 
                                        ($sortie['statut'] === 'annulee' ? 'error' : 'secondary')); 
                                ?>">
                                    <?php echo $sortie['statut']; ?>
                                </span>
                            </td>
                            <td><?php echo escape($sortie['prenom']); ?></td>
                            <td class="actions">
                                <a href="sortie_view.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Voir</a>
                                <a href="?action=edit&id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-outline">Modifier</a>
                                
                                <select onchange="if(this.value) window.location='?action=status&id=<?php echo $sortie['id']; ?>&status='+this.value" 
                                        class="form-control" style="width: auto; display: inline-block; padding: 5px;">
                                    <option value="">Statut...</option>
                                    <option value="ouverte">Ouverte</option>
                                    <option value="complete">Complète</option>
                                    <option value="annulee">Annulée</option>
                                    <option value="archivee">Archivée</option>
                                </select>
                                
                                <a href="?action=delete&id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-danger"
                                   data-confirm="Supprimer cette sortie et toutes ses inscriptions ?">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
