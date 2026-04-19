<?php
/**
 * =====================================================
 * Détail d'une sortie
 * =====================================================
 */

require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Sortie non trouvée.');
    redirect('sorties.php');
}

// Récupérer la sortie
$sortie = dbFetchOne("
    SELECT s.*, u.prenom, u.nom as organisateur_nom,
           (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits
    FROM sorties s 
    JOIN users u ON s.created_by = u.id 
    WHERE s.id = ?
", [$id]);

if (!$sortie) {
    setFlash('error', 'Sortie non trouvée.');
    redirect('sorties.php');
}

$pageTitle = $sortie['titre'];
require_once __DIR__ . '/header.php';

$user = getCurrentUser();
$placesRestantes = $sortie['nombre_places'] - $sortie['nb_inscrits'];

// Vérifier si l'utilisateur est inscrit
$isInscrit = false;
if ($user) {
    $inscription = dbFetchOne("
        SELECT * FROM sortie_inscriptions 
        WHERE sortie_id = ? AND user_id = ? AND statut = 'inscrit'
    ", [$id, $user['id']]);
    $isInscrit = $inscription !== false;
}

$canInscribe = $user && isValidated() && $placesRestantes > 0 && $sortie['statut'] === 'ouverte' && !$isInscrit;

// Liste des inscrits (visible pour bureau/admin)
$inscrits = [];
if (isBureau()) {
    $inscrits = dbFetchAll("
        SELECT u.id, u.nom, u.prenom, u.email, si.created_at as date_inscription
        FROM sortie_inscriptions si
        JOIN users u ON si.user_id = u.id
        WHERE si.sortie_id = ? AND si.statut = 'inscrit'
        ORDER BY si.created_at ASC
    ", [$id]);
}
?>

<div class="card">
    <div class="card-header flex flex-between items-center">
        <h1><?php echo escape($sortie['titre']); ?></h1>
        <span class="badge badge-<?php 
            echo $sortie['statut'] === 'ouverte' ? 'success' : 
                ($sortie['statut'] === 'complete' ? 'info' : 
                ($sortie['statut'] === 'annulee' ? 'error' : 'secondary')); 
        ?>">
            <?php echo ucfirst($sortie['statut']); ?>
        </span>
    </div>
    
    <div class="card-body">
        <div class="grid grid-2" style="gap: 30px;">
            <div>
                <h3>Informations</h3>
                <table class="table">
                    <tr>
                        <th>Date</th>
                        <td><?php echo formatDate($sortie['date_sortie']); ?></td>
                    </tr>
                    <tr>
                        <th>Places</th>
                        <td>
                            <?php echo $sortie['nb_inscrits']; ?>/<?php echo $sortie['nombre_places']; ?>
                            <?php if ($placesRestantes > 0): ?>
                                <span class="text-success">(<?php echo $placesRestantes; ?> disponible<?php echo $placesRestantes > 1 ? 's' : ''; ?>)</span>
                            <?php else: ?>
                                <span class="text-error">(Complet)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($sortie['encadrants'])): ?>
                    <tr>
                        <th>Encadrants</th>
                        <td><?php echo escape($sortie['encadrants']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($sortie['accompagnateurs'])): ?>
                    <tr>
                        <th>Accompagnateurs</th>
                        <td><?php echo escape($sortie['accompagnateurs']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Organisateur</th>
                        <td><?php echo escape($sortie['prenom'] . ' ' . $sortie['organisateur_nom']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Description</h3>
                <?php if (!empty($sortie['description'])): ?>
                    <p style="line-height: 1.8;"><?php echo nl2br(escape($sortie['description'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">Aucune description disponible.</p>
                <?php endif; ?>
                
                <div class="mt-3">
                    <?php if ($isInscrit): ?>
                        <div class="alert alert-success">
                            ✓ Vous êtes inscrit à cette sortie.
                        </div>
                        <a href="sortie_inscription.php?id=<?php echo $sortie['id']; ?>&action=annuler" 
                           class="btn btn-outline" 
                           data-confirm="Voulez-vous vraiment annuler votre inscription ?">
                            Annuler mon inscription
                        </a>
                    <?php elseif ($canInscribe): ?>
                        <a href="sortie_inscription.php?id=<?php echo $sortie['id']; ?>" class="btn btn-primary btn-lg">
                            S'inscrire à cette sortie
                        </a>
                    <?php elseif (!$user): ?>
                        <div class="alert alert-info">
                            <a href="login.php">Connectez-vous</a> pour vous inscrire à cette sortie.
                        </div>
                    <?php elseif ($sortie['statut'] !== 'ouverte'): ?>
                        <div class="alert alert-warning">
                            Les inscriptions sont fermées pour cette sortie.
                        </div>
                    <?php elseif ($placesRestantes <= 0): ?>
                        <div class="alert alert-warning">
                            Cette sortie est complète.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (isBureau() && !empty($inscrits)): ?>
            <div class="mt-3">
                <h3>Liste des inscrits (<?php echo count($inscrits); ?>)</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Date d'inscription</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscrits as $i => $inscrit): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo escape($inscrit['prenom'] . ' ' . $inscrit['nom']); ?></td>
                                    <td><?php echo escape($inscrit['email']); ?></td>
                                    <td><?php echo formatDate($inscrit['date_inscription'], true); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-footer">
        <a href="sorties.php" class="btn btn-outline">← Retour au planning</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
