<?php
require_once __DIR__ . '/../config/database.php';

class Produit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id ORDER BY p.date_creation DESC");
        return $stmt->fetchAll();
    }

    public function getSoldes($limite = 8) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1 AND p.solde_prix IS NOT NULL AND p.solde_prix > 0 ORDER BY p.date_creation DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function getDerniersSansSoldes($limite = 8) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1 AND (p.solde_prix IS NULL OR p.solde_prix = 0) ORDER BY p.date_creation DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCategorie($categorieId) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.categorie_id = ? ORDER BY p.date_creation DESC");
        $stmt->execute([$categorieId]);
        return $stmt->fetchAll();
    }

    public function getDerniers($limite = 8) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1 ORDER BY p.date_creation DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function getMeilleuresVentes($limite = 4) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom, COALESCE(SUM(lc.quantite), 0) as total_vendu FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id LEFT JOIN ligne_commande lc ON p.id = lc.produit_id WHERE p.statut = 1 GROUP BY p.id ORDER BY total_vendu DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function rechercher($motCle) {
        $motCle = "%$motCle%";
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.nom LIKE ? OR p.description LIKE ? AND p.statut = 1");
        $stmt->execute([$motCle, $motCle]);
        return $stmt->fetchAll();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM produit")->fetchColumn();
    }

    public function getStockFaible() {
        return $this->db->query("SELECT COUNT(*) FROM produit WHERE stock <= 5 AND stock > 0")->fetchColumn();
    }

    public function getNombreActifs() {
        return $this->db->query("SELECT COUNT(*) FROM produit WHERE statut = 1")->fetchColumn();
    }

    public function ajouter($nom, $description, $prix, $stock, $categorieId, $photo, $taille = null, $couleur = null, $matiere = null, $soldePrix = null, $images = null) {
        $stmt = $this->db->prepare("INSERT INTO produit (nom, description, prix, stock, categorie_id, photo, images, taille_disponible, couleur, matiere, solde_prix) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $photo, $images, $taille, $couleur, $matiere, $soldePrix]);
    }

    public function modifier($id, $nom, $description, $prix, $stock, $categorieId, $photo = null, $taille = null, $couleur = null, $matiere = null, $soldePrix = null, $images = null) {
        if ($photo && $images) {
            $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, photo=?, images=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
            return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $photo, $images, $taille, $couleur, $matiere, $soldePrix, $id]);
        }
        if ($photo) {
            $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, photo=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
            return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $photo, $taille, $couleur, $matiere, $soldePrix, $id]);
        }
        if ($images) {
            $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, images=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
            return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $images, $taille, $couleur, $matiere, $soldePrix, $id]);
        }
        $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
        return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $taille, $couleur, $matiere, $soldePrix, $id]);
    }

    public function toggleStatut($id) {
        $stmt = $this->db->prepare("UPDATE produit SET statut = NOT statut WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM produit WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function verifierDisponibilite($id, $quantite) {
        $produit = $this->getById($id);
        return $produit && $produit['stock'] >= $quantite;
    }

    public function mettreAJourStock($id, $quantite) {
        $stmt = $this->db->prepare("UPDATE produit SET stock = stock - ? WHERE id = ?");
        return $stmt->execute([$quantite, $id]);
    }

    public function getByCategorieWithFilters($categorieId = null, $recherche = null, $minPrix = null, $maxPrix = null, $tailles = null, $couleurs = null, $matieres = null, $noteMin = null, $stockOnly = false, $soldesOnly = false, $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1";
        $params = [];
        if ($categorieId) { $sql .= " AND p.categorie_id = ?"; $params[] = $categorieId; }
        if ($recherche) { $sql .= " AND (p.nom LIKE ? OR p.description LIKE ?)"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; }
        if ($minPrix !== null) { $sql .= " AND p.prix >= ?"; $params[] = $minPrix; }
        if ($maxPrix !== null) { $sql .= " AND p.prix <= ?"; $params[] = $maxPrix; }
        if ($stockOnly) { $sql .= " AND p.stock > 0"; }
        if ($soldesOnly) { $sql .= " AND p.solde_prix IS NOT NULL AND p.solde_prix > 0"; }
        $sql .= " ORDER BY p.date_creation DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countByCategorieWithFilters($categorieId = null, $recherche = null, $minPrix = null, $maxPrix = null, $tailles = null, $couleurs = null, $matieres = null, $noteMin = null, $stockOnly = false, $soldesOnly = false) {
        $sql = "SELECT COUNT(*) FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1";
        $params = [];
        if ($categorieId) { $sql .= " AND p.categorie_id = ?"; $params[] = $categorieId; }
        if ($recherche) { $sql .= " AND (p.nom LIKE ? OR p.description LIKE ?)"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; }
        if ($minPrix !== null) { $sql .= " AND p.prix >= ?"; $params[] = $minPrix; }
        if ($maxPrix !== null) { $sql .= " AND p.prix <= ?"; $params[] = $maxPrix; }
        if ($stockOnly) { $sql .= " AND p.stock > 0"; }
        if ($soldesOnly) { $sql .= " AND p.solde_prix IS NOT NULL AND p.solde_prix > 0"; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
