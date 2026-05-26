<?php
// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe gérant les opérations de paiement en base de données
class Paiement {
    // Instance de connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère tous les paiements avec les informations utilisateur associées, triés par date décroissante
    public function getAll() {
        return $this->db->query("SELECT pa.*, c.utilisateur_id, u.nom, u.prenom FROM paiement pa JOIN commande c ON pa.commande_id = c.id JOIN utilisateur u ON c.utilisateur_id = u.id ORDER BY pa.date_paiement DESC")->fetchAll();
    }

    // Récupère un paiement spécifique par son identifiant
    public function getById($id) {
        // Préparation de la requête avec paramètre id
        $stmt = $this->db->prepare("SELECT * FROM paiement WHERE id = ?");
        // Exécution avec l'identifiant du paiement
        $stmt->execute([$id]);
        // Retourne une seule ligne (ou false si non trouvé)
        return $stmt->fetch();
    }

    // Récupère tous les paiements associés à une commande donnée
    public function getByCommande($commandeId) {
        // Préparation de la requête avec paramètre commande_id
        $stmt = $this->db->prepare("SELECT * FROM paiement WHERE commande_id = ?");
        // Exécution avec l'identifiant de la commande
        $stmt->execute([$commandeId]);
        // Retourne toutes les lignes trouvées
        return $stmt->fetchAll();
    }

    // Récupère tous les paiements d'un utilisateur avec le montant total de la commande, triés par date décroissante
    public function getByUtilisateur($userId) {
        // Jointure entre paiement et commande pour filtrer par utilisateur
        $stmt = $this->db->prepare("SELECT pa.*, c.montant_total FROM paiement pa JOIN commande c ON pa.commande_id = c.id WHERE c.utilisateur_id = ? ORDER BY pa.date_paiement DESC");
        // Exécution avec l'identifiant de l'utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes trouvées
        return $stmt->fetchAll();
    }

    // Calcule le montant total des paiements confirmés
    public function getTotalPaiements() {
        return $this->db->query("SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut = 'Confirmé'")->fetchColumn();
    }

    // Crée un nouveau paiement pour une commande avec le montant, le mode, et optionnellement téléphone, token et référence
    public function creer($commandeId, $montant, $mode, $telephone = null, $token = null, $reference = null) {
        // Préparation de la requête d'insertion
        $stmt = $this->db->prepare("INSERT INTO paiement (commande_id, montant, mode, telephone_paiement, token, reference_transaction) VALUES (?, ?, ?, ?, ?, ?)");
        // Exécution avec les paramètres fournis
        return $stmt->execute([$commandeId, $montant, $mode, $telephone, $token, $reference]);
    }

    // Confirme un paiement en mettant son statut à 'Confirmé' et en générant une référence unique
    public function confirmer($id) {
        // Mise à jour du statut et génération d'une référence au format TRX-XXXXXXXXXX
        $stmt = $this->db->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = CONCAT('TRX-', UPPER(SUBSTRING(MD5(RAND()), 1, 10))) WHERE id = ?");
        // Exécution avec l'identifiant du paiement
        return $stmt->execute([$id]);
    }

    // Marque un paiement comme échoué
    public function echouer($id) {
        // Mise à jour du statut à 'Échoué'
        $stmt = $this->db->prepare("UPDATE paiement SET statut = 'Échoué' WHERE id = ?");
        // Exécution avec l'identifiant du paiement
        return $stmt->execute([$id]);
    }
}
