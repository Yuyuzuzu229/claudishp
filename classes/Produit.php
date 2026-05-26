<?php
// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe gérant les opérations CRUD et recherches sur les produits
class Produit {
    // Instance de connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère tous les produits avec le nom de leur catégorie, triés par date de création décroissante
    public function getAll() {
        $stmt = $this->db->query("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id ORDER BY p.date_creation DESC");
        return $stmt->fetchAll();
    }

    // Recherche des produits par mot-clé dans le nom ou la description
    public function search($q) {
        // Préparation d'une requête avec LIKE pour chercher dans nom et description
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.nom LIKE ? OR p.description LIKE ? ORDER BY p.date_creation DESC");
        // Ajout des wildcards % pour la recherche partielle
        $like = "%$q%";
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    // Récupère les produits en solde (solde_prix > 0), avec une limite optionnelle
    public function getSoldes($limite = 8) {
        // Filtre sur statut actif et solde_prix non nul et positif
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1 AND p.solde_prix IS NOT NULL AND p.solde_prix > 0 ORDER BY p.date_creation DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    // Récupère les derniers produits sans solde, avec une limite optionnelle
    public function getDerniersSansSoldes($limite = 8) {
        // Filtre sur statut actif ET solde_prix nul ou égal à 0
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1 AND (p.solde_prix IS NULL OR p.solde_prix = 0) ORDER BY p.date_creation DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    // Récupère un produit spécifique par son identifiant
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Récupère plusieurs produits par leurs identifiants
    public function getByIds(array $ids) {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.id IN ($placeholders)");
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll();
    }

    // Récupère tous les produits d'une catégorie donnée, triés par date de création décroissante
    public function getByCategorie($categorieId) {
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.categorie_id = ? ORDER BY p.date_creation DESC");
        $stmt->execute([$categorieId]);
        return $stmt->fetchAll();
    }

    // Récupère les derniers produits actifs, avec une limite optionnelle
    public function getDerniers($limite = 8) {
        // Filtre sur les produits dont le statut est actif (statut = 1)
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1 ORDER BY p.date_creation DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    // Récupère les meilleures ventes (produits avec le plus de quantité vendue), avec une limite optionnelle
    public function getMeilleuresVentes($limite = 4) {
        // Jointure avec ligne_commande pour agréger les quantités vendues, tri par total descendant
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom, COALESCE(SUM(lc.quantite), 0) as total_vendu FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id LEFT JOIN ligne_commande lc ON p.id = lc.produit_id WHERE p.statut = 1 GROUP BY p.id ORDER BY total_vendu DESC LIMIT ?");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    // Recherche de produits actifs par mot-clé (nom ou description)
    public function rechercher($motCle) {
        // Ajout des wildcards pour la recherche partielle
        $motCle = "%$motCle%";
        $stmt = $this->db->prepare("SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.nom LIKE ? OR p.description LIKE ? AND p.statut = 1");
        $stmt->execute([$motCle, $motCle]);
        return $stmt->fetchAll();
    }

    // Retourne le nombre total de produits dans la base
    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM produit")->fetchColumn();
    }

    // Retourne le nombre de produits dont le stock est faible (≤ 5 mais > 0)
    public function getStockFaible() {
        return $this->db->query("SELECT COUNT(*) FROM produit WHERE stock <= 5 AND stock > 0")->fetchColumn();
    }

    // Retourne le nombre de produits actifs (statut = 1)
    public function getNombreActifs() {
        return $this->db->query("SELECT COUNT(*) FROM produit WHERE statut = 1")->fetchColumn();
    }

    // Ajoute un nouveau produit avec toutes ses caractéristiques
    public function ajouter($nom, $description, $prix, $stock, $categorieId, $photo, $taille = null, $couleur = null, $matiere = null, $soldePrix = null, $images = null) {
        $stmt = $this->db->prepare("INSERT INTO produit (nom, description, prix, stock, categorie_id, photo, images, taille_disponible, couleur, matiere, solde_prix) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $photo, $images, $taille, $couleur, $matiere, $soldePrix]);
    }

    // Modifie un produit existant (gère les cas avec ou sans photo/images)
    public function modifier($id, $nom, $description, $prix, $stock, $categorieId, $photo = null, $taille = null, $couleur = null, $matiere = null, $soldePrix = null, $images = null) {
        // Si photo ET images sont fournis, on met à jour les deux champs
        if ($photo && $images) {
            $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, photo=?, images=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
            return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $photo, $images, $taille, $couleur, $matiere, $soldePrix, $id]);
        }
        // Si seulement photo est fournie, on met à jour sans images
        if ($photo) {
            $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, photo=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
            return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $photo, $taille, $couleur, $matiere, $soldePrix, $id]);
        }
        // Si seulement images est fournie, on met à jour sans photo
        if ($images) {
            $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, images=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
            return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $images, $taille, $couleur, $matiere, $soldePrix, $id]);
        }
        // Si ni photo ni images, mise à jour sans ces champs
        $stmt = $this->db->prepare("UPDATE produit SET nom=?, description=?, prix=?, stock=?, categorie_id=?, taille_disponible=?, couleur=?, matiere=?, solde_prix=? WHERE id=?");
        return $stmt->execute([$nom, $description, $prix, $stock, $categorieId, $taille, $couleur, $matiere, $soldePrix, $id]);
    }

    // Bascule le statut d'un produit (actif ↔ inactif) en inversant la valeur booléenne
    public function toggleStatut($id) {
        $stmt = $this->db->prepare("UPDATE produit SET statut = NOT statut WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Supprime un produit par son identifiant
    public function supprimer($id) {
        $stmt = $this->db->prepare("DELETE FROM produit WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Vérifie si un produit est disponible en quantité suffisante
    public function verifierDisponibilite($id, $quantite) {
        $produit = $this->getById($id);
        // Retourne true si le produit existe et que son stock est ≥ la quantité demandée
        return $produit && $produit['stock'] >= $quantite;
    }

    // Décrémente le stock d'un produit d'une quantité donnée (après une commande)
    public function mettreAJourStock($id, $quantite) {
        $stmt = $this->db->prepare("UPDATE produit SET stock = stock - ? WHERE id = ?");
        return $stmt->execute([$quantite, $id]);
    }

    // Récupère les produits avec filtres avancés (catégorie, recherche, prix, taille, couleur, matière, note, stock, soldes) et pagination
    public function getByCategorieWithFilters($categorieId = null, $recherche = null, $minPrix = null, $maxPrix = null, $tailles = null, $couleurs = null, $matieres = null, $noteMin = null, $stockOnly = false, $soldesOnly = false, $page = 1, $perPage = 12) {
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $perPage;
        // Requête de base avec jointure catégorie et filtre statut actif
        $sql = "SELECT p.*, c.nom as categorie_nom FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1";
        $params = [];
        // Filtre par catégorie (support multiple séparé par des virgules)
        if ($categorieId) {
            // Si c'est une chaîne contenant des virgules, on traite plusieurs IDs
            if (is_string($categorieId) && strpos($categorieId, ',') !== false) {
                $ids = array_map('intval', explode(',', $categorieId));
                $ids = array_filter($ids);
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql .= " AND p.categorie_id IN ($placeholders)";
                    $params = array_merge($params, $ids);
                }
            } else {
                $sql .= " AND p.categorie_id = ?";
                $params[] = intval($categorieId);
            }
        }
        // Filtre par mot-clé de recherche (nom OU description)
        if ($recherche) { $sql .= " AND (p.nom LIKE ? OR p.description LIKE ?)"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; }
        // Filtre par prix minimum
        if ($minPrix !== null) { $sql .= " AND p.prix >= ?"; $params[] = $minPrix; }
        // Filtre par prix maximum
        if ($maxPrix !== null) { $sql .= " AND p.prix <= ?"; $params[] = $maxPrix; }
        // Filtre par tailles disponibles (utilisation de FIND_IN_SET pour le champ multi-valeurs)
        if ($tailles && is_array($tailles) && !empty($tailles)) {
            $sizeConditions = [];
            foreach ($tailles as $t) {
                $sizeConditions[] = "FIND_IN_SET(?, p.taille_disponible)";
                $params[] = trim($t);
            }
            $sql .= " AND (" . implode(' OR ', $sizeConditions) . ")";
        }
        // Filtre par note minimum (sous-requête sur la moyenne des avis)
        if ($noteMin !== null) {
            $sql .= " AND (SELECT COALESCE(AVG(note), 0) FROM avis WHERE produit_id = p.id) >= ?";
            $params[] = $noteMin;
        }
        // Filtre : uniquement les produits en stock
        if ($stockOnly) { $sql .= " AND p.stock > 0"; }
        // Filtre : uniquement les produits en solde
        if ($soldesOnly) { $sql .= " AND p.solde_prix IS NOT NULL AND p.solde_prix > 0"; }
        // Tri par date de création décroissante avec limite et offset
        $sql .= " ORDER BY p.date_creation DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Compte le nombre de produits correspondant aux mêmes filtres que getByCategorieWithFilters (sans pagination)
    public function countByCategorieWithFilters($categorieId = null, $recherche = null, $minPrix = null, $maxPrix = null, $tailles = null, $couleurs = null, $matieres = null, $noteMin = null, $stockOnly = false, $soldesOnly = false) {
        // Requête de comptage avec les mêmes filtres
        $sql = "SELECT COUNT(*) FROM produit p LEFT JOIN categorie c ON p.categorie_id = c.id WHERE p.statut = 1";
        $params = [];
        // Mêmes filtres que getByCategorieWithFilters (voir ci-dessus pour les commentaires détaillés)
        if ($categorieId) {
            if (is_string($categorieId) && strpos($categorieId, ',') !== false) {
                $ids = array_map('intval', explode(',', $categorieId));
                $ids = array_filter($ids);
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql .= " AND p.categorie_id IN ($placeholders)";
                    $params = array_merge($params, $ids);
                }
            } else {
                $sql .= " AND p.categorie_id = ?";
                $params[] = intval($categorieId);
            }
        }
        if ($recherche) { $sql .= " AND (p.nom LIKE ? OR p.description LIKE ?)"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; }
        if ($minPrix !== null) { $sql .= " AND p.prix >= ?"; $params[] = $minPrix; }
        if ($maxPrix !== null) { $sql .= " AND p.prix <= ?"; $params[] = $maxPrix; }
        if ($tailles && is_array($tailles) && !empty($tailles)) {
            $sizeConditions = [];
            foreach ($tailles as $t) {
                $sizeConditions[] = "FIND_IN_SET(?, p.taille_disponible)";
                $params[] = trim($t);
            }
            $sql .= " AND (" . implode(' OR ', $sizeConditions) . ")";
        }
        if ($noteMin !== null) {
            $sql .= " AND (SELECT COALESCE(AVG(note), 0) FROM avis WHERE produit_id = p.id) >= ?";
            $params[] = $noteMin;
        }
        if ($stockOnly) { $sql .= " AND p.stock > 0"; }
        if ($soldesOnly) { $sql .= " AND p.solde_prix IS NOT NULL AND p.solde_prix > 0"; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
