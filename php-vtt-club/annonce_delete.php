<?php
/**
 * =====================================================
 * Supprimer une annonce
 * =====================================================
 */

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
    setFlash('error', 'Vous n\'avez pas le droit de supprimer cette annonce.');
    redirect('annonces.php');
}

// Supprimer les images associées
$images = dbFetchAll("SELECT * FROM annonce_images WHERE annonce_id = ?", [$id]);
foreach ($images as $img) {
    deleteImage($img['image_path']);
}

// Supprimer l'annonce (les images seront supprimées en cascade)
dbExecute("DELETE FROM annonces WHERE id = ?", [$id]);

setFlash('success', 'L\'annonce a été supprimée.');
redirect('annonces.php?mes_annonces=1');
