<?php
require_once __DIR__ . '/../config/database.php';

class Categorie {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM categorie ORDER BY nom")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categorie WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM categorie")->fetchColumn();
    }

    public function getWithProduitCount() {
        return $this->db->query("SELECT c.*, COUNT(p.id) as nb_produits FROM categorie c LEFT JOIN produit p ON c.id = p.categorie_id AND p.statut = 1 GROUP BY c.id ORDER BY c.nom")->fetchAll();
    }

    public function getActives() {
        return $this->db->query("SELECT COUNT(*) FROM categorie WHERE statut = 1")->fetchColumn();
    }

    public function getForNav() {
        return $this->db->query("SELECT id, nom FROM categorie WHERE statut = 1 ORDER BY nom")->fetchAll();
    }

    public function ajouter($nom, $description, $image = null, $parentId = null) {
        $stmt = $this->db->prepare("INSERT INTO categorie (nom, description, image, parent_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nom, $description, $image, $parentId]);
    }

    public function modifier($id, $nom, $description, $image = null) {
        if ($image) {
            $stmt = $this->db->prepare("UPDATE categorie SET nom=?, description=?, image=? WHERE id=?");
            return $stmt->execute([$nom, $description, $image, $id]);
        }
        $stmt = $this->db->prepare("UPDATE categorie SET nom=?, description=? WHERE id=?");
        return $stmt->execute([$nom, $description, $id]);
    }

    public function toggleStatut($id) {
        $stmt = $this->db->prepare("UPDATE categorie SET statut = NOT statut WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM categorie WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
