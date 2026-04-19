<?php
/**
 * =====================================================
 * Modifier un utilisateur (admin)
 * =====================================================
 */

$pageTitle = 'Modifier un utilisateur';
require_once __DIR__ . '/auth.php';

requireAdmin();

$currentUser = getCurrentUser();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Utilisateur non trouvé.');
    redirect('manage_users.php');
}

$editUser = getUserById($id);

if (!$editUser) {
    setFlash('error', 'Utilisateur non trouvé.');
    redirect('manage_users.php');
}

// Ne pas permettre de modifier son propre compte ici
if ($editUser['id'] === $currentUser['id']) {
    setFlash('error', 'Utilisez votre profil pour modifier vos propres informations.');
    redirect('manage_users.php');
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $role = $_POST['role'] ?? $editUser['role'];
    $statut = $_POST['statut_compte'] ?? $editUser['statut_compte'];
    
    // Validation du rôle
    $validRoles = [ROLE_UTILISATEUR, ROLE_BUREAU, ROLE_ADMIN];
    if (!in_array($role, $validRoles)) {
        $error = 'Rôle invalide.';
    }
    
    // Validation du statut
    $validStatuts = [STATUT_EN_ATTENTE, STATUT_VALIDE, STATUT_REFUSE, STATUT_DESACTIVE];
    if (!in_array($statut, $validStatuts)) {
        $error = 'Statut invalide.';
    }
    
    if (!$error) {
        // Mettre à jour le rôle si changé
        if ($role !== $editUser['role']) {
            updateUserRole($id, $role, $currentUser['id']);
        }
        
        // Mettre à jour le statut si changé
        if ($statut !== $editUser['statut_compte']) {
            updateUserStatut($id, $statut, $currentUser['id']);
        }
        
        setFlash('success', 'L\'utilisateur a été mis à jour.');
        redirect('manage_users.php');
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Modifier l'utilisateur</h1>
    <p><?php echo escape($editUser['prenom'] . ' ' . $editUser['nom']); ?></p>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <!-- Informations en lecture seule -->
        <h3>Informations du compte</h3>
        <table class="table mb-3">
            <tr>
                <th>Nom complet</th>
                <td><?php echo escape($editUser['prenom'] . ' ' . $editUser['nom']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo escape($editUser['email']); ?></td>
            </tr>
            <tr>
                <th>Âge</th>
                <td><?php echo $editUser['age']; ?> ans</td>
            </tr>
            <?php if ($editUser['responsable_nom_prenom']): ?>
            <tr>
                <th>Responsable légal</th>
                <td><?php echo escape($editUser['responsable_nom_prenom']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Téléphone urgence</th>
                <td><?php echo escape($editUser['telephone_urgence']); ?></td>
            </tr>
            <tr>
                <th>Inscription</th>
                <td><?php echo formatDate($editUser['created_at'], true); ?></td>
            </tr>
            <?php if ($editUser['commentaire']): ?>
            <tr>
                <th>Commentaire</th>
                <td><?php echo escape($editUser['commentaire']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        
        <!-- Formulaire de modification -->
        <form method="POST" action="">
            <?php csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="role">Rôle</label>
                    <select name="role" id="role" class="form-control">
                        <option value="utilisateur" <?php echo $editUser['role'] === 'utilisateur' ? 'selected' : ''; ?>>
                            Utilisateur
                        </option>
                        <option value="bureau" <?php echo $editUser['role'] === 'bureau' ? 'selected' : ''; ?>>
                            Membre du bureau
                        </option>
                        <option value="admin" <?php echo $editUser['role'] === 'admin' ? 'selected' : ''; ?>>
                            Administrateur
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="statut_compte">Statut du compte</label>
                    <select name="statut_compte" id="statut_compte" class="form-control">
                        <option value="en_attente" <?php echo $editUser['statut_compte'] === 'en_attente' ? 'selected' : ''; ?>>
                            En attente
                        </option>
                        <option value="valide" <?php echo $editUser['statut_compte'] === 'valide' ? 'selected' : ''; ?>>
                            Validé
                        </option>
                        <option value="refuse" <?php echo $editUser['statut_compte'] === 'refuse' ? 'selected' : ''; ?>>
                            Refusé
                        </option>
                        <option value="desactive" <?php echo $editUser['statut_compte'] === 'desactive' ? 'selected' : ''; ?>>
                            Désactivé
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="manage_users.php" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
