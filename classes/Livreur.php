<?php
require_once __DIR__ . '/../config/database.php';

class Livreur {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM livreur ORDER BY nom")->fetchAll();
    }

    public function search($q) {
        $stmt = $this->db->prepare("SELECT * FROM livreur WHERE nom LIKE ? OR telephone LIKE ? OR email LIKE ? ORDER BY nom");
        $like = "%$q%";
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM livreur WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getDisponibles() {
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

    public function getPremierDisponible() {
        $stmt = $this->db->query("
            SELECT * FROM livreur
            WHERE est_actif = 1
            AND id NOT IN (
                SELECT livreur_id FROM livraison
                WHERE livreur_id IS NOT NULL AND statut NOT IN ('Livrée','Annulée','Échouée')
            )
            ORDER BY RAND() LIMIT 1
        ");
        return $stmt->fetch();
    }

    public function ajouter($nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo = null) {
        $stmt = $this->db->prepare("INSERT INTO livreur (nom, telephone, email, vehicule, statut, zone_affectation, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo]);
    }

    public function modifier($id, $nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo = null) {
        if ($photo) {
            $stmt = $this->db->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, vehicule=?, statut=?, zone_affectation=?, photo=? WHERE id=?");
            return $stmt->execute([$nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $photo, $id]);
        }
        $stmt = $this->db->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, vehicule=?, statut=?, zone_affectation=? WHERE id=?");
        return $stmt->execute([$nom, $telephone, $email, $vehicule, $statut, $zoneAffectation, $id]);
    }

    public function changerStatut($id, $statut) {
        $stmt = $this->db->prepare("UPDATE livreur SET statut = ? WHERE id = ?");
        return $stmt->execute([$statut, $id]);
    }

    public function estDisponible($id) {
        $l = $this->getById($id);
        if (!$l || !$l['est_actif']) return false;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM livraison WHERE livreur_id = ? AND statut NOT IN ('Livrée','Annulée','Échouée')");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() == 0;
    }

    public function toggleActif($id) {
        $stmt = $this->db->prepare("UPDATE livreur SET est_actif = NOT est_actif WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM livreur WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getLivraisonsEnCours($id) {
        $stmt = $this->db->prepare("SELECT l.*, co.nom_complet FROM livraison l JOIN commande co ON l.commande_id = co.id WHERE l.livreur_id = ? AND l.statut NOT IN ('Livrée','Annulée','Échouée')");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    /**
     * Synchronise la colonne statut de chaque livreur avec ses livraisons actives réelles.
     * Un livreur sans livraison active passe en 'Disponible',
     * un livreur avec au moins une livraison active passe en 'En livraison'.
     */
    public function syncStatuts() {
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
