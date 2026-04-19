<?php
/**
 * =====================================================
 * Page de connexion
 * =====================================================
 */

$pageTitle = 'Connexion';
require_once __DIR__ . '/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            $result = login($email, $password);
            
            if ($result['success']) {
                setFlash('success', $result['message']);
                redirect('dashboard.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Connexion</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php csrfField(); ?>
            
            <div class="form-group">
                <label for="email">Adresse email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo escape($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
        
        <div class="auth-footer">
            <p>Pas encore membre ? <a href="signup.php">S'inscrire</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
