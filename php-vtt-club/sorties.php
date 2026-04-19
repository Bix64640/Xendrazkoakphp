<?php
/**
 * =====================================================
 * Planning des sorties
 * =====================================================
 */

$pageTitle = 'Planning des sorties';
require_once __DIR__ . '/header.php';

$user = getCurrentUser();

// Filtres
$mois = $_GET['mois'] ?? date('Y-m');
$showPast = isset($_GET['archives']);

// Construire la requête
if ($showPast) {
    $sql = "
        SELECT s.*, 
               (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits,
               u.prenom, u.nom as organisateur_nom
        FROM sorties s
        JOIN users u ON s.created_by = u.id
        WHERE s.date_sortie < CURDATE()
        ORDER BY s.date_sortie DESC
        LIMIT 20
    ";
    $sorties = dbFetchAll($sql);
} else {
    $sql = "
        SELECT s.*, 
               (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits,
               u.prenom, u.nom as organisateur_nom
        FROM sorties s
        JOIN users u ON s.created_by = u.id
        WHERE DATE_FORMAT(s.date_sortie, '%Y-%m') = ? AND s.date_sortie >= CURDATE()
        ORDER BY s.date_sortie ASC
    ";
    $sorties = dbFetchAll($sql, [$mois]);
}

// Vérifier les inscriptions de l'utilisateur
$userInscriptions = [];
if ($user) {
    $inscriptions = dbFetchAll("
        SELECT sortie_id FROM sortie_inscriptions 
        WHERE user_id = ? AND statut = 'inscrit'
    ", [$user['id']]);
    foreach ($inscriptions as $insc) {
        $userInscriptions[] = $insc['sortie_id'];
    }
}

// Générer les mois pour le sélecteur
$moisOptions = [];
for ($i = 0; $i < 6; $i++) {
    $date = date('Y-m', strtotime("+$i month"));
    $label = strftime('%B %Y', strtotime($date . '-01'));
    // Alternative si strftime ne fonctionne pas
    $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $m = (int)date('n', strtotime($date . '-01'));
    $y = date('Y', strtotime($date . '-01'));
    $label = $moisNoms[$m-1] . ' ' . $y;
    $moisOptions[$date] = $label;
}
?>

<div class="page-header">
    <h1>Planning des sorties</h1>
    <p>Consultez les prochaines sorties VTT et inscrivez-vous !</p>
</div>

<!-- Filtres -->
<div class="filters">
    <form method="GET" action="">
        <div class="form-group">
            <label for="mois">Mois</label>
            <select name="mois" id="mois" class="form-control" onchange="this.form.submit()">
                <?php foreach ($moisOptions as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $mois === $value ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>&nbsp;</label>
            <?php if ($showPast): ?>
                <a href="sorties.php" class="btn btn-outline">Voir les sorties à venir</a>
            <?php else: ?>
                <a href="sorties.php?archives=1" class="btn btn-outline">Voir les archives</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (empty($sorties)): ?>
    <div class="empty-state">
        <div class="icon">🚴</div>
        <h3>Aucune sortie prévue</h3>
        <p><?php echo $showPast ? 'Aucune sortie archivée.' : 'Aucune sortie n\'est prévue pour ce mois. Consultez un autre mois !'; ?></p>
    </div>
<?php else: ?>
    <div class="grid grid-auto">
        <?php foreach ($sorties as $sortie): ?>
            <?php 
            $placesRestantes = $sortie['nombre_places'] - $sortie['nb_inscrits'];
            $isInscrit = in_array($sortie['id'], $userInscriptions);
            $canInscribe = $user && isValidated() && $placesRestantes > 0 && $sortie['statut'] === 'ouverte' && !$isInscrit;
            ?>
            <article class="card sortie-card">
                <div class="card-body">
                    <div class="flex flex-between items-center mb-1">
                        <div class="sortie-date">
                            <span>📅</span>
                            <?php echo formatDate($sortie['date_sortie']); ?>
                        </div>
                        <span class="badge badge-<?php 
                            echo $sortie['statut'] === 'ouverte' ? 'success' : 
                                ($sortie['statut'] === 'complete' ? 'info' : 
                                ($sortie['statut'] === 'annulee' ? 'error' : 'secondary')); 
                        ?>">
                            <?php echo ucfirst($sortie['statut']); ?>
                        </span>
                    </div>
                    
                    <h3><?php echo escape($sortie['titre']); ?></h3>
                    
                    <?php if (!empty($sortie['description'])): ?>
                        <p class="text-muted"><?php echo escape(truncate($sortie['description'], 120)); ?></p>
                    <?php endif; ?>
                    
                    <div class="mt-2">
                        <?php if (!empty($sortie['encadrants'])): ?>
                            <p><strong>Encadrants :</strong> <?php echo escape($sortie['encadrants']); ?></p>
                        <?php endif; ?>
                        
                        <p class="sortie-places <?php echo $placesRestantes <= 0 ? 'full' : ''; ?>">
                            <strong>Places :</strong> 
                            <?php echo $sortie['nb_inscrits']; ?>/<?php echo $sortie['nombre_places']; ?>
                            <?php if ($placesRestantes > 0): ?>
                                (<?php echo $placesRestantes; ?> restante<?php echo $placesRestantes > 1 ? 's' : ''; ?>)
                            <?php else: ?>
                                - Complet
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="card-footer flex flex-between items-center">
                    <a href="sortie_view.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-outline">Détails</a>
                    
                    <?php if ($isInscrit): ?>
                        <span class="badge badge-success">✓ Inscrit</span>
                    <?php elseif ($canInscribe): ?>
                        <a href="sortie_inscription.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-primary">S'inscrire</a>
                    <?php elseif (!$user): ?>
                        <a href="login.php" class="btn btn-sm btn-outline">Connexion requise</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
