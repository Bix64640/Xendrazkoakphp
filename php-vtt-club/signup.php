<?php
/**
 * =====================================================
 * Page d'inscription
 * =====================================================
 */

$pageTitle = 'Inscription';
require_once __DIR__ . '/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$formData = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $formData = [
            'nom' => $_POST['nom'] ?? '',
            'prenom' => $_POST['prenom'] ?? '',
            'email' => $_POST['email'] ?? '',
            'age' => $_POST['age'] ?? '',
            'responsable_nom_prenom' => $_POST['responsable_nom_prenom'] ?? '',
            'telephone_urgence' => $_POST['telephone_urgence'] ?? '',
            'commentaire' => $_POST['commentaire'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];
        
        $result = register($formData);
        
        if ($result['success']) {
            setFlash('success', $result['message']);
            redirect('login.php');
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="auth-container" style="max-width: 600px;">
    <div class="auth-card">
        <h1>Inscription</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            Après votre inscription, votre compte devra être validé par un membre du bureau avant de pouvoir vous connecter.
        </div>
        
        <form method="POST" action="" data-validate-age>
            <?php csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control" 
                           value="<?php echo escape($formData['nom'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom <span class="required">*</span></label>
                    <input type="text" id="prenom" name="prenom" class="form-control" 
                           value="<?php echo escape($formData['prenom'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Adresse email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo escape($formData['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="age">Âge <span class="required">*</span></label>
                    <input type="number" id="age" name="age" class="form-control" min="5" max="120"
                           value="<?php echo escape($formData['age'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telephone_urgence">Téléphone d'urgence <span class="required">*</span></label>
                    <input type="tel" id="telephone_urgence" name="telephone_urgence" class="form-control" 
                           value="<?php echo escape($formData['telephone_urgence'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group" id="responsable-group" style="display: none;">
                <label for="responsable_nom_prenom">Nom et prénom du responsable légal <span class="required">*</span></label>
                <input type="text" id="responsable_nom_prenom" name="responsable_nom_prenom" class="form-control" 
                       value="<?php echo escape($formData['responsable_nom_prenom'] ?? ''); ?>">
                <p class="form-text">Obligatoire pour les mineurs (moins de 18 ans)</p>
            </div>
            
            <div class="form-group">
                <label for="commentaire">Commentaire (optionnel)</label>
                <textarea id="commentaire" name="commentaire" class="form-control" rows="3" 
                          maxlength="500"><?php echo escape($formData['commentaire'] ?? ''); ?></textarea>
                <p class="form-text">Informations complémentaires, niveau de pratique, etc.</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mot de passe <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" 
                           minlength="6" required>
                    <p class="form-text">Minimum 6 caractères</p>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe <span class="required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                           minlength="6" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block btn-lg">S'inscrire</button>
        </form>
        
        <div class="auth-footer">
            <p>Déjà membre ? <a href="login.php">Se connecter</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
