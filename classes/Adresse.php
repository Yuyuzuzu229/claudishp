<?php
// Inclut le fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe Adresse : gère les opérations CRUD sur les adresses des utilisateurs
class Adresse {
    // Instance de la connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base de données via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère toutes les adresses d'un utilisateur, triées par adresse principale en premier
    public function getByUtilisateur($userId) {
        // Prépare la requête SQL pour sélectionner les adresses d'un utilisateur
        $stmt = $this->db->prepare("SELECT * FROM adresse WHERE utilisateur_id = ? ORDER BY est_principale DESC");
        // Exécute la requête avec l'ID utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes sous forme de tableau
        return $stmt->fetchAll();
    }

    // Récupère une adresse par son identifiant
    public function getById($id) {
        // Prépare la requête SQL pour sélectionner une adresse par son ID
        $stmt = $this->db->prepare("SELECT * FROM adresse WHERE id = ?");
        // Exécute la requête avec l'ID
        $stmt->execute([$id]);
        // Retourne une seule ligne sous forme de tableau associatif
        return $stmt->fetch();
    }

    // Ajoute une nouvelle adresse pour un utilisateur
    public function ajouter($utilisateurId, $quartier, $ville, $pointRepere = null) {
        // Prépare la requête d'insertion dans la table adresse
        $stmt = $this->db->prepare("INSERT INTO adresse (utilisateur_id, quartier, ville, point_repere) VALUES (?, ?, ?, ?)");
        // Exécute et retourne le résultat (true/false)
        return $stmt->execute([$utilisateurId, $quartier, $ville, $pointRepere]);
    }

    // Définit une adresse comme principale pour un utilisateur (les autres passent à 0)
    public function definirPrincipale($id, $userId) {
        // Désactive d'abord toutes les adresses principales de cet utilisateur
        $this->db->prepare("UPDATE adresse SET est_principale = 0 WHERE utilisateur_id = ?")->execute([$userId]);
        // Puis définit l'adresse spécifiée comme principale
        $stmt = $this->db->prepare("UPDATE adresse SET est_principale = 1 WHERE id = ? AND utilisateur_id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$id, $userId]);
    }

    // Supprime une adresse par son identifiant
    public function supprimer($id) {
        // Prépare la requête de suppression
        $stmt = $this->db->prepare("DELETE FROM adresse WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$id]);
    }
}
