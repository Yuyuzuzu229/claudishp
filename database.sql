-- ============================================================
-- CLAUDISHOP - Schéma de base de données MySQL/MariaDB
-- Version : 1.1.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `claudishop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `claudishop`;

-- ============================================================
-- TABLE : utilisateur
-- ============================================================
CREATE TABLE `utilisateur` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`              VARCHAR(100) NOT NULL,
  `prenom`           VARCHAR(100) NOT NULL,
  `email`            VARCHAR(180) NOT NULL UNIQUE,
  `mot_de_passe`     VARCHAR(255) NOT NULL,
  `telephone`        VARCHAR(20) DEFAULT NULL,
  `role`             ENUM('client','admin','gestionnaire','livreur','support','comptable','logistique') NOT NULL DEFAULT 'client',
  `est_actif`        TINYINT(1) NOT NULL DEFAULT 1,
  `reset_token`      VARCHAR(64) DEFAULT NULL,
  `reset_expire`     DATETIME DEFAULT NULL,
  `derniere_connexion` DATETIME DEFAULT NULL,
  `date_inscription` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : categorie
-- ============================================================
CREATE TABLE `categorie` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `statut`      TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : produit
-- ============================================================
CREATE TABLE `produit` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`             VARCHAR(200) NOT NULL,
  `description`     TEXT DEFAULT NULL,
  `prix`            DECIMAL(12,2) NOT NULL,
  `prix_promo`      DECIMAL(12,2) DEFAULT NULL,
  `stock`           INT NOT NULL DEFAULT 0,
  `qte_min`         INT NOT NULL DEFAULT 1,
  `qte_alerte`      INT NOT NULL DEFAULT 5,
  `sku`             VARCHAR(50) DEFAULT NULL UNIQUE,
  `categorie_id`    INT UNSIGNED NOT NULL,
  `statut`          TINYINT(1) NOT NULL DEFAULT 1,
  `afficher_details` TINYINT(1) NOT NULL DEFAULT 1,
  `mode_paiement`   ENUM('mtn','moov','tous') NOT NULL DEFAULT 'tous',
  `taille_disponible` VARCHAR(100) DEFAULT NULL,
  `couleur`         VARCHAR(100) DEFAULT NULL,
  `matiere`         VARCHAR(100) DEFAULT NULL,
  `photo`           VARCHAR(255) DEFAULT NULL,
  `date_creation`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_categorie` (`categorie_id`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : produit_image
-- ============================================================
CREATE TABLE `produit_image` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `produit_id`  INT UNSIGNED NOT NULL,
  `chemin`      VARCHAR(255) NOT NULL,
  `ordre`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produit_img` (`produit_id`),
  CONSTRAINT `fk_img_produit` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : zone_livraison
-- ============================================================
CREATE TABLE `zone_livraison` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `communes`    TEXT DEFAULT NULL,
  `frais`       DECIMAL(10,2) NOT NULL DEFAULT 0,
  `delai_min`   INT DEFAULT 1,
  `delai_max`   INT DEFAULT 4,
  `statut`      TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : livreur
-- ============================================================
CREATE TABLE `livreur` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`           VARCHAR(100) NOT NULL,
  `telephone`     VARCHAR(20) NOT NULL,
  `email`         VARCHAR(180) DEFAULT NULL UNIQUE,
  `mot_de_passe`  VARCHAR(255) NOT NULL,
  `vehicule`      ENUM('moto','voiture','tricycle','velo','pied') NOT NULL DEFAULT 'moto',
  `type_livraison` ENUM('express','standard','tous') NOT NULL DEFAULT 'tous',
  `statut`        ENUM('disponible','en_livraison','inactif') NOT NULL DEFAULT 'disponible',
  `est_actif`     TINYINT(1) NOT NULL DEFAULT 1,
  `photo`         VARCHAR(255) DEFAULT NULL,
  `note`          TEXT DEFAULT NULL,
  `date_embauche` DATE DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : livreur_zone (pivot)
-- ============================================================
CREATE TABLE `livreur_zone` (
  `livreur_id` INT UNSIGNED NOT NULL,
  `zone_id`    INT UNSIGNED NOT NULL,
  PRIMARY KEY (`livreur_id`,`zone_id`),
  CONSTRAINT `fk_lz_livreur` FOREIGN KEY (`livreur_id`) REFERENCES `livreur` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lz_zone`    FOREIGN KEY (`zone_id`)    REFERENCES `zone_livraison` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : panier
-- ============================================================
CREATE TABLE `panier` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `est_actif`    TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_panier_user` (`utilisateur_id`),
  CONSTRAINT `fk_panier_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : ligne_panier
-- ============================================================
CREATE TABLE `ligne_panier` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `panier_id`   INT UNSIGNED NOT NULL,
  `produit_id`  INT UNSIGNED NOT NULL,
  `quantite`    INT NOT NULL DEFAULT 1,
  `taille`      VARCHAR(20) DEFAULT NULL,
  `couleur`     VARCHAR(50) DEFAULT NULL,
  `prix_unitaire` DECIMAL(12,2) NOT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lp_panier`  (`panier_id`),
  KEY `idx_lp_produit` (`produit_id`),
  CONSTRAINT `fk_lp_panier`  FOREIGN KEY (`panier_id`)  REFERENCES `panier` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lp_produit` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : commande
-- ============================================================
CREATE TABLE `commande` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`          VARCHAR(20) NOT NULL UNIQUE,
  `utilisateur_id`     INT UNSIGNED NOT NULL,
  `date_commande`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mode_retrait`       ENUM('livraison','boutique') NOT NULL DEFAULT 'livraison',
  `montant_total`      DECIMAL(12,2) NOT NULL,
  `adresse_livraison`  TEXT DEFAULT NULL,
  `latitude_client`    DECIMAL(10,7) DEFAULT NULL,
  `longitude_client`   DECIMAL(10,7) DEFAULT NULL,
  `precision_gps`      FLOAT DEFAULT NULL,
  `zone_id`            INT UNSIGNED DEFAULT NULL,
  `statut`             ENUM('En attente','Confirmée','En préparation','En route','Livrée','Annulée') NOT NULL DEFAULT 'En attente',
  `instructions`       TEXT DEFAULT NULL,
  `date_creation`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cmd_user`   (`utilisateur_id`),
  KEY `idx_cmd_zone`   (`zone_id`),
  KEY `idx_cmd_statut` (`statut`),
  CONSTRAINT `fk_cmd_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cmd_zone` FOREIGN KEY (`zone_id`) REFERENCES `zone_livraison` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : ligne_commande
-- ============================================================
CREATE TABLE `ligne_commande` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commande_id`  INT UNSIGNED NOT NULL,
  `produit_id`   INT UNSIGNED NOT NULL,
  `quantite`     INT NOT NULL DEFAULT 1,
  `taille`       VARCHAR(20) DEFAULT NULL,
  `couleur`      VARCHAR(50) DEFAULT NULL,
  `prix_unitaire` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lc_commande` (`commande_id`),
  KEY `idx_lc_produit`  (`produit_id`),
  CONSTRAINT `fk_lc_commande` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lc_produit`  FOREIGN KEY (`produit_id`)  REFERENCES `produit` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : paiement
-- ============================================================
CREATE TABLE `paiement` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commande_id`         INT UNSIGNED NOT NULL,
  `montant`             DECIMAL(12,2) NOT NULL,
  `mode`                ENUM('mtn_momo','moov_money') NOT NULL,
  `telephone_paiement`  VARCHAR(20) DEFAULT NULL,
  `statut`              ENUM('en_attente','Confirmé','Échoué','Remboursé') NOT NULL DEFAULT 'en_attente',
  `reference_transaction` VARCHAR(100) DEFAULT NULL UNIQUE,
  `date_paiement`       DATETIME DEFAULT NULL,
  `date_creation`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_paiement_commande` (`commande_id`),
  CONSTRAINT `fk_paiement_commande` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : livraison
-- ============================================================
CREATE TABLE `livraison` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commande_id`   INT UNSIGNED NOT NULL,
  `livreur_id`    INT UNSIGNED DEFAULT NULL,
  `zone_id`       INT UNSIGNED DEFAULT NULL,
  `frais`         DECIMAL(10,2) NOT NULL DEFAULT 0,
  `creneau`       VARCHAR(100) DEFAULT NULL,
  `statut`        ENUM('En attente','Prêt à expédier','En cours','Livrée','Annulée','Échouée') NOT NULL DEFAULT 'En attente',
  `date_prevue`   DATE DEFAULT NULL,
  `date_livraison` DATETIME DEFAULT NULL,
  `signature`     VARCHAR(255) DEFAULT NULL,
  `prevision`     TEXT DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_liv_commande` (`commande_id`),
  KEY `idx_liv_livreur`  (`livreur_id`),
  KEY `idx_liv_zone`     (`zone_id`),
  CONSTRAINT `fk_liv_commande` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_liv_livreur`  FOREIGN KEY (`livreur_id`)  REFERENCES `livreur` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_liv_zone`     FOREIGN KEY (`zone_id`)      REFERENCES `zone_livraison` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : avis
-- ============================================================
CREATE TABLE `avis` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `produit_id`   INT UNSIGNED NOT NULL,
  `note`         TINYINT UNSIGNED NOT NULL CHECK (`note` BETWEEN 1 AND 5),
  `commentaire`  TEXT DEFAULT NULL,
  `statut`       ENUM('en_moderation','Publié','Refusé') NOT NULL DEFAULT 'en_moderation',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_avis_user`    (`utilisateur_id`),
  KEY `idx_avis_produit` (`produit_id`),
  CONSTRAINT `fk_avis_user`    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_produit` FOREIGN KEY (`produit_id`)     REFERENCES `produit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : notification
-- ============================================================
CREATE TABLE `notification` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED DEFAULT NULL,
  `commande_id`    INT UNSIGNED DEFAULT NULL,
  `canal`          ENUM('sms','email','push','interne') NOT NULL DEFAULT 'interne',
  `titre`          VARCHAR(200) NOT NULL,
  `message`        TEXT NOT NULL,
  `statut`         ENUM('Non lue','Lue','Archivée','Envoyé') NOT NULL DEFAULT 'Non lue',
  `date_envoi`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`     (`utilisateur_id`),
  KEY `idx_notif_commande` (`commande_id`),
  CONSTRAINT `fk_notif_user`     FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_commande` FOREIGN KEY (`commande_id`)    REFERENCES `commande` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DONNÉES DE DÉMONSTRATION
-- ============================================================

INSERT INTO `categorie` (`nom`,`description`,`statut`) VALUES
('Femme','Vêtements et accessoires femme',1),
('Homme','Vêtements et accessoires homme',1),
('Enfant','Mode enfant 0-16 ans',1),
('Accessoires','Sacs, ceintures, lunettes, bijoux',1),
('Beauté & Santé','Soins et cosmétiques',1),
('Soldes','Articles en promotion',0);

INSERT INTO `zone_livraison` (`nom`,`description`,`frais`,`delai_min`,`delai_max`,`statut`) VALUES
('Cotonou Zone 1','Centre-ville et Plateau',1500,1,2,1),
('Cotonou Zone 2','Akpakpa, Aidjèdo, Cadjèhoun',2000,1,3,1),
('Abomey-Calavi','Abomey-Calavi et environs',2500,2,4,1),
('Porto-Novo','Capitale administrative',3500,3,6,1),
('Parakou','Nord Bénin – ville principale',5000,6,12,0);

-- Mots de passe : admin123 / user123
INSERT INTO `utilisateur` (`nom`,`prenom`,`email`,`mot_de_passe`,`telephone`,`role`,`est_actif`) VALUES
('Admin','Super','admin@claudishop.bj','$2y$10$9a7cj1l4u24aIU/B5YaHaeNBDB2W2Bm8JEtNBhA5FxMImC8fwjNYK','+22997000000','admin',1),
('Dupont','Jean','jean@email.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','+22990123456','client',1),
('K.','Adjoua','adjoua.k@email.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','+22990123457','client',1),
('M.','Didier','didier.m@mail.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','+22990445566','gestionnaire',1);

INSERT INTO `livreur` (`nom`,`telephone`,`email`,`mot_de_passe`,`vehicule`,`statut`,`est_actif`,`date_embauche`) VALUES
('Paul Dossou','+22997112233','paul.d@mail.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','moto','disponible',1,'2025-01-01'),
('Jean-Pierre Koudé','+22997445566','jp.k@mail.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','moto','en_livraison',1,'2025-02-15'),
('Koffi Adé','+22990123456','koffi.a@mail.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','voiture','disponible',1,'2025-03-10'),
('Sènan Houngbé','+22996778899','senan.h@mail.com','$2y$10$m3zRvjYy3Omznt7t2coA0u7zhXgE5t8HUaGP4OrTEqXlJL.giO21O','moto','inactif',0,'2025-04-05');

INSERT INTO `livreur_zone` (`livreur_id`,`zone_id`) VALUES
(1,1),(2,1),(2,2),(3,3),(4,4);

INSERT INTO `produit` (`nom`,`description`,`prix`,`stock`,`qte_alerte`,`sku`,`categorie_id`,`statut`,`taille_disponible`) VALUES
('Robe Wax fleurie','Superbe robe en Wax aux couleurs vives',32500,18,5,'RWX-00142',1,1,'S,M,L,XL'),
('Chemise Homme Slim','Chemise coupe slim, tissu premium',18000,35,5,'CHS-00141',2,1,'S,M,L,XL,XXL'),
('Basket cuir mixte','Basket en cuir véritable, confortable',45000,12,5,'BSK-00140',4,1,'39,40,41,42,43'),
('Sac à main Élégance','Sac à main cuir élégant',28500,8,3,'SAC-00139',4,1,''),
('Parfum Musc Royal','Parfum oriental longue durée',22000,26,5,'PAR-00138',5,1,''),
('Ensemble Wax enfant','Ensemble deux pièces en Wax pour enfant',8900,30,10,'EWX-00135',3,1,'3,4,5,6,7,8'),
('Jean Slim Fit','Jean slim en denim de qualité',21500,19,5,'JNS-00134',2,1,'28,30,32,34,36'),
('Ceinture en cuir','Ceinture cuir véritable, boucle dorée',9900,31,5,'CEN-00133',4,1,'');

-- Réinitialiser les auto-incréments après les INSERT
-- (optionnel, InnoDB gère automatiquement)
