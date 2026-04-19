<?php
/**
 * =====================================================
 * Gestion des utilisateurs
 * =====================================================
 */

$pageTitle = 'Gestion des utilisateurs';
require_once __DIR__ . '/auth.php';

requireBureau();

$user = getCurrentUser();
$isAdmin = isAdmin();

// Actions
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action && $id) {
    $targetUser = getUserById($id);
    
    if ($targetUser) {
        // Un membre du bureau ne peut pas modifier bureau/admin
        $canModify = $isAdmin || !in_array($targetUser['role'], [ROLE_BUREAU, ROLE_ADMIN]);
        
        if ($canModify) {
            switch ($action) {
                case 'validate':
                    updateUserStatut($id, STATUT_VALIDE, $user['id']);
                    setFlash('success', "Le compte de {$targetUser['prenom']} {$targetUser['nom']} a été validé.");
                    break;
                    
                case 'refuse':
                    updateUserStatut($id, STATUT_REFUSE, $user['id']);
                    setFlash('success', "Le compte de {$targetUser['prenom']} {$targetUser['nom']} a été refusé.");
                    break;
                    
                case 'deactivate':
                    updateUserStatut($id, STATUT_DESACTIVE, $user['id']);
                    setFlash('success', "Le compte de {$targetUser['prenom']} {$targetUser['nom']} a été désactivé.");
                    break;
                    
                case 'reactivate':
                    updateUserStatut($id, STATUT_VALIDE, $user['id']);
                    setFlash('success', "Le compte de {$targetUser['prenom']} {$targetUser['nom']} a été réactivé.");
                    break;
            }
        } else {
            setFlash('error', 'Vous n\'avez pas les droits pour modifier cet utilisateur.');
        }
    }
    
    redirect('manage_users.php' . (!empty($_GET['statut']) ? '?statut=' . $_GET['statut'] : ''));
}

// Filtres
$filterStatut = $_GET['statut'] ?? '';
$filterRole = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Construire la requête
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($filterStatut) {
    $sql .= " AND statut_compte = ?";
    $params[] = $filterStatut;
}

if ($filterRole) {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}

if ($search) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Un membre du bureau ne voit pas les autres bureaux/admins à modifier
if (!$isAdmin) {
    $sql .= " AND role NOT IN ('bureau', 'admin')";
}

$sql .= " ORDER BY created_at DESC";

$users = dbFetchAll($sql, $params);

require_once __DIR__ . '/header.php';
?>

<div class="page-header flex flex-between items-center">
    <div>
        <h1>Gestion des utilisateurs</h1>
        <p><?php echo count($users); ?> utilisateur<?php echo count($users) > 1 ? 's' : ''; ?></p>
    </div>
    <a href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'bureau_dashboard.php'; ?>" class="btn btn-outline">← Retour</a>
</div>

<!-- Filtres -->
<div class="filters">
    <form method="GET" action="">
        <div class="form-group">
            <label for="search">Recherche</label>
            <input type="text" id="search" name="search" class="form-control" 
                   placeholder="Nom, prénom, email..." value="<?php echo escape($search); ?>">
        </div>
        
        <div class="form-group">
            <label for="statut">Statut</label>
            <select name="statut" id="statut" class="form-control">
                <option value="">Tous</option>
                <option value="en_attente" <?php echo $filterStatut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                <option value="valide" <?php echo $filterStatut === 'valide' ? 'selected' : ''; ?>>Validé</option>
                <option value="refuse" <?php echo $filterStatut === 'refuse' ? 'selected' : ''; ?>>Refusé</option>
                <option value="desactive" <?php echo $filterStatut === 'desactive' ? 'selected' : ''; ?>>Désactivé</option>
            </select>
        </div>
        
        <?php if ($isAdmin): ?>
        <div class="form-group">
            <label for="role">Rôle</label>
            <select name="role" id="role" class="form-control">
                <option value="">Tous</option>
                <option value="utilisateur" <?php echo $filterRole === 'utilisateur' ? 'selected' : ''; ?>>Utilisateur</option>
                <option value="bureau" <?php echo $filterRole === 'bureau' ? 'selected' : ''; ?>>Bureau</option>
                <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="manage_users.php" class="btn btn-outline">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table" id="users-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Âge</th>
                    <th>Téléphone urgence</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php $canModify = $isAdmin || !in_array($u['role'], [ROLE_BUREAU, ROLE_ADMIN]); ?>
                        <tr>
                            <td>
                                <strong><?php echo escape($u['prenom'] . ' ' . $u['nom']); ?></strong>
                                <?php if ($u['age'] < 18 && $u['responsable_nom_prenom']): ?>
                                    <br><small class="text-muted">Resp: <?php echo escape($u['responsable_nom_prenom']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($u['email']); ?></td>
                            <td><?php echo $u['age']; ?> ans</td>
                            <td><?php echo escape($u['telephone_urgence']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $u['role'] === 'admin' ? 'error' : 
                                        ($u['role'] === 'bureau' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $u['statut_compte'] === 'valide' ? 'success' : 
                                        ($u['statut_compte'] === 'en_attente' ? 'warning' : 'error'); 
                                ?>">
                                    <?php echo $u['statut_compte']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($u['created_at']); ?></td>
                            <td class="actions">
                                <?php if ($canModify): ?>
                                    <?php if ($u['statut_compte'] === 'en_attente'): ?>
                                        <a href="?action=validate&id=<?php echo $u['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                           class="btn btn-sm btn-primary">Valider</a>
                                        <a href="?action=refuse&id=<?php echo $u['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                           class="btn btn-sm btn-danger">Refuser</a>
                                    <?php elseif ($u['statut_compte'] === 'valide'): ?>
                                        <a href="?action=deactivate&id=<?php echo $u['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           data-confirm="Désactiver ce compte ?">Désactiver</a>
                                    <?php elseif (in_array($u['statut_compte'], ['refuse', 'desactive'])): ?>
                                        <a href="?action=reactivate&id=<?php echo $u['id']; ?>&statut=<?php echo $filterStatut; ?>" 
                                           class="btn btn-sm btn-primary">Réactiver</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($isAdmin && $u['id'] != $user['id']): ?>
                                        <a href="admin_edit_user.php?id=<?php echo $u['id']; ?>" 
                                           class="btn btn-sm btn-outline">Modifier</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
