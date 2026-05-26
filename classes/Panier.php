<?php
// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe gérant le panier d'achat (utilisateurs connectés et invités)
class Panier {
    // Instance de connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère le panier actif d'un utilisateur, ou en crée un nouveau si aucun n'existe
    public function getPanierActif($utilisateurId) {
        // Recherche d'un panier actif existant pour cet utilisateur
        $stmt = $this->db->prepare("SELECT id FROM panier WHERE utilisateur_id = ? AND est_actif = 1 LIMIT 1");
        $stmt->execute([$utilisateurId]);
        $panier = $stmt->fetch();
        // Si un panier actif existe, retourne son identifiant
        if ($panier) return $panier['id'];
        // Sinon, tente d'en créer un nouveau
        try {
            $stmt = $this->db->prepare("INSERT INTO panier (utilisateur_id) VALUES (?)");
            $stmt->execute([$utilisateurId]);
            // Retourne l'identifiant du nouveau panier inséré
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Si une erreur de contrainte (FK) se produit (utilisateur supprimé)
            if ($e->getCode() == 23000) {
                // FK violation : utilisateur supprimé → détruire la session
                $_SESSION = [];
                session_destroy();
                // Redirige vers la page d'accueil
                redirect(BASE_URL . '/index.php');
            }
            // Relance l'exception si ce n'est pas une erreur FK
            throw $e;
        }
    }

    // Récupère toutes les lignes (produits) d'un panier avec les informations produit associées
    public function getLignes($panierId) {
        // Jointure entre ligne_panier et produit pour obtenir nom, photo, stock, prix et solde
        $stmt = $this->db->prepare("SELECT lp.*, p.nom, p.photo, p.stock, p.prix as prix_actuel, p.solde_prix FROM ligne_panier lp JOIN produit p ON lp.produit_id = p.id WHERE lp.panier_id = ?");
        $stmt->execute([$panierId]);
        return $stmt->fetchAll();
    }

