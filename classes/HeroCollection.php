<?php
require_once __DIR__ . '/../config/database.php';

class HeroCollection {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM hero_collection ORDER BY ordre, id")->fetchAll();
    }

    public function getActives() {
        return $this->db->query("SELECT * FROM hero_collection WHERE statut = 1 ORDER BY ordre, id")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM hero_collection WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function ajouter($titre, $tag, $type, $categorieId, $produitIds, $ordre) {
        $stmt = $this->db->prepare("INSERT INTO hero_collection (titre, tag, type, categorie_id, produit_ids, ordre) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$titre, $tag, $type, $categorieId ?: null, $produitIds ?: null, $ordre]);
    }

    public function modifier($id, $titre, $tag, $type, $categorieId, $produitIds, $statut, $ordre) {
        $stmt = $this->db->prepare("UPDATE hero_collection SET titre=?, tag=?, type=?, categorie_id=?, produit_ids=?, statut=?, ordre=? WHERE id=?");
        return $stmt->execute([$titre, $tag, $type, $categorieId ?: null, $produitIds ?: null, $statut, $ordre, $id]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM hero_collection WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleStatut($id) {
        $stmt = $this->db->prepare("UPDATE hero_collection SET statut = NOT statut WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
