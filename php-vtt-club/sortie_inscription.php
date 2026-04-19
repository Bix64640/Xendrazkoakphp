<?php
/**
 * =====================================================
 * Inscription / Désinscription à une sortie
 * =====================================================
 */

require_once __DIR__ . '/auth.php';

// Vérifier la connexion et le compte validé
requireValidated();

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'inscrire';
$user = getCurrentUser();

if (!$id) {
    setFlash('error', 'Sortie non trouvée.');
    redirect('sorties.php');
}

// Récupérer la sortie
$sortie = dbFetchOne("
    SELECT s.*,
           (SELECT COUNT(*) FROM sortie_inscriptions si WHERE si.sortie_id = s.id AND si.statut = 'inscrit') as nb_inscrits
    FROM sorties s 
    WHERE s.id = ?
", [$id]);

if (!$sortie) {
    setFlash('error', 'Sortie non trouvée.');
    redirect('sorties.php');
}

// Vérifier l'inscription existante
$inscription = dbFetchOne("
    SELECT * FROM sortie_inscriptions 
    WHERE sortie_id = ? AND user_id = ?
", [$id, $user['id']]);

if ($action === 'annuler') {
    // Annuler l'inscription
    if ($inscription && $inscription['statut'] === 'inscrit') {
        $sql = "UPDATE sortie_inscriptions SET statut = 'annule' WHERE id = ?";
        dbExecute($sql, [$inscription['id']]);
        
        logStatutChange('sortie_inscriptions', $inscription['id'], 'inscrit', 'annule', $user['id']);
        
        setFlash('success', 'Votre inscription a été annulée.');
    } else {
        setFlash('error', 'Vous n\'étiez pas inscrit à cette sortie.');
    }
    
    redirect('sortie_view.php?id=' . $id);
}

// Inscription
if ($inscription && $inscription['statut'] === 'inscrit') {
    setFlash('info', 'Vous êtes déjà inscrit à cette sortie.');
    redirect('sortie_view.php?id=' . $id);
}

// Vérifier les places disponibles
$placesRestantes = $sortie['nombre_places'] - $sortie['nb_inscrits'];
if ($placesRestantes <= 0) {
    setFlash('error', 'Cette sortie est complète.');
    redirect('sortie_view.php?id=' . $id);
}

// Vérifier le statut de la sortie
if ($sortie['statut'] !== 'ouverte') {
    setFlash('error', 'Les inscriptions sont fermées pour cette sortie.');
    redirect('sortie_view.php?id=' . $id);
}

// Vérifier la date
if (strtotime($sortie['date_sortie']) < strtotime('today')) {
    setFlash('error', 'Cette sortie est passée.');
    redirect('sortie_view.php?id=' . $id);
}

// Effectuer l'inscription
if ($inscription) {
    // Réactiver une inscription annulée
    $sql = "UPDATE sortie_inscriptions SET statut = 'inscrit', created_at = NOW() WHERE id = ?";
    dbExecute($sql, [$inscription['id']]);
    logStatutChange('sortie_inscriptions', $inscription['id'], 'annule', 'inscrit', $user['id']);
} else {
    // Nouvelle inscription
    $sql = "INSERT INTO sortie_inscriptions (sortie_id, user_id, statut) VALUES (?, ?, 'inscrit')";
    dbExecute($sql, [$id, $user['id']]);
}

// Notification
createNotification($user['id'], 'Inscription confirmée', 
    "Votre inscription à la sortie \"{$sortie['titre']}\" du " . formatDate($sortie['date_sortie']) . " est confirmée.");

// Vérifier si la sortie est maintenant complète
if ($placesRestantes - 1 <= 0) {
    $sql = "UPDATE sorties SET statut = 'complete' WHERE id = ?";
    dbExecute($sql, [$id]);
    logStatutChange('sorties', $id, 'ouverte', 'complete', $user['id']);
}

setFlash('success', 'Votre inscription à la sortie "' . $sortie['titre'] . '" est confirmée !');
redirect('sortie_view.php?id=' . $id);
