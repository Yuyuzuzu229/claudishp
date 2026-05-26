CREATE DATABASE IF NOT EXISTS claudishop DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE claudishop ;

DROP TABLE IF EXISTS adresse;
DROP TABLE IF EXISTS notification;
DROP TABLE IF EXISTS avis;
DROP TABLE IF EXISTS livraison;
DROP TABLE IF EXISTS livreur;
DROP TABLE IF EXISTS paiement;
DROP TABLE IF EXISTS ligne_commande;
DROP TABLE IF EXISTS commande;
DROP TABLE IF EXISTS ligne_panier;
DROP TABLE IF EXISTS panier;
DROP TABLE IF EXISTS produit;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS hero_collection;
DROP TABLE IF EXISTS configuration_boutique;
DROP TABLE IF EXISTS zone_livraison;
DROP TABLE IF EXISTS utilisateur;

CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    role ENUM('admin','gestionnaire','responsable_logistique','livreur','user') NOT NULL DEFAULT 'user',
    est_actif BOOLEAN NOT NULL DEFAULT TRUE,
    google_id VARCHAR(255) DEFAULT NULL UNIQUE,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expire DATETIME DEFAULT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    statut BOOLEAN NOT NULL DEFAULT TRUE,
    parent_id INT DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categorie(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE produit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(12,2) NOT NULL,
    solde_prix DECIMAL(12,2) DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    photo VARCHAR(255) DEFAULT 'default.jpg',
    images TEXT DEFAULT NULL,
    categorie_id INT NOT NULL,
    statut BOOLEAN NOT NULL DEFAULT TRUE,
    taille_disponible VARCHAR(100) DEFAULT NULL,
    couleur VARCHAR(50) DEFAULT NULL,
    matiere VARCHAR(100) DEFAULT NULL,
    note_moyenne DECIMAL(2,1) DEFAULT 0.0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categorie(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE panier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    est_actif BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ligne_panier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panier_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(12,2) NOT NULL,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (panier_id) REFERENCES panier(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produit(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE zone_livraison (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    tarif DECIMAL(12,2) NOT NULL DEFAULT 0,
    statut BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant_total DECIMAL(12,2) NOT NULL,
    statut ENUM('Confirmée','En préparation','En route','En livraison','Livrée','Annulée') NOT NULL DEFAULT 'Confirmée',
    mode_retrait ENUM('livraison','retrait_magasin','point_relais') NOT NULL DEFAULT 'livraison',
    adresse_livraison TEXT DEFAULT NULL,
    nom_complet VARCHAR(255) DEFAULT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    instructions TEXT DEFAULT NULL,
    latitude_client DECIMAL(10,7) DEFAULT NULL,
    longitude_client DECIMAL(10,7) DEFAULT NULL,
    precision_geoloc INT DEFAULT NULL,
    id_zone INT DEFAULT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (id_zone) REFERENCES zone_livraison(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ligne_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produit(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE paiement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    mode ENUM('MTN Mobile Money','Moov Money','Carte','À la livraison') NOT NULL,
    statut ENUM('En attente','Confirmé','Échoué') NOT NULL DEFAULT 'En attente',
    date_paiement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_transaction VARCHAR(100) DEFAULT NULL,
    telephone_paiement VARCHAR(20) DEFAULT NULL,
    token VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE livreur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(180) DEFAULT NULL,
    mot_de_passe VARCHAR(255) DEFAULT NULL,
    vehicule VARCHAR(100) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    statut ENUM('Disponible','En livraison','Indisponible') NOT NULL DEFAULT 'Disponible',
    type_livraison ENUM('simple','multiple') NOT NULL DEFAULT 'simple',
    zone_affectation VARCHAR(255) DEFAULT NULL,
    date_embauche DATE DEFAULT NULL,
    est_actif BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE livraison (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    livreur_id INT DEFAULT NULL,
    zone_id INT DEFAULT NULL,
    frais DECIMAL(12,2) NOT NULL DEFAULT 0,
    creneau ENUM('matin','apres_midi','soir') DEFAULT NULL,
    statut ENUM('En attente','Prêt à expédier','En cours','Livrée','Annulée','Échouée') NOT NULL DEFAULT 'En attente',
    date_prevue DATE DEFAULT NULL,
    date_livraison DATETIME DEFAULT NULL,
    adresse TEXT,
    signature DECIMAL(10,2) DEFAULT NULL,
    precision_livraison INT DEFAULT NULL,
    FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE,
    FOREIGN KEY (livreur_id) REFERENCES livreur(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zone_livraison(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    note INT NOT NULL CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    statut ENUM('Publié','En modération','Refusé') NOT NULL DEFAULT 'En modération',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produit(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    canal ENUM('SMS','WhatsApp','Email','In-app') NOT NULL DEFAULT 'In-app',
    statut ENUM('Envoyé','Échec','Non lue','Lue') NOT NULL DEFAULT 'Non lue',
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commande_id INT DEFAULT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE adresse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    quartier VARCHAR(150) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    point_repere VARCHAR(255) DEFAULT NULL,
    est_principale BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE configuration_boutique (
    id INT NOT NULL DEFAULT 1,
    latitude DECIMAL(10,7) NOT NULL DEFAULT 0.0000000,
    longitude DECIMAL(10,7) NOT NULL DEFAULT 0.0000000,
    adresse VARCHAR(255) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuration_boutique (id, latitude, longitude, adresse)
VALUES (1, 6.3650, 2.4330, 'Wologede, Mairie, Cotonou')
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE hero_collection (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre VARCHAR(200) NOT NULL,
    tag VARCHAR(100) NOT NULL DEFAULT '',
    type ENUM('categorie','produits') NOT NULL DEFAULT 'categorie',
    categorie_id INT UNSIGNED DEFAULT NULL,
    produit_ids TEXT DEFAULT NULL,
    statut TINYINT(1) NOT NULL DEFAULT 1,
    ordre INT NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hero_cat (categorie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO hero_collection (titre, tag, type, categorie_id, produit_ids, statut, ordre) VALUES
('Collection Printemps', 'Tendance', 'categorie', 1, NULL, 1, 0),
('Nouvelle Saison', 'Nouveauté', 'categorie', 2, NULL, 1, 1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role) VALUES
('Admin', 'Super', 'admin@claudishop.com', '$2y$10$kNXT/dB6x31v8Rt5DYpq.uxqPrDOpIm363i1NQZETQfMmNXFauDkS', '+22877000000', 'admin');

INSERT INTO categorie (nom, description) VALUES
('Femme', 'Vêtements et accessoires pour femme'),
('Homme', 'Vêtements et accessoires pour homme'),
('Enfant', 'Vêtements et accessoires pour enfant'),
('Accessoires', 'Sacs, ceintures, bijoux, lunettes'),
('Chaussures', 'Chaussures tous âges');

INSERT INTO zone_livraison (nom, description, tarif) VALUES
('Cotonou - Zone 1', 'Akpakpa, Fidjrossè, Agblangandan', 2000),
('Cotonou - Zone 2', 'Védoko, Cadjehoun, Haie Vive', 2000),
('Cotonou - Zone 3', 'Mairie, Gbegamey, Missessin', 2000),
('Cotonou - Zone 4', 'Dantokpa, Centre-ville, St Michel', 2000),
('Abomey-Calavi - Zone 1', 'Calavi Centre, UAC', 2500),
('Abomey-Calavi - Zone 2', 'Zogbadje, Kpota, Tankpè', 2500),
('Abomey-Calavi - Zone 3', 'Godomey, Togoudo', 2500),
('Parakou', 'Parakou Centre et périphérie', 3500),
('Porto-Novo - Zone 1', 'Porto-Novo Centre', 2500),
('Porto-Novo - Zone 2', 'Dokèkpo, Adjara', 2500),
('Bohicon / Abomey', 'Bohicon et Abomey', 3000),
('Ouidah / Grand-Popo', 'Ouidah Grand-Popo', 3500),
('Lokossa / Dogbo', 'Lokossa Dogbo', 3500),
('Cové / Dassa / Savalou', 'Cové Dassa Savalou', 3500),
('Kandi / Malanville', 'Kandi Malanville', 4500),
('Natitingou / Djougou', 'Natitingou Djougou', 4000);

INSERT INTO produit (nom, description, prix, stock, categorie_id, taille_disponible, couleur) VALUES
('Robe Wax fleurie', 'Magnifique robe en wax africain', 18500, 18, 1, 'S à XL', 'Multicolore'),
('Chemise lin blanc', 'Chemise en lin blanc élégante', 12000, 35, 2, 'S à XXL', 'Blanc'),
('Ensemble Wax enfant', 'Ensemble wax pour enfant', 8900, 25, 3, '2 à 12 ans', 'Multicolore'),
('Sac à main cuir', 'Sac à main en cuir véritable', 22000, 12, 4, 'Unique', 'Marron');

INSERT INTO livreur (nom, telephone, email, vehicule, statut, zone_affectation) VALUES
('Moussa T.', '+22890123456', 'moussa.t@claudishop.com', 'Moto', 'Disponible', 'Cotonou Zone A, Zone B'),
('Jennes L.', '+22890567890', 'jennes.l@claudishop.com', 'Voiture', 'En livraison', 'Zone B, Zone A'),
('Balnava A.', '+22890234567', 'balnava.a@claudishop.com', 'Moto', 'Indisponible', 'Zone A, Zone B');
