<?php
require_once __DIR__ . '/../config/database.php';

class Panier {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPanierActif($utilisateurId) {
        $stmt = $this->db->prepare("SELECT id FROM panier WHERE utilisateur_id = ? AND est_actif = 1 LIMIT 1");
        $stmt->execute([$utilisateurId]);
        $panier = $stmt->fetch();
        if ($panier) return $panier['id'];
        try {
            $stmt = $this->db->prepare("INSERT INTO panier (utilisateur_id) VALUES (?)");
            $stmt->execute([$utilisateurId]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // FK violation : utilisateur supprimé → détruire la session
                $_SESSION = [];
                session_destroy();
                redirect(BASE_URL . '/index.php');
            }
            throw $e;
        }
    }

    public function getLignes($panierId) {
        $stmt = $this->db->prepare("SELECT lp.*, p.nom, p.photo, p.stock, p.prix as prix_actuel, p.solde_prix FROM ligne_panier lp JOIN produit p ON lp.produit_id = p.id WHERE lp.panier_id = ?");
        $stmt->execute([$panierId]);
        return $stmt->fetchAll();
    }

    public function getNombreArticles($panierId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantite), 0) FROM ligne_panier WHERE panier_id = ?");
        $stmt->execute([$panierId]);
        return $stmt->fetchColumn();
    }

    public function calculerTotal($panierId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(lp.quantite * lp.prix_unitaire), 0) FROM ligne_panier lp WHERE lp.panier_id = ?");
        $stmt->execute([$panierId]);
        return $stmt->fetchColumn();
    }

    public function ajouterProduit($panierId, $produitId, $quantite = 1) {
        $exist = $this->db->prepare("SELECT id, quantite FROM ligne_panier WHERE panier_id = ? AND produit_id = ?");
        $exist->execute([$panierId, $produitId]);
        $ligne = $exist->fetch();
        if ($ligne) {
            $stmt = $this->db->prepare("UPDATE ligne_panier SET quantite = quantite + ? WHERE id = ?");
            $ok = $stmt->execute([$quantite, $ligne['id']]);
            return $ok
                ? ['success' => true, 'message' => 'Quantité mise à jour dans le panier']
                : ['success' => false, 'message' => 'Erreur lors de la mise à jour du panier'];
        }
        $prod = $this->db->prepare("SELECT prix, solde_prix FROM produit WHERE id = ?");
        $prod->execute([$produitId]);
        $p = $prod->fetch();
        $prixUnitaire = ($p['solde_prix'] && $p['solde_prix'] > 0) ? $p['solde_prix'] : $p['prix'];
        $stmt = $this->db->prepare("INSERT INTO ligne_panier (panier_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
        $ok = $stmt->execute([$panierId, $produitId, $quantite, $prixUnitaire]);
        return $ok
            ? ['success' => true, 'message' => 'Produit ajouté au panier']
            : ['success' => false, 'message' => 'Erreur lors de l\'ajout au panier'];
    }

    public function modifierQuantite($ligneId, $quantite) {
        if ($quantite <= 0) return $this->supprimerLigne($ligneId);
        $stmt = $this->db->prepare("UPDATE ligne_panier SET quantite = ? WHERE id = ?");
        return $stmt->execute([$quantite, $ligneId]);
    }

    public function supprimerProduit($ligneId) {
        return $this->supprimerLigne($ligneId);
    }

    public function viderPanier($panierId) {
        return $this->vider($panierId);
    }

    public function supprimerLigne($ligneId) {
        $stmt = $this->db->prepare("DELETE FROM ligne_panier WHERE id = ?");
        return $stmt->execute([$ligneId]);
    }

    public function vider($panierId) {
        $stmt = $this->db->prepare("DELETE FROM ligne_panier WHERE panier_id = ?");
        return $stmt->execute([$panierId]);
    }

    // ── GUEST CART (session-based) ─────────────────────────────────
    public function getGuestCart() {
        return $_SESSION['guest_cart'] ?? [];
    }

    public function guestAjouterProduit($produitId, $quantite = 1) {
        $cart = $this->getGuestCart();
        foreach ($cart as &$item) {
            if ($item['produit_id'] == $produitId) {
                $item['quantite'] += $quantite;
                $_SESSION['guest_cart'] = $cart;
                return ['success' => true, 'message' => 'Quantité mise à jour dans le panier'];
            }
        }
        $stmt = $this->db->prepare("SELECT nom, prix, solde_prix, photo, stock FROM produit WHERE id = ?");
        $stmt->execute([$produitId]);
        $p = $stmt->fetch();
        if (!$p) return ['success' => false, 'message' => 'Produit introuvable'];
        $prixUnitaire = ($p['solde_prix'] && $p['solde_prix'] > 0) ? $p['solde_prix'] : $p['prix'];
        $cart[] = [
            'produit_id' => $produitId, 'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire, 'nom' => $p['nom'],
            'photo' => $p['photo'], 'stock' => $p['stock'],
            'prix_actuel' => $p['prix'], 'solde_prix' => $p['solde_prix'],
        ];
        $_SESSION['guest_cart'] = $cart;
        return ['success' => true, 'message' => 'Produit ajouté au panier'];
    }

    public function guestGetLignes() {
        return $this->getGuestCart();
    }

    public function guestCalculerTotal() {
        $total = 0;
        foreach ($this->getGuestCart() as $item) $total += $item['prix_unitaire'] * $item['quantite'];
        return $total;
    }

    public function guestGetNombreArticles() {
        $count = 0;
        foreach ($this->getGuestCart() as $item) $count += $item['quantite'];
        return $count;
    }

    public function guestSupprimerLigne($index) {
        $cart = $this->getGuestCart();
        if (isset($cart[$index])) { array_splice($cart, $index, 1); $_SESSION['guest_cart'] = $cart; }
    }

    public function guestModifierQuantite($index, $quantite) {
        $cart = $this->getGuestCart();
        if (isset($cart[$index])) {
            if ($quantite <= 0) { $this->guestSupprimerLigne($index); return; }
            $cart[$index]['quantite'] = $quantite;
            $_SESSION['guest_cart'] = $cart;
        }
    }

    public function guestVider() {
        unset($_SESSION['guest_cart']);
    }

    public function transferGuestToDb($utilisateurId) {
        $cart = $this->getGuestCart();
        if (empty($cart)) return;
        $panierId = $this->getPanierActif($utilisateurId);
        foreach ($cart as $item) $this->ajouterProduit($panierId, $item['produit_id'], $item['quantite']);
        $this->guestVider();
    }

    public function verifierProduitsDisponibles($panierId) {
        $lignes = $this->getLignes($panierId);
        foreach ($lignes as $l) {
            if ($l['stock'] < $l['quantite']) return false;
        }
        return true;
    }
}