    // Calcule le nombre total d'articles dans un panier (somme des quantités)
    public function getNombreArticles($panierId) {
        // Somme des quantités avec gestion des NULL (COALESCE)
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantite), 0) FROM ligne_panier WHERE panier_id = ?");
        $stmt->execute([$panierId]);
        return $stmt->fetchColumn();
    }

    // Calcule le montant total du panier (somme des quantité × prix unitaire)
    public function calculerTotal($panierId) {
        // Multiplication quantité * prix_unitaire pour chaque ligne, puis somme
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(lp.quantite * lp.prix_unitaire), 0) FROM ligne_panier lp WHERE lp.panier_id = ?");
        $stmt->execute([$panierId]);
        return $stmt->fetchColumn();
    }

    // Ajoute un produit au panier (ou augmente la quantité si déjà présent avec la même taille)
    public function ajouterProduit($panierId, $produitId, $quantite = 1, $taille = null) {
        // Vérifie si le produit existe déjà dans le panier avec la même taille
        if ($taille) {
            $exist = $this->db->prepare("SELECT id, quantite FROM ligne_panier WHERE panier_id = ? AND produit_id = ? AND taille = ?");
            $exist->execute([$panierId, $produitId, $taille]);
        } else {
            $exist = $this->db->prepare("SELECT id, quantite FROM ligne_panier WHERE panier_id = ? AND produit_id = ? AND taille IS NULL");
            $exist->execute([$panierId, $produitId]);
        }
        $ligne = $exist->fetch();
        // Si le produit est déjà dans le panier avec la même taille, on met à jour la quantité
        if ($ligne) {
            $stmt = $this->db->prepare("UPDATE ligne_panier SET quantite = quantite + ? WHERE id = ?");
            $ok = $stmt->execute([$quantite, $ligne['id']]);
            return $ok
                ? ['success' => true, 'message' => 'Quantité mise à jour dans le panier']
                : ['success' => false, 'message' => 'Erreur lors de la mise à jour du panier'];
        }
        // Sinon, on récupère le prix du produit (avec gestion du solde)
        $prod = $this->db->prepare("SELECT prix, solde_prix FROM produit WHERE id = ?");
        $prod->execute([$produitId]);
        $p = $prod->fetch();
        // Le prix unitaire est le prix soldé si disponible, sinon le prix normal
        $prixUnitaire = ($p['solde_prix'] && $p['solde_prix'] > 0) ? $p['solde_prix'] : $p['prix'];
        // Insertion d'une nouvelle ligne dans le panier
        $stmt = $this->db->prepare("INSERT INTO ligne_panier (panier_id, produit_id, quantite, prix_unitaire, taille) VALUES (?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$panierId, $produitId, $quantite, $prixUnitaire, $taille]);
        return $ok
            ? ['success' => true, 'message' => 'Produit ajouté au panier']
            : ['success' => false, 'message' => 'Erreur lors de l\'ajout au panier'];
    }

    // Modifie la quantité d'une ligne de panier (supprime si quantité ≤ 0)
    public function modifierQuantite($ligneId, $quantite) {
        // Si la quantité est ≤ 0, on supprime la ligne
        if ($quantite <= 0) return $this->supprimerLigne($ligneId);
        // Sinon, mise à jour de la quantité
        $stmt = $this->db->prepare("UPDATE ligne_panier SET quantite = ? WHERE id = ?");
        return $stmt->execute([$quantite, $ligneId]);
    }

    // Supprime un produit du panier (délègue à supprimerLigne)
    public function supprimerProduit($ligneId) {
        return $this->supprimerLigne($ligneId);
    }

    // Vide complètement le panier (délègue à vider)
    public function viderPanier($panierId) {
        return $this->vider($panierId);
    }

    // Supprime une ligne spécifique du panier par son identifiant
    public function supprimerLigne($ligneId) {
        $stmt = $this->db->prepare("DELETE FROM ligne_panier WHERE id = ?");
        return $stmt->execute([$ligneId]);
    }

    // Supprime toutes les lignes d'un panier donné
    public function vider($panierId) {
        $stmt = $this->db->prepare("DELETE FROM ligne_panier WHERE panier_id = ?");
        return $stmt->execute([$panierId]);
    }

    // ── GUEST CART (session-based) ─────────────────────────────────
    // Récupère le panier invité stocké en session
    public function getGuestCart() {
        return $_SESSION['guest_cart'] ?? [];
    }

    // Ajoute un produit au panier invité (session), ou augmente la quantité si déjà présent avec la même taille
    public function guestAjouterProduit($produitId, $quantite = 1, $taille = null) {
        // Récupération du panier invité actuel
        $cart = $this->getGuestCart();
        // Parcourt le panier pour voir si le produit existe déjà avec la même taille
        foreach ($cart as &$item) {
            // Si le produit est trouvé avec la même taille, on augmente sa quantité
            if ($item['produit_id'] == $produitId && ($item['taille'] ?? null) == $taille) {
                $item['quantite'] += $quantite;
                $_SESSION['guest_cart'] = $cart;
                return ['success' => true, 'message' => 'Quantité mise à jour dans le panier'];
            }
        }
        // Sinon, on récupère les informations du produit depuis la base
        $stmt = $this->db->prepare("SELECT nom, prix, solde_prix, photo, stock FROM produit WHERE id = ?");
        $stmt->execute([$produitId]);
        $p = $stmt->fetch();
        // Si le produit n'existe pas, retourne une erreur
        if (!$p) return ['success' => false, 'message' => 'Produit introuvable'];
        // Détermination du prix unitaire (solde ou prix normal)
        $prixUnitaire = ($p['solde_prix'] && $p['solde_prix'] > 0) ? $p['solde_prix'] : $p['prix'];
        // Ajout du produit au tableau du panier invité
        $cart[] = [
            'produit_id' => $produitId, 'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire, 'nom' => $p['nom'],
            'photo' => $p['photo'], 'stock' => $p['stock'],
            'prix_actuel' => $p['prix'], 'solde_prix' => $p['solde_prix'],
            'taille' => $taille,
        ];
        // Sauvegarde du panier mis à jour en session
        $_SESSION['guest_cart'] = $cart;
        return ['success' => true, 'message' => 'Produit ajouté au panier'];
    }

    // Retourne les lignes du panier invité (alias de getGuestCart)
    public function guestGetLignes() {
        return $this->getGuestCart();
    }

    // Calcule le montant total du panier invité
    public function guestCalculerTotal() {
        $total = 0;
        // Parcourt tous les articles du panier invité
        foreach ($this->getGuestCart() as $item) $total += $item['prix_unitaire'] * $item['quantite'];
        return $total;
    }

    // Retourne le nombre total d'articles dans le panier invité
    public function guestGetNombreArticles() {
        $count = 0;
        // Parcourt tous les articles et additionne les quantités
        foreach ($this->getGuestCart() as $item) $count += $item['quantite'];
        return $count;
    }

    // Supprime une ligne du panier invité par son index
    public function guestSupprimerLigne($index) {
        $cart = $this->getGuestCart();
        // Si l'index existe, on le supprime du tableau et on met à jour la session
        if (isset($cart[$index])) { array_splice($cart, $index, 1); $_SESSION['guest_cart'] = $cart; }
    }

    // Modifie la quantité d'un article dans le panier invité par son index
    public function guestModifierQuantite($index, $quantite) {
        $cart = $this->getGuestCart();
        // Vérifie que l'index existe
        if (isset($cart[$index])) {
            // Si quantité ≤ 0, supprime la ligne
            if ($quantite <= 0) { $this->guestSupprimerLigne($index); return; }
            // Sinon, met à jour la quantité et sauvegarde en session
            $cart[$index]['quantite'] = $quantite;
            $_SESSION['guest_cart'] = $cart;
        }
    }

    // Vide complètement le panier invité
    public function guestVider() {
        unset($_SESSION['guest_cart']);
    }

    // Transfère le panier invité (session) vers le panier d'un utilisateur connecté (base de données)
    public function transferGuestToDb($utilisateurId) {
        // Récupère le panier invité
        $cart = $this->getGuestCart();
        // Si le panier invité est vide, on ne fait rien
        if (empty($cart)) return;
        // Récupère ou crée le panier actif de l'utilisateur
        $panierId = $this->getPanierActif($utilisateurId);
        // Parcourt chaque article du panier invité et l'ajoute dans le panier base de données
        foreach ($cart as $item) $this->ajouterProduit($panierId, $item['produit_id'], $item['quantite'], $item['taille'] ?? null);
        // Vide le panier invité après transfert
        $this->guestVider();
    }

    // Vérifie que tous les produits d'un panier sont encore en stock en quantité suffisante
    public function verifierProduitsDisponibles($panierId) {
        // Récupère toutes les lignes du panier
        $lignes = $this->getLignes($panierId);
        // Parcourt chaque ligne pour comparer stock et quantité demandée
        foreach ($lignes as $l) {
            // Si le stock est insuffisant pour un produit, retourne false
            if ($l['stock'] < $l['quantite']) return false;
        }
        // Si tous les produits sont disponibles, retourne true
        return true;
    }
}
