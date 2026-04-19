<?php
/**
 * =====================================================
 * Déconnexion
 * =====================================================
 */

require_once __DIR__ . '/auth.php';

logout();
setFlash('success', 'Vous avez été déconnecté.');
redirect('index.php');
