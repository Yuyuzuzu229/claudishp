<?php
// Inclut le fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe Commande : gère les commandes clients, les lignes de commande et les statistiques
class Commande {
    // Instance de la connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère toutes les commandes avec les noms des utilisateurs, triées par date descendante
    public function getAll() {
        // Jointure entre commande et utilisateur pour obtenir le nom et prénom du client
        return $this->db->query("SELECT c.*, u.nom, u.prenom FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id ORDER BY c.date_commande DESC")->fetchAll();
    }

    // Récupère une commande spécifique avec les détails complets de l'utilisateur
    public function getById($id) {
        // Prépare la requête avec jointure pour obtenir email et téléphone du client
        $stmt = $this->db->prepare("SELECT c.*, u.nom, u.prenom, u.email, u.telephone FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id WHERE c.id = ?");
        // Exécute avec l'ID de la commande
        $stmt->execute([$id]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Récupère toutes les commandes d'un utilisateur spécifique
    public function getByUtilisateur($userId) {
        // Prépare la requête filtrée par utilisateur, triée par date descendante
        $stmt = $this->db->prepare("SELECT * FROM commande WHERE utilisateur_id = ? ORDER BY date_commande DESC");
        // Exécute avec l'ID utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Recherche des commandes par ID, nom ou prénom du client (recherche partielle LIKE)
    public function search($q) {
        // Prépare une requête avec trois critères de recherche (ID, nom, prénom)
        $stmt = $this->db->prepare("SELECT c.*, u.nom, u.prenom FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id WHERE c.id LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? ORDER BY c.date_commande DESC");
        // Ajoute les wildcards % pour la recherche partielle
        $like = "%$q%";
        // Exécute avec le même pattern pour les trois champs
        $stmt->execute([$like, $like, $like]);
        // Retourne toutes les correspondances
        return $stmt->fetchAll();
    }

    // Retourne le nombre total de commandes
    public function getNombre() {
        // Exécute un comptage simple
        return $this->db->query("SELECT COUNT(*) FROM commande")->fetchColumn();
    }

    // Retourne le montant total des ventes (somme des commandes non annulées)
    public function getTotalVentes() {
        // Calcule la somme des montants des commandes non annulées (COALESCE pour éviter NULL)
        return $this->db->query("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE statut != 'Annulée'")->fetchColumn();
    }

    // Récupère les dernières commandes avec une limite configurable (défaut : 5)
    public function getDernieresCommandes($limite = 5) {
        // Prépare la requête avec une limite dynamique
        $stmt = $this->db->prepare("SELECT c.*, u.nom, u.prenom FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.id ORDER BY c.date_commande DESC LIMIT ?");
        // Exécute avec la limite
        $stmt->execute([$limite]);
        // Retourne les lignes
        return $stmt->fetchAll();
    }

    // Récupère les statistiques mensuelles (nombre de commandes et revenus) des 6 derniers mois
    public function getStatistiquesMois() {
        // Groupe par mois en formatant la date, calcule le nombre et le total des revenus pour les commandes non annulées
        return $this->db->query("SELECT DATE_FORMAT(date_commande, '%Y-%m') as mois, COUNT(*) as nb_commandes, SUM(montant_total) as revenus FROM commande WHERE statut != 'Annulée' GROUP BY mois ORDER BY mois DESC LIMIT 6")->fetchAll();
    }

    // Retourne le nombre de commandes passées aujourd'hui
    public function getCommandesDuJour() {
        // Compte les commandes dont la date correspond à la date courante
        return $this->db->query("SELECT COUNT(*) FROM commande WHERE DATE(date_commande) = CURDATE()")->fetchColumn();
    }

    // Retourne le nombre de commandes en cours (tous statuts sauf Livrée et Annulée)
    public function getEnCours() {
        // Compte les commandes qui ne sont ni livrées ni annulées
        return $this->db->query("SELECT COUNT(*) FROM commande WHERE statut NOT IN ('Livrée','Annulée')")->fetchColumn();
    }

    // Retourne le nombre de commandes annulées
    public function getAnnulees() {
        // Compte les commandes avec le statut 'Annulée'
        return $this->db->query("SELECT COUNT(*) FROM commande WHERE statut = 'Annulée'")->fetchColumn();
    }

    // Récupère les lignes d'une commande avec le nom et la photo du produit
    public function getLignes($commandeId) {
        // Jointure entre ligne_commande et produit pour obtenir les détails de chaque ligne
        $stmt = $this->db->prepare("SELECT lc.*, p.nom, p.photo FROM ligne_commande lc JOIN produit p ON lc.produit_id = p.id WHERE lc.commande_id = ?");
        // Exécute avec l'ID de la commande
        $stmt->execute([$commandeId]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Crée une nouvelle commande avec gestion de transaction (mode retrait/livraison)
    public function creer($utilisateurId, $montantTotal, $modeRetrait, $adresse = null, $nomComplet = null, $telephone = null, $instructions = null, $latitude = null, $longitude = null, $idZone = null) {
        // Démarre une transaction pour garantir l'intégrité des données
        $this->db->beginTransaction();
        try {
            // Insère la commande avec tous les paramètres
            $stmt = $this->db->prepare("INSERT INTO commande (utilisateur_id, montant_total, mode_retrait, adresse_livraison, nom_complet, telephone, instructions, latitude_client, longitude_client, id_zone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$utilisateurId, $montantTotal, $modeRetrait, $adresse, $nomComplet, $telephone, $instructions, $latitude, $longitude, $idZone]);
            // Récupère l'ID de la commande insérée
            $commandeId = $this->db->lastInsertId();
            // Valide la transaction
            $this->db->commit();
            // Retourne l'ID de la nouvelle commande
            return $commandeId;
        } catch (Exception $e) {
            // En cas d'erreur, annule la transaction
            $this->db->rollBack();
            // Retourne false pour signaler l'échec
            return false;
        }
    }

    // Ajoute plusieurs lignes de produits à une commande (panier)
    public function ajouterLignes($commandeId, $lignes) {
        // Prépare une requête d'insertion pour une ligne de commande
        $stmt = $this->db->prepare("INSERT INTO ligne_commande (commande_id, produit_id, quantite, prix_unitaire, taille) VALUES (?, ?, ?, ?, ?)");
        // Prépare la décrémentation du stock
        $stmtStock = $this->db->prepare("UPDATE produit SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        // Parcourt chaque ligne du panier
        foreach ($lignes as $l) {
            // Exécute l'insertion pour chaque produit
            $taille = $l['taille'] ?? null;
            $stmt->execute([$commandeId, $l['produit_id'], $l['quantite'], $l['prix_unitaire'], $taille]);
            // Décrémente le stock en conséquence
            $stmtStock->execute([$l['quantite'], $l['produit_id']]);
        }
    }

    // Met à jour le statut d'une commande
    public function updateStatut($id, $statut) {
        // Prépare la mise à jour du statut
        $stmt = $this->db->prepare("UPDATE commande SET statut = ? WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$statut, $id]);
    }

    // Annule une commande et libère le livreur assigné si existant
    public function annuler($id) {
        // Met le statut de la commande à 'Annulée'
        $result = $this->updateStatut($id, 'Annulée');
        // Inclut la classe Livraison pour libérer le livreur
        require_once __DIR__ . '/Livraison.php';
        $liv = new Livraison();
        // Libère le livreur associé à cette commande
        $liv->libererLivreurParCommande($id);
        // Retourne le résultat de la mise à jour de statut
        return $result;
    }

    // Récupère le statut actuel d'une commande pour le suivi
    public function suivreStatut($id) {
        // Récupère la commande par son ID
        $c = $this->getById($id);
        // Retourne le statut si la commande existe, sinon null
        return $c ? $c['statut'] : null;
    }

    // Récupère le total des ventes des 7 derniers jours, groupé par jour
    public function getVentes7Jours() {
        // Sélectionne la date et la somme des montants pour les 7 derniers jours, commandes non annulées
        return $this->db->query("SELECT DATE(date_commande) as jour, SUM(montant_total) as total FROM commande WHERE date_commande >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND statut != 'Annulée' GROUP BY DATE(date_commande) ORDER BY jour")->fetchAll();
    }

    // Calcule la répartition des commandes par statut avec pourcentages
    public function getRepartitionStatuts() {
        // Groupe par statut, compte le nombre et calcule le pourcentage par rapport au total
        return $this->db->query("SELECT statut, COUNT(*) as total, COUNT(*) * 100.0 / (SELECT COUNT(*) FROM commande) as pourcentage FROM commande GROUP BY statut")->fetchAll();
    }

    public function getNombreByPeriode($debut, $fin) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM commande WHERE date_commande BETWEEN ? AND ?");
        $stmt->execute([$debut, $fin]);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalVentesByPeriode($debut, $fin) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE date_commande BETWEEN ? AND ? AND statut NOT IN ('Annulée', 'Échouée')");
        $stmt->execute([$debut, $fin]);
        return (float)$stmt->fetchColumn();
    }

    public function getByUtilisateurByPeriode($userId, $debut, $fin) {
        $stmt = $this->db->prepare("SELECT * FROM commande WHERE utilisateur_id = ? AND date_commande BETWEEN ? AND ? ORDER BY date_commande DESC");
        $stmt->execute([$userId, $debut, $fin]);
        return $stmt->fetchAll();
    }

    public function getTotalDepensesByPeriode($userId, $debut, $fin) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE utilisateur_id = ? AND date_commande BETWEEN ? AND ? AND statut NOT IN ('Annulée', 'Échouée')");
        $stmt->execute([$userId, $debut, $fin]);
        return (float)$stmt->fetchColumn();
    }
}
