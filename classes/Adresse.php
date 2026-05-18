<?php
require_once __DIR__ . '/../config/database.php';

class Adresse {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByUtilisateur($userId) {
        $stmt = $this->db->prepare("SELECT * FROM adresse WHERE utilisateur_id = ? ORDER BY est_principale DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM adresse WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function ajouter($utilisateurId, $quartier, $ville, $pointRepere = null) {
        $stmt = $this->db->prepare("INSERT INTO adresse (utilisateur_id, quartier, ville, point_repere) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$utilisateurId, $quartier, $ville, $pointRepere]);
    }

    public function definirPrincipale($id, $userId) {
        $this->db->prepare("UPDATE adresse SET est_principale = 0 WHERE utilisateur_id = ?")->execute([$userId]);
        $stmt = $this->db->prepare("UPDATE adresse SET est_principale = 1 WHERE id = ? AND utilisateur_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM adresse WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
