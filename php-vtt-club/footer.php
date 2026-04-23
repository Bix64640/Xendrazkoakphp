        </div>
    </main>
    
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <?php if ($clubInfo): ?>
                        <p><?php echo escape($clubInfo['adresse']); ?></p>
                        <p>Tél : <?php echo escape($clubInfo['telephone']); ?></p>
                        <p>Inscriptions : <?php echo escape($clubInfo['jours_inscription']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="footer-links">
                    <h4>Liens rapides</h4>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="actualites.php">Actualités</a></li>
                        <li><a href="sorties.php">Planning des sorties</a></li>
                        <li><a href="annonces.php">Annonces</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4>Contact</h4>
                    <p>Pour toute question, n'hésitez pas à nous joindre par téléphone</a> ou  présentez-vous aux permanences</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Tous droits réservés</p>
            </div>
        </div>
    </footer>
    
    <script src="js/main.js"></script>
</body>
</html>
