<?php
require_once __DIR__ . '/../config/database.php';

class Paiement {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT pa.*, c.utilisateur_id, u.nom, u.prenom FROM paiement pa JOIN commande c ON pa.commande_id = c.id JOIN utilisateur u ON c.utilisateur_id = u.id ORDER BY pa.date_paiement DESC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM paiement WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCommande($commandeId) {
        $stmt = $this->db->prepare("SELECT * FROM paiement WHERE commande_id = ?");
        $stmt->execute([$commandeId]);
        return $stmt->fetchAll();
    }

    public function getByUtilisateur($userId) {
        $stmt = $this->db->prepare("SELECT pa.*, c.montant_total FROM paiement pa JOIN commande c ON pa.commande_id = c.id WHERE c.utilisateur_id = ? ORDER BY pa.date_paiement DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getTotalPaiements() {
        return $this->db->query("SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut = 'Confirmé'")->fetchColumn();
    }

    public function creer($commandeId, $montant, $mode, $telephone = null, $token = null, $reference = null) {
        $stmt = $this->db->prepare("INSERT INTO paiement (commande_id, montant, mode, telephone_paiement, token, reference_transaction) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$commandeId, $montant, $mode, $telephone, $token, $reference]);
    }

    public function confirmer($id) {
        $stmt = $this->db->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = CONCAT('TRX-', UPPER(SUBSTRING(MD5(RAND()), 1, 10))) WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function echouer($id) {
        $stmt = $this->db->prepare("UPDATE paiement SET statut = 'Échoué' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
