<?php
// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe gérant les zones de livraison et le calcul de distance géographique
class ZoneLivraison {
    // Instance de connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère toutes les zones de livraison triées par nom
    public function getAll() {
        return $this->db->query("SELECT * FROM zone_livraison ORDER BY nom")->fetchAll();
    }

    // Récupère uniquement les zones actives (statut = 1) triées par nom
    public function getActives() {
        return $this->db->query("SELECT * FROM zone_livraison WHERE statut = 1 ORDER BY nom")->fetchAll();
    }

    // Récupère une zone de livraison par son identifiant
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM zone_livraison WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Retourne le nombre total de zones de livraison
    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM zone_livraison")->fetchColumn();
    }

    // Ajoute une nouvelle zone de livraison avec nom, description et tarif
    public function ajouter($nom, $description, $tarif) {
        $stmt = $this->db->prepare("INSERT INTO zone_livraison (nom, description, tarif) VALUES (?, ?, ?)");
        return $stmt->execute([$nom, $description, $tarif]);
    }

    // Modifie une zone de livraison existante (nom, description, tarif)
    public function modifier($id, $nom, $description, $tarif) {
        $stmt = $this->db->prepare("UPDATE zone_livraison SET nom=?, description=?, tarif=? WHERE id=?");
        return $stmt->execute([$nom, $description, $tarif, $id]);
    }

    // Trouve les zones de livraison les plus proches d'un point GPS (latitude, longitude) en utilisant la formule de Haversine
    public function getProches($lat, $lng, $limite = 1) {
        // Calcul de la distance en km avec la formule de Haversine (formule du grand cercle)
        $stmt = $this->db->prepare("SELECT *, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))) AS distance FROM zone_livraison WHERE statut = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY distance ASC LIMIT ?");
        $stmt->execute([$lat, $lng, $lat, $limite]);
        // Si limite = 1, retourne une seule ligne, sinon retourne toutes les lignes
        return $limite === 1 ? $stmt->fetch() : $stmt->fetchAll();
    }

    // Bascule le statut d'une zone (actif ↔ inactif)
    public function toggleStatut($id) {
        $stmt = $this->db->prepare("UPDATE zone_livraison SET statut = NOT statut WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Supprime une zone de livraison par son identifiant
    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM zone_livraison WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
