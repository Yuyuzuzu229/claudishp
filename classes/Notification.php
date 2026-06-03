<?php
// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe gérant les notifications en base de données
class Notification {
    // Instance de connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère toutes les notifications avec le nom et prénom de l'utilisateur, triées par date d'envoi décroissante
    public function getAll() {
        return $this->db->query("SELECT n.*, u.nom, u.prenom FROM notification n JOIN utilisateur u ON n.utilisateur_id = u.id ORDER BY n.date_envoi DESC")->fetchAll();
    }

    // Récupère toutes les notifications d'un utilisateur spécifique, triées par date d'envoi décroissante
    public function getByUtilisateur($userId) {
        // Préparation de la requête avec un paramètre utilisateur_id
        $stmt = $this->db->prepare("SELECT * FROM notification WHERE utilisateur_id = ? ORDER BY date_envoi DESC");
        // Exécution de la requête avec l'identifiant de l'utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes trouvées
        return $stmt->fetchAll();
    }

    // Compte le nombre de notifications non lues pour un utilisateur donné
    public function getNombreNonLu($userId) {
        // Préparation de la requête de comptage avec filtre statut = 'Non lue'
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification WHERE utilisateur_id = ? AND statut = 'Non lue'");
        // Exécution de la requête avec l'identifiant de l'utilisateur
        $stmt->execute([$userId]);
        // Retourne le nombre de notifications non lues
        return $stmt->fetchColumn();
    }

    // Retourne le nombre total de notifications dans la base
    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM notification")->fetchColumn();
    }

    // Crée une nouvelle notification pour un utilisateur avec un titre, un message, un canal et éventuellement un identifiant de commande
    public function creer($utilisateurId, $titre, $message, $canal = 'In-app', $commandeId = null) {
        // Préparation de la requête d'insertion
        $stmt = $this->db->prepare("INSERT INTO notification (utilisateur_id, titre, message, canal, commande_id, date_envoi) VALUES (?, ?, ?, ?, ?, NOW())");
        // Exécution de l'insertion avec les paramètres fournis
        return $stmt->execute([$utilisateurId, $titre, $message, $canal, $commandeId]);
    }

    // Marque une notification comme lue en mettant son statut à 'Lue'
    public function marquerLue($id) {
        // Préparation de la requête de mise à jour du statut
        $stmt = $this->db->prepare("UPDATE notification SET statut = 'Lue' WHERE id = ?");
        // Exécution avec l'identifiant de la notification
        return $stmt->execute([$id]);
    }

    // Marque toutes les notifications non lues d'un utilisateur comme lues
    public function marquerToutesLues($userId) {
        // Préparation de la requête de mise à jour en masse
        $stmt = $this->db->prepare("UPDATE notification SET statut = 'Lue' WHERE utilisateur_id = ? AND statut = 'Non lue'");
        // Exécution avec l'identifiant de l'utilisateur
        return $stmt->execute([$userId]);
    }

    // Marque une notification comme envoyée en mettant son statut à 'Envoyé'
    public function envoyer($id) {
        return $this->db->prepare("UPDATE notification SET statut = 'Envoyé' WHERE id = ?")->execute([$id]);
    }

    // Supprime une notification de la base de données par son identifiant
    public function supprimer($id) {
        // Préparation de la requête de suppression
        $stmt = $this->db->prepare("DELETE FROM notification WHERE id = ?");
        // Exécution avec l'identifiant de la notification
        return $stmt->execute([$id]);
    }

    // Supprime plusieurs notifications en une seule requête
    public function supprimerPlusieurs(array $ids) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM notification WHERE id IN ($placeholders)");
        return $stmt->execute(array_values($ids));
    }

    // Supprime plusieurs notifications d'un utilisateur spécifique (sécurité)
    public function supprimerPlusieursByUser(array $ids, $userId) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_values($ids);
        $params[] = $userId;
        $stmt = $this->db->prepare("DELETE FROM notification WHERE id IN ($placeholders) AND utilisateur_id = ?");
        return $stmt->execute($params);
    }
}
