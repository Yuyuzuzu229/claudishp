<?php
// Inclut le fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe Avis : gère les avis et évaluations des produits par les utilisateurs
class Avis {
    // Instance de la connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère tous les avis avec les noms du produit et de l'utilisateur, triés par date récente
    public function getAll() {
        // Exécute une requête JOIN entre avis, produit et utilisateur, ordonnée par date de création descendante
        return $this->db->query("SELECT a.*, p.nom as produit_nom, u.nom, u.prenom FROM avis a JOIN produit p ON a.produit_id = p.id JOIN utilisateur u ON a.utilisateur_id = u.id ORDER BY a.date_creation DESC")->fetchAll();
    }

    // Récupère les avis filtrés par note et/ou texte de recherche
    public function getFiltered(?int $note, ?string $search) {
        $sql = "SELECT a.*, p.nom as produit_nom, u.nom, u.prenom FROM avis a JOIN produit p ON a.produit_id = p.id JOIN utilisateur u ON a.utilisateur_id = u.id WHERE 1=1";
        $params = [];
        if ($note !== null && $note >= 1 && $note <= 5) {
            $sql .= " AND a.note = ?";
            $params[] = $note;
        }
        if ($search !== null && $search !== '') {
            $sql .= " AND (a.commentaire LIKE ? OR p.nom LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        $sql .= " ORDER BY a.date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Récupère un avis spécifique par son identifiant, avec les infos produit et utilisateur
    public function getById($id) {
        // Prépare la requête avec jointures pour un ID donné
        $stmt = $this->db->prepare("SELECT a.*, p.nom as produit_nom, u.nom, u.prenom FROM avis a JOIN produit p ON a.produit_id = p.id JOIN utilisateur u ON a.utilisateur_id = u.id WHERE a.id = ?");
        // Exécute avec l'ID
        $stmt->execute([$id]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Récupère tous les avis publiés pour un produit donné
    public function getByProduit($produitId) {
        // Prépare la requête pour les avis d'un produit avec statut 'Publié', triés par date descendante
        $stmt = $this->db->prepare("SELECT a.*, u.nom, u.prenom FROM avis a JOIN utilisateur u ON a.utilisateur_id = u.id WHERE a.produit_id = ? AND a.statut = 'Publié' ORDER BY a.date_creation DESC");
        // Exécute avec l'ID du produit
        $stmt->execute([$produitId]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Récupère tous les avis laissés par un utilisateur spécifique
    public function getByUtilisateur($userId) {
        // Prépare la requête pour les avis d'un utilisateur avec le nom du produit
        $stmt = $this->db->prepare("SELECT a.*, p.nom as produit_nom FROM avis a JOIN produit p ON a.produit_id = p.id WHERE a.utilisateur_id = ? ORDER BY a.date_creation DESC");
        // Exécute avec l'ID utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Vérifie si un utilisateur a déjà laissé un avis sur un produit
    public function aDejaAvis($userId, $produitId) {
        // Compte le nombre d'avis existant pour cet utilisateur et ce produit
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM avis WHERE utilisateur_id = ? AND produit_id = ?");
        // Exécute avec les deux IDs
        $stmt->execute([$userId, $produitId]);
        // Retourne true si le compte est supérieur à 0, false sinon
        return $stmt->fetchColumn() > 0;
    }

    // Récupère les produits achetés par un utilisateur pour lesquels il peut donner un avis
    public function getProduitsAchetables($userId) {
        // Prépare une requête qui sélectionne les produits distincts des commandes validées
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.nom, p.photo, p.prix
            FROM ligne_commande lc
            JOIN commande c ON lc.commande_id = c.id
            JOIN produit p ON lc.produit_id = p.id
            WHERE c.utilisateur_id = ?
              AND c.statut IN ('Confirmée','En préparation','En route','En livraison','Livrée')
            ORDER BY p.nom
        ");
        // Exécute avec l'ID utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Récupère les derniers avis publiés, avec une limite configurable (défaut : 6)
    public function getPublies($limit = 6) {
        // Prépare la requête avec une limite pour les avis au statut 'Publié'
        $stmt = $this->db->prepare("SELECT a.*, u.nom, u.prenom FROM avis a JOIN utilisateur u ON a.utilisateur_id = u.id WHERE a.statut = 'Publié' ORDER BY a.date_creation DESC LIMIT ?");
        // Exécute avec la limite
        $stmt->execute([$limit]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Retourne le nombre total d'avis dans la base
    public function getNombre() {
        // Exécute une requête de comptage simple
        return $this->db->query("SELECT COUNT(*) FROM avis")->fetchColumn();
    }

    // Ajoute un nouvel avis et met à jour la note moyenne du produit
    public function ajouter($produitId, $utilisateurId, $note, $commentaire) {
        // Insère un nouvel avis avec le statut 'En modération' par défaut
        $stmt = $this->db->prepare("INSERT INTO avis (produit_id, utilisateur_id, note, commentaire, statut) VALUES (?, ?, ?, ?, 'En modération')");
        $result = $stmt->execute([$produitId, $utilisateurId, $note, $commentaire]);
        // Si l'insertion a réussi, met à jour la note moyenne du produit
        if ($result) $this->mettreAJourNoteMoyenne($produitId);
        // Retourne le résultat de l'insertion
        return $result;
    }

    // Modifie le statut d'un avis (ex: 'Publié', 'Masqué') et met à jour la note moyenne
    public function updateStatut($id, $statut) {
        // Met à jour le statut de l'avis
        $stmt = $this->db->prepare("UPDATE avis SET statut = ? WHERE id = ?");
        $result = $stmt->execute([$statut, $id]);
        // Si la mise à jour a réussi
        if ($result) {
            // Récupère l'avis pour obtenir l'ID du produit
            $a = $this->getById($id);
            // Si l'avis existe, met à jour la note moyenne du produit concerné
            if ($a) $this->mettreAJourNoteMoyenne($a['produit_id']);
        }
        // Retourne le résultat de la mise à jour
        return $result;
    }

    // Supprime un avis et met à jour la note moyenne du produit
    public function supprimer($id) {
        // Récupère l'avis avant suppression pour avoir l'ID du produit
        $a = $this->getById($id);
        // Prépare et exécute la suppression
        $stmt = $this->db->prepare("DELETE FROM avis WHERE id = ?");
        $result = $stmt->execute([$id]);
        // Si la suppression a réussi et que l'avis existait, met à jour la note moyenne
        if ($result && $a) $this->mettreAJourNoteMoyenne($a['produit_id']);
        // Retourne le résultat
        return $result;
    }

    // Méthode privée : recalcule et met à jour la note moyenne d'un produit
    // en faisant la moyenne des notes des avis publiés
    private function mettreAJourNoteMoyenne($produitId) {
        // Calcule la moyenne des notes (ou 0 si aucun avis) pour les avis publiés du produit
        $stmt = $this->db->prepare("UPDATE produit SET note_moyenne = (SELECT COALESCE(AVG(note), 0) FROM avis WHERE produit_id = ? AND statut = 'Publié') WHERE id = ?");
        // Exécute la mise à jour
        return $stmt->execute([$produitId, $produitId]);
    }
}
