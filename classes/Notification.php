<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT n.*, u.nom, u.prenom FROM notification n JOIN utilisateur u ON n.utilisateur_id = u.id ORDER BY n.date_envoi DESC")->fetchAll();
    }

    public function getByUtilisateur($userId) {
        $stmt = $this->db->prepare("SELECT * FROM notification WHERE utilisateur_id = ? ORDER BY date_envoi DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getNombreNonLu($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification WHERE utilisateur_id = ? AND statut = 'Non lue'");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM notification")->fetchColumn();
    }

    public function creer($utilisateurId, $titre, $message, $canal = 'In-app', $commandeId = null) {
        $stmt = $this->db->prepare("INSERT INTO notification (utilisateur_id, titre, message, canal, commande_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$utilisateurId, $titre, $message, $canal, $commandeId]);
    }

    public function marquerLue($id) {
        $stmt = $this->db->prepare("UPDATE notification SET statut = 'Lue' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function marquerToutesLues($userId) {
        $stmt = $this->db->prepare("UPDATE notification SET statut = 'Lue' WHERE utilisateur_id = ? AND statut = 'Non lue'");
        return $stmt->execute([$userId]);
    }

    public function envoyer($id) {
        return $this->db->prepare("UPDATE notification SET statut = 'Envoyé' WHERE id = ?")->execute([$id]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM notification WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
