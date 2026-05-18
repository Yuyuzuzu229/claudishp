<?php
require_once __DIR__ . '/../config/database.php';

class Avis {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT a.*, p.nom as produit_nom, u.nom, u.prenom FROM avis a JOIN produit p ON a.produit_id = p.id JOIN utilisateur u ON a.utilisateur_id = u.id ORDER BY a.date_creation DESC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT a.*, p.nom as produit_nom, u.nom, u.prenom FROM avis a JOIN produit p ON a.produit_id = p.id JOIN utilisateur u ON a.utilisateur_id = u.id WHERE a.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByProduit($produitId) {
        $stmt = $this->db->prepare("SELECT a.*, u.nom, u.prenom FROM avis a JOIN utilisateur u ON a.utilisateur_id = u.id WHERE a.produit_id = ? AND a.statut = 'Publié' ORDER BY a.date_creation DESC");
        $stmt->execute([$produitId]);
        return $stmt->fetchAll();
    }

    public function getByUtilisateur($userId) {
        $stmt = $this->db->prepare("SELECT a.*, p.nom as produit_nom FROM avis a JOIN produit p ON a.produit_id = p.id WHERE a.utilisateur_id = ? ORDER BY a.date_creation DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function aDejaAvis($userId, $produitId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM avis WHERE utilisateur_id = ? AND produit_id = ?");
        $stmt->execute([$userId, $produitId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getProduitsAchetables($userId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.nom, p.photo, p.prix
            FROM ligne_commande lc
            JOIN commande c ON lc.commande_id = c.id
            JOIN produit p ON lc.produit_id = p.id
            WHERE c.utilisateur_id = ?
              AND c.statut IN ('Confirmée','En préparation','En route','En livraison','Livrée')
              AND lc.produit_id NOT IN (
                  SELECT produit_id FROM avis WHERE utilisateur_id = ?
              )
            ORDER BY p.nom
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getPublies($limit = 6) {
        $stmt = $this->db->prepare("SELECT a.*, u.nom, u.prenom FROM avis a JOIN utilisateur u ON a.utilisateur_id = u.id WHERE a.statut = 'Publié' ORDER BY a.date_creation DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM avis")->fetchColumn();
    }

    public function ajouter($produitId, $utilisateurId, $note, $commentaire) {
        $stmt = $this->db->prepare("INSERT INTO avis (produit_id, utilisateur_id, note, commentaire, statut) VALUES (?, ?, ?, ?, 'Publié')");
        $result = $stmt->execute([$produitId, $utilisateurId, $note, $commentaire]);
        if ($result) $this->mettreAJourNoteMoyenne($produitId);
        return $result;
    }

    public function updateStatut($id, $statut) {
        $stmt = $this->db->prepare("UPDATE avis SET statut = ? WHERE id = ?");
        $result = $stmt->execute([$statut, $id]);
        if ($result) {
            $a = $this->getById($id);
            if ($a) $this->mettreAJourNoteMoyenne($a['produit_id']);
        }
        return $result;
    }

    public function supprimer($id) {
        $a = $this->getById($id);
        $stmt = $this->db->prepare("DELETE FROM avis WHERE id = ?");
        $result = $stmt->execute([$id]);
        if ($result && $a) $this->mettreAJourNoteMoyenne($a['produit_id']);
        return $result;
    }

    private function mettreAJourNoteMoyenne($produitId) {
        $stmt = $this->db->prepare("UPDATE produit SET note_moyenne = (SELECT COALESCE(AVG(note), 0) FROM avis WHERE produit_id = ? AND statut = 'Publié') WHERE id = ?");
        return $stmt->execute([$produitId, $produitId]);
    }
}
