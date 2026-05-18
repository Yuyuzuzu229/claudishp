<?php
require_once __DIR__ . '/../config/database.php';

class ZoneLivraison {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM zone_livraison ORDER BY nom")->fetchAll();
    }

    public function getActives() {
        return $this->db->query("SELECT * FROM zone_livraison WHERE statut = 1 ORDER BY nom")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM zone_livraison WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM zone_livraison")->fetchColumn();
    }

    public function ajouter($nom, $description, $tarif) {
        $stmt = $this->db->prepare("INSERT INTO zone_livraison (nom, description, tarif) VALUES (?, ?, ?)");
        return $stmt->execute([$nom, $description, $tarif]);
    }

    public function modifier($id, $nom, $description, $tarif) {
        $stmt = $this->db->prepare("UPDATE zone_livraison SET nom=?, description=?, tarif=? WHERE id=?");
        return $stmt->execute([$nom, $description, $tarif, $id]);
    }

    public function getProches($lat, $lng, $limite = 1) {
        $stmt = $this->db->prepare("SELECT *, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))) AS distance FROM zone_livraison WHERE statut = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY distance ASC LIMIT ?");
        $stmt->execute([$lat, $lng, $lat, $limite]);
        return $limite === 1 ? $stmt->fetch() : $stmt->fetchAll();
    }

    public function toggleStatut($id) {
        $stmt = $this->db->prepare("UPDATE zone_livraison SET statut = NOT statut WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM zone_livraison WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
