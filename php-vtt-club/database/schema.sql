-- =====================================================
-- Script SQL - Xendrazkoak VTT Club
-- Base de données MySQL
-- =====================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS xendrazkoak_vtt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xendrazkoak_vtt;

-- =====================================================
-- Table: users (Utilisateurs)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    responsable_nom_prenom VARCHAR(200) DEFAULT NULL COMMENT 'Obligatoire si age < 18',
    telephone_urgence VARCHAR(20) NOT NULL,
    commentaire TEXT DEFAULT NULL,
    role ENUM('visiteur', 'utilisateur', 'bureau', 'admin') DEFAULT 'utilisateur',
    statut_compte ENUM('en_attente', 'valide', 'refuse', 'desactive') DEFAULT 'en_attente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table: club_infos (Informations du club)
-- =====================================================
CREATE TABLE club_infos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_club VARCHAR(200) NOT NULL,
    adresse TEXT NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    jours_inscription VARCHAR(255) NOT NULL,
    youtube_url VARCHAR(500) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table: actualites (Actualités du club)
-- =====================================================
CREATE TABLE actualites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    statut ENUM('publie', 'archive') DEFAULT 'publie',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: sorties (Sorties VTT)
-- =====================================================
CREATE TABLE sorties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    date_sortie DATE NOT NULL,
    nombre_places INT NOT NULL DEFAULT 20,
    encadrants VARCHAR(500) DEFAULT NULL,
    accompagnateurs VARCHAR(500) DEFAULT NULL,
    statut ENUM('ouverte', 'complete', 'annulee', 'archivee') DEFAULT 'ouverte',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: sortie_inscriptions (Inscriptions aux sorties)
-- =====================================================
CREATE TABLE sortie_inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sortie_id INT NOT NULL,
    user_id INT NOT NULL,
    statut ENUM('inscrit', 'annule') DEFAULT 'inscrit',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sortie_id) REFERENCES sorties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inscription (sortie_id, user_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table: annonce_categories (Catégories d'annonces)
-- =====================================================
CREATE TABLE annonce_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =====================================================
-- Table: annonces (Annonces de matériel)
-- =====================================================
CREATE TABLE annonces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    categorie_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    prix DECIMAL(10,2) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    statut ENUM('en_attente', 'publiee', 'restreinte', 'archivee') DEFAULT 'en_attente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES annonce_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: annonce_images (Images des annonces)
-- =====================================================
CREATE TABLE annonce_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    annonce_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: notifications
-- =====================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: messages (Messagerie interne)
-- =====================================================
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table: logs_statuts (Historique des changements de statut)
-- =====================================================
CREATE TABLE logs_statuts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_cible VARCHAR(50) NOT NULL,
    element_id INT NOT NULL,
    ancien_statut VARCHAR(50) DEFAULT NULL,
    nouveau_statut VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DONNÉES DE DÉMONSTRATION
-- =====================================================

-- Catégories d'annonces
INSERT INTO annonce_categories (nom) VALUES 
('Vente'),
('Prêt'),
('Recherche');

-- Informations du club
INSERT INTO club_infos (nom_club, adresse, telephone, jours_inscription, youtube_url) VALUES 
('Xendrazkoak VTT', '12 Rue du Vélo, 64100 Bayonne', '05 59 12 34 56', 'Mercredi 18h-20h, Samedi 10h-12h', 'https://www.youtube.com/embed/dQw4w9WgXcQ');

-- Utilisateur administrateur par défaut (mot de passe: admin123)
INSERT INTO users (nom, prenom, email, password_hash, age, telephone_urgence, role, statut_compte) VALUES 
('Admin', 'Super', 'admin@xendrazkoak.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 35, '0600000000', 'admin', 'valide');

-- Utilisateur bureau (mot de passe: bureau123)
INSERT INTO users (nom, prenom, email, password_hash, age, telephone_urgence, role, statut_compte) VALUES 
('Dupont', 'Marie', 'marie.dupont@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 42, '0611111111', 'bureau', 'valide');

-- Utilisateur standard validé (mot de passe: user123)
INSERT INTO users (nom, prenom, email, password_hash, age, telephone_urgence, role, statut_compte) VALUES 
('Martin', 'Pierre', 'pierre.martin@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 28, '0622222222', 'utilisateur', 'valide');

-- Utilisateur en attente (mot de passe: attente123)
INSERT INTO users (nom, prenom, email, password_hash, age, telephone_urgence, role, statut_compte) VALUES 
('Petit', 'Lucas', 'lucas.petit@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 16, '0633333333', 'utilisateur', 'en_attente', 'Durand Jean');

-- Actualités de démonstration
INSERT INTO actualites (titre, contenu, statut, created_by) VALUES 
('Bienvenue sur le nouveau site !', 'Nous sommes ravis de vous présenter le nouveau site web du club Xendrazkoak VTT. Vous y trouverez toutes les informations sur nos activités, le planning des sorties et bien plus encore !', 'publie', 1),
('Sortie inaugurale de la saison', 'La première sortie de la nouvelle saison aura lieu le premier samedi du mois. Rendez-vous à 9h devant le local du club. N''oubliez pas votre équipement complet !', 'publie', 1),
('Nouveau partenariat avec VéloPro', 'Nous avons le plaisir de vous annoncer notre nouveau partenariat avec le magasin VéloPro. Les membres du club bénéficieront de 15% de réduction sur tout le matériel.', 'publie', 2);

-- Sorties de démonstration
INSERT INTO sorties (titre, description, date_sortie, nombre_places, encadrants, accompagnateurs, statut, created_by) VALUES 
('Sortie découverte La Rhune', 'Parcours accessible à tous les niveaux. Magnifique vue sur la côte basque. Prévoir pique-nique.', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 15, 'Jean Dupont, Marie Martin', 'Pierre Durand', 'ouverte', 1),
('Randonnée forêt d''Iraty', 'Parcours intermédiaire dans la magnifique forêt d''Iraty. Environ 35km et 800m de dénivelé.', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 12, 'Marie Martin', 'Lucas Petit, Sophie Bernard', 'ouverte', 2),
('Entraînement technique', 'Séance de perfectionnement technique sur terrain varié. Idéal pour progresser en descente.', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 8, 'Jean Dupont', '', 'ouverte', 1);

-- Annonces de démonstration
INSERT INTO annonces (user_id, categorie_id, titre, prix, description, statut) VALUES 
(3, 1, 'VTT Rockrider ST 530 - Très bon état', 350.00, 'VTT Rockrider ST 530 taille M, utilisé 2 saisons. Freins à disque hydrauliques, fourche suspendue. Quelques traces d''usure normales.', 'publiee'),
(3, 2, 'Prêt de porte-vélo hayon', NULL, 'Je propose de prêter mon porte-vélo hayon (2 vélos) pour les vacances. Contactez-moi pour les disponibilités.', 'publiee'),
(2, 3, 'Recherche casque intégral taille M', NULL, 'Je recherche un casque intégral taille M pour la pratique du VTT enduro. Merci de me contacter si vous avez quelque chose.', 'publiee');

-- Notifications de démonstration
INSERT INTO notifications (user_id, titre, message) VALUES 
(3, 'Bienvenue !', 'Bienvenue sur le site du club Xendrazkoak VTT. Votre compte a été validé.'),
(3, 'Nouvelle sortie disponible', 'Une nouvelle sortie "Sortie découverte La Rhune" est disponible. Inscrivez-vous vite !');
