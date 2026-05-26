<?php
// Inclut le fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe Livreur : gère les livreurs, leur disponibilité et leurs livraisons
class Livreur {
    // Instance de la connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère tous les livreurs triés par nom
    public function getAll() {
        // Requête simple pour lister tous les livreurs
        return $this->db->query("SELECT * FROM livreur ORDER BY nom")->fetchAll();
    }

    // Recherche des livreurs par nom, téléphone ou email (recherche partielle)
    public function search($q) {
        // Prépare une recherche avec LIKE sur trois champs
        $stmt = $this->db->prepare("SELECT * FROM livreur WHERE nom LIKE ? OR telephone LIKE ? OR email LIKE ? ORDER BY nom");
        // Ajoute les wildcards pour la recherche partielle
        $like = "%$q%";
        // Exécute avec le même motif pour les trois champs
        $stmt->execute([$like, $like, $like]);
        // Retourne toutes les correspondances
        return $stmt->fetchAll();
    }

    // Récupère un livreur par son identifiant
    public function getById($id) {
        // Prépare une requête paramétrée
        $stmt = $this->db->prepare("SELECT * FROM livreur WHERE id = ?");
        // Exécute avec l'ID
        $stmt->execute([$id]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Récupère les livreurs actuellement disponibles (actifs et sans livraison active)
    public function getDisponibles() {
        // Sélectionne les livreurs actifs qui n'ont pas de livraison en cours
        return $this->db->query("
            SELECT * FROM livreur
            WHERE est_actif = 1
            AND id NOT IN (
                SELECT livreur_id FROM livraison
                WHERE livreur_id IS NOT NULL AND statut NOT IN ('Livrée','Annulée','Échouée')
            )
            ORDER BY RAND()
        ")->fetchAll();
    }

    // Récupère un seul livreur disponible aléatoirement
    public function getPremierDisponible() {
        // Même requête que getDisponibles mais avec LIMIT 1 pour un seul résultat
        $stmt = $this->db->query("
            SELECT * FROM livreur
            WHERE est_actif = 1
            AND id NOT IN (
                SELECT livreur_id FROM livraison
                WHERE livreur_id IS NOT NULL AND statut NOT IN ('Livrée','Annulée','Échouée')
            )
            ORDER BY RAND() LIMIT 1
        ");
        // Retourne une seule ligne ou false
        return $stmt->fetch();
    }

    // Ajoute un nouveau livreur dans la base de données
    public function ajouter($nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo = null) {
        // Insère un livreur avec tous ses attributs
        $stmt = $this->db->prepare("INSERT INTO livreur (nom, telephone, email, vehicule, statut, zone_affectation, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // Exécute et retourne le résultat
        return $stmt->execute([$nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo]);
    }

    // Modifie les informations d'un livreur (nom, téléphone, email, véhicule, statut, zone, photo optionnelle)
    public function modifier($id, $nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo = null) {
        // Si une nouvelle photo est fournie
        if ($photo) {
            // Met à jour avec la photo
            $stmt = $this->db->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, vehicule=?, statut=?, zone_affectation=?, photo=? WHERE id=?");
            return $stmt->execute([$nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo, $id]);
        }
        // Sinon, met à jour sans modifier la photo
        $stmt = $this->db->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, vehicule=?, statut=?, zone_affectation=? WHERE id=?");
        return $stmt->execute([$nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $id]);
    }

    // Change le statut d'un livreur (ex: 'Disponible', 'En livraison', 'Absent')
    public function changerStatut($id, $statut) {
        // Met à jour le statut du livreur
        $stmt = $this->db->prepare("UPDATE livreur SET statut = ? WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$statut, $id]);
    }

    // Vérifie si un livreur est disponible (existe, actif et sans livraison active)
    public function estDisponible($id) {
        // Récupère le livreur par son ID
        $l = $this->getById($id);
        // Si le livreur n'existe pas ou n'est pas actif, retourne false
        if (!$l || !$l['est_actif']) return false;
        // Compte les livraisons actives de ce livreur
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM livraison WHERE livreur_id = ? AND statut NOT IN ('Livrée','Annulée','Échouée')");
        $stmt->execute([$id]);
        // Retourne true si aucune livraison active, false sinon
        return $stmt->fetchColumn() == 0;
    }

    // Bascule l'état actif/inactif d'un livreur
    public function toggleActif($id) {
        // Inverse la valeur booléenne de est_actif
        $stmt = $this->db->prepare("UPDATE livreur SET est_actif = NOT est_actif WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$id]);
    }

    // Supprime un livreur par son identifiant
    public function supprimer($id) {
        // Prépare la requête de suppression
        $stmt = $this->db->prepare("DELETE FROM livreur WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$id]);
    }

    // Récupère les livraisons actives d'un livreur avec le nom du client
    public function getLivraisonsEnCours($id) {
        // Jointure entre livraison et commande pour obtenir le nom complet du client
        $stmt = $this->db->prepare("SELECT l.*, co.nom_complet FROM livraison l JOIN commande co ON l.commande_id = co.id WHERE l.livreur_id = ? AND l.statut NOT IN ('Livrée','Annulée','Échouée')");
        // Exécute avec l'ID du livreur
        $stmt->execute([$id]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    /**
     * Synchronise la colonne statut de chaque livreur avec ses livraisons actives réelles.
     * Un livreur sans livraison active passe en 'Disponible',
     * un livreur avec au moins une livraison active passe en 'En livraison'.
     */
    public function syncStatuts() {
        // Met à jour tous les statuts en une seule requête : LEFT JOIN avec les livraisons actives
        $this->db->exec("
            UPDATE livreur l
            LEFT JOIN (
                SELECT livreur_id, COUNT(*) AS nb
                FROM livraison
                WHERE livreur_id IS NOT NULL AND statut NOT IN ('Livrée','Annulée','Échouée')
                GROUP BY livreur_id
            ) a ON a.livreur_id = l.id
            SET l.statut = CASE WHEN a.nb > 0 THEN 'En livraison' ELSE 'Disponible' END
        ");
    }
}
