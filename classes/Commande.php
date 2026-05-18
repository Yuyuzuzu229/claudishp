<?php
require_once __DIR__ . '/../config/database.php';

class Commande {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT c.*, u.nom, u.prenom FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id ORDER BY c.date_commande DESC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT c.*, u.nom, u.prenom, u.email, u.telephone FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id WHERE c.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByUtilisateur($userId) {
        $stmt = $this->db->prepare("SELECT * FROM commande WHERE utilisateur_id = ? ORDER BY date_commande DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM commande")->fetchColumn();
    }

    public function getTotalVentes() {
        return $this->db->query("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE statut != 'Annulée'")->fetchColumn();
    }

    public function getDernieresCommandes($limite = 5) {
        $stmt = $this->db->prepare("SELECT c.*, u.nom, u.prenom FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id ORDER BY c.date_commande DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function getStatistiquesMois() {
        return $this->db->query("SELECT DATE_FORMAT(date_commande, '%Y-%m') as mois, COUNT(*) as nb_commandes, SUM(montant_total) as revenus FROM commande WHERE statut != 'Annulée' GROUP BY mois ORDER BY mois DESC LIMIT 6")->fetchAll();
    }

    public function getCommandesDuJour() {
        return $this->db->query("SELECT COUNT(*) FROM commande WHERE DATE(date_commande) = CURDATE()")->fetchColumn();
    }

    public function getEnCours() {
        return $this->db->query("SELECT COUNT(*) FROM commande WHERE statut NOT IN ('Livrée','Annulée')")->fetchColumn();
    }

    public function getAnnulees() {
        return $this->db->query("SELECT COUNT(*) FROM commande WHERE statut = 'Annulée'")->fetchColumn();
    }

    public function getLignes($commandeId) {
        $stmt = $this->db->prepare("SELECT lc.*, p.nom, p.photo FROM ligne_commande lc JOIN produit p ON lc.produit_id = p.id WHERE lc.commande_id = ?");
        $stmt->execute([$commandeId]);
        return $stmt->fetchAll();
    }

    public function creer($utilisateurId, $montantTotal, $modeRetrait, $adresse = null, $nomComplet = null, $telephone = null, $instructions = null, $latitude = null, $longitude = null, $idZone = null) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO commande (utilisateur_id, montant_total, mode_retrait, adresse_livraison, nom_complet, telephone, instructions, latitude_client, longitude_client, id_zone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$utilisateurId, $montantTotal, $modeRetrait, $adresse, $nomComplet, $telephone, $instructions, $latitude, $longitude, $idZone]);
            $commandeId = $this->db->lastInsertId();
            $this->db->commit();
            return $commandeId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function ajouterLignes($commandeId, $lignes) {
        $stmt = $this->db->prepare("INSERT INTO ligne_commande (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
        foreach ($lignes as $l) {
            $stmt->execute([$commandeId, $l['produit_id'], $l['quantite'], $l['prix_unitaire']]);
        }
    }

    public function updateStatut($id, $statut) {
        $stmt = $this->db->prepare("UPDATE commande SET statut = ? WHERE id = ?");
        return $stmt->execute([$statut, $id]);
    }

    public function annuler($id) {
        $result = $this->updateStatut($id, 'Annulée');
        require_once __DIR__ . '/Livraison.php';
        $liv = new Livraison();
        $liv->libererLivreurParCommande($id);
        return $result;
    }

    public function suivreStatut($id) {
        $c = $this->getById($id);
        return $c ? $c['statut'] : null;
    }

    public function getVentes7Jours() {
        return $this->db->query("SELECT DATE(date_commande) as jour, SUM(montant_total) as total FROM commande WHERE date_commande >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND statut != 'Annulée' GROUP BY DATE(date_commande) ORDER BY jour")->fetchAll();
    }

    public function getRepartitionStatuts() {
        return $this->db->query("SELECT statut, COUNT(*) as total, COUNT(*) * 100.0 / (SELECT COUNT(*) FROM commande) as pourcentage FROM commande GROUP BY statut")->fetchAll();
    }
}
