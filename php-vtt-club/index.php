<?php
/**
 * =====================================================
 * Page d'accueil - Xendrazkoak VTT
 * =====================================================
 */

$pageTitle = 'Accueil';
$bodyClass = 'home-page';
require_once __DIR__ . '/header.php';

// Récupérer les infos du club
$clubInfo = getClubInfo();

// Récupérer les 3 dernières actualités publiées
$actualites = dbFetchAll("
    SELECT a.*, u.prenom, u.nom as auteur_nom 
    FROM actualites a 
    JOIN users u ON a.created_by = u.id 
    WHERE a.statut = 'publie' 
    ORDER BY a.created_at DESC 
    LIMIT 3
");

// Récupérer les 3 prochaines sorties ouvertes
$sorties = dbFetchAll("
    SELECT s.*, 
           (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits
    FROM sorties s 
    WHERE s.statut = 'ouverte' AND s.date_sortie >= CURDATE()
    ORDER BY s.date_sortie ASC 
    LIMIT 3
");

$nomClub = escape($clubInfo['nom_club'] ?? SITE_NAME);
?>

<!-- Section Hero -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-kicker">Club VTT • Ipparalde</span>
        <h1>Bienvenue au <?php echo $nomClub; ?></h1>
        <p>
            Club de VTT pour jeunes initiés , sorties tous les week-ends, encadrées par des bénévoles passionnés.
             Rejoignez-nous pour partager des moments de convivialité et de sport en pleine nature !
        </p>
        <div class="hero-actions">
            <?php if (!isLoggedIn()): ?>
                <a href="signup.php" class="btn btn-primary btn-lg">Rejoindre le club</a>
                <a href="sorties.php" class="btn btn-outline btn-lg">Voir les sorties</a>
            <?php else: ?>
                <a href="sorties.php" class="btn btn-primary btn-lg">Voir les sorties</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Informations du club -->
<section class="section">
    <h2 class="section-title">Informations pratiques</h2>
    <div class="club-info-cards">
        <div class="info-card">
            <div class="icon">📍</div>
            <h3>Adresse</h3>
            <p><?php echo escape($clubInfo['adresse'] ?? 'Non renseignée'); ?></p>
        </div>
        <div class="info-card">
            <div class="icon">📞</div>
            <h3>Téléphone</h3>
            <p><?php echo escape($clubInfo['telephone'] ?? 'Non renseigné'); ?></p>
        </div>
        <div class="info-card">
            <div class="icon">📅</div>
            <h3>Inscriptions</h3>
            <p><?php echo escape($clubInfo['jours_inscription'] ?? 'Contactez-nous'); ?></p>
        </div>
        <div class="info-card">
            <div class="icon">🚴</div>
            <h3>Activités</h3>
            <p>Sorties VTT tous niveaux, randonnées, entraînements</p>
        </div>
    </div>
</section>

<!-- Vidéo YouTube -->
<?php if (!empty($clubInfo['youtube_url'])): ?>
<section class="section">
    <h2 class="section-title">Notre club en vidéo</h2>
    <div class="video-container">
        <iframe
            src="<?php echo escape($clubInfo['youtube_url']); ?>"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>
</section>
<?php endif; ?>

<!-- Actualités récentes -->
<section class="section">
    <div class="flex flex-between items-center mb-2">
        <h2 class="section-title" style="margin-bottom: 0; border: none;">Actualités récentes</h2>
        <a href="actualites.php" class="btn btn-outline btn-sm">Voir toutes</a>
    </div>

    <?php if (empty($actualites)): ?>
        <div class="empty-state">
            <div class="icon">📰</div>
            <h3>Aucune actualité pour le moment</h3>
            <p>Revenez bientôt pour découvrir les nouvelles du club !</p>
        </div>
    <?php else: ?>
        <div class="grid grid-auto">
            <?php foreach ($actualites as $actu): ?>
                <article class="card news-card">
                    <?php if (!empty($actu['image'])): ?>
                        <img src="<?php echo UPLOAD_URL . escape($actu['image']); ?>" alt="" class="card-img">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="news-date">
                            <?php echo formatDate($actu['created_at']); ?>
                        </div>
                        <h3><?php echo escape($actu['titre']); ?></h3>
                        <p><?php echo escape(truncate($actu['contenu'], 150)); ?></p>
                    </div>
                    <div class="card-footer">
                        <a href="actualite_view.php?id=<?php echo $actu['id']; ?>" class="btn btn-sm btn-outline">
                            Lire la suite
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Prochaines sorties -->
<section class="section">
    <div class="flex flex-between items-center mb-2">
        <h2 class="section-title" style="margin-bottom: 0; border: none;">Prochaines sorties</h2>
        <a href="sorties.php" class="btn btn-outline btn-sm">Voir le planning</a>
    </div>

    <?php if (empty($sorties)): ?>
        <div class="empty-state">
            <div class="icon">🚴</div>
            <h3>Aucune sortie prévue</h3>
            <p>Les prochaines sorties seront bientôt annoncées !</p>
        </div>
    <?php else: ?>
        <div class="grid grid-auto">
            <?php foreach ($sorties as $sortie): ?>
                <?php $placesRestantes = $sortie['nombre_places'] - $sortie['nb_inscrits']; ?>
                <article class="card sortie-card">
                    <div class="card-body">
                        <div class="sortie-date">
                            <span>📅</span>
                            <?php echo formatDate($sortie['date_sortie']); ?>
                        </div>
                        <h3><?php echo escape($sortie['titre']); ?></h3>
                        <p><?php echo escape(truncate($sortie['description'], 100)); ?></p>
                        <div class="sortie-places <?php echo $placesRestantes <= 0 ? 'full' : ''; ?>">
                            <?php if ($placesRestantes > 0): ?>
                                <?php echo $placesRestantes; ?>
                                place<?php echo $placesRestantes > 1 ? 's' : ''; ?>
                                disponible<?php echo $placesRestantes > 1 ? 's' : ''; ?>
                            <?php else: ?>
                                Complet
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="sortie_view.php?id=<?php echo $sortie['id']; ?>" class="btn btn-sm btn-outline">
                            Voir détails
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>