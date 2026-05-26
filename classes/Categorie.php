<?php
// Inclut le fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe Categorie : gère les catégories de produits
class Categorie {
    // Instance de la connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère toutes les catégories triées par nom
    public function getAll() {
        // Exécute une requête simple pour lister toutes les catégories
        return $this->db->query("SELECT * FROM categorie ORDER BY nom")->fetchAll();
    }

    // Récupère une catégorie par son identifiant
    public function getById($id) {
        // Prépare une requête paramétrée pour éviter les injections SQL
        $stmt = $this->db->prepare("SELECT * FROM categorie WHERE id = ?");
        // Exécute avec l'ID fourni
        $stmt->execute([$id]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Retourne le nombre total de catégories
    public function getNombre() {
        // Exécute une requête de comptage
        return $this->db->query("SELECT COUNT(*) FROM categorie")->fetchColumn();
    }

    // Récupère toutes les catégories avec le nombre de produits actifs dans chacune
    public function getWithProduitCount() {
        // Effectue une LEFT JOIN entre catégorie et produit pour compter les produits actifs par catégorie
        return $this->db->query("SELECT c.*, COUNT(p.id) as nb_produits FROM categorie c LEFT JOIN produit p ON c.id = p.categorie_id AND p.statut = 1 GROUP BY c.id ORDER BY c.nom")->fetchAll();
    }

    // Retourne le nombre de catégories actives (statut = 1)
    public function getActives() {
        // Compte les catégories dont le statut est actif
        return $this->db->query("SELECT COUNT(*) FROM categorie WHERE statut = 1")->fetchColumn();
    }

    // Récupère les catégories actives pour la navigation (uniquement id et nom)
    public function getForNav() {
        // Sélectionne les colonnes id et nom des catégories actives, triées par nom
        return $this->db->query("SELECT id, nom FROM categorie WHERE statut = 1 ORDER BY nom")->fetchAll();
    }

    // Ajoute une nouvelle catégorie avec un nom, une description, une image optionnelle et un parent optionnel
    public function ajouter($nom, $description, $image = null, $parentId = null) {
        // Prépare l'insertion dans la table categorie
        $stmt = $this->db->prepare("INSERT INTO categorie (nom, description, image, parent_id) VALUES (?, ?, ?, ?)");
        // Exécute et retourne le résultat
        return $stmt->execute([$nom, $description, $image, $parentId]);
    }

    // Modifie une catégorie existante (nom, description, image optionnelle)
    public function modifier($id, $nom, $description, $image = null) {
        // Si une nouvelle image est fournie
        if ($image) {
            // Met à jour avec l'image
            $stmt = $this->db->prepare("UPDATE categorie SET nom=?, description=?, image=? WHERE id=?");
            return $stmt->execute([$nom, $description, $image, $id]);
        }
        // Sinon, met à jour sans modifier l'image
        $stmt = $this->db->prepare("UPDATE categorie SET nom=?, description=? WHERE id=?");
        return $stmt->execute([$nom, $description, $id]);
    }

    // Bascule le statut d'une catégorie (actif/inactif) en inversant la valeur booléenne
    public function toggleStatut($id) {
        // Inverse le statut (NOT statut) pour la catégorie donnée
        $stmt = $this->db->prepare("UPDATE categorie SET statut = NOT statut WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$id]);
    }

    // Supprime une catégorie par son identifiant
    public function supprimer($id) {
        // Prépare la requête de suppression
        $stmt = $this->db->prepare("DELETE FROM categorie WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$id]);
    }
}
