<?php
/**
 * =====================================================
 * Export des membres en CSV
 * =====================================================
 */

require_once __DIR__ . '/auth.php';

requireBureau();

// Récupérer tous les membres validés
$membres = dbFetchAll("
    SELECT nom, prenom, email, age, responsable_nom_prenom, telephone_urgence, 
           commentaire, role, statut_compte, created_at
    FROM users 
    WHERE statut_compte = 'valide'
    ORDER BY nom, prenom
");

// Nom du fichier
$filename = 'membres_' . SITE_NAME . '_' . date('Y-m-d') . '.csv';

// Headers HTTP pour le téléchargement
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Ouvrir le flux de sortie
$output = fopen('php://output', 'w');

// BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes du CSV
$headers = [
    'Nom',
    'Prénom',
    'Email',
    'Âge',
    'Responsable légal',
    'Téléphone urgence',
    'Commentaire',
    'Rôle',
    'Statut',
    'Date inscription'
];
fputcsv($output, $headers, ';');

// Données
foreach ($membres as $membre) {
    $row = [
        $membre['nom'],
        $membre['prenom'],
        $membre['email'],
        $membre['age'],
        $membre['responsable_nom_prenom'] ?? '',
        $membre['telephone_urgence'],
        $membre['commentaire'] ?? '',
        $membre['role'],
        $membre['statut_compte'],
        formatDate($membre['created_at'])
    ];
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
