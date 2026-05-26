<?php
// Inclusion des fichiers de configuration et des classes nécessaires
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Produit.php';

// Vérifie si la requête est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Vérifie si la méthode HTTP est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si AJAX, retourne une réponse JSON, sinon redirige vers l'accueil
    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); exit; }
    redirect(BASE_URL . '/index.php');
}

// Récupère et sécurise les données du produit
$produitId = intval($_POST['produit_id']);
$quantite = intval($_POST['quantite'] ?? 1);
$taille = isset($_POST['taille']) && $_POST['taille'] !== '' ? securiser(trim($_POST['taille'])) : null;
// Force la quantité minimale à 1
$quantite = max(1, $quantite);

// Vérification du stock disponible
$dbStock = Database::getInstance()->getConnection();
$stmtStock = $dbStock->prepare("SELECT stock FROM produit WHERE id = ?");
$stmtStock->execute([$produitId]);
$produit = $stmtStock->fetch();
if (!$produit) {
    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Produit introuvable']); exit; }
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Produit introuvable.'];
    redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php');
}
$stockDisponible = intval($produit['stock']);
if ($stockDisponible <= 0) {
    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Ce produit est en rupture de stock']); exit; }
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ce produit est en rupture de stock.'];
    redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php');
}

// Crée une instance du panier
$panier = new Panier();

// Si l'utilisateur est connecté, utilise le panier en base de données
if (isLoggedIn()) {
    $panierId = $panier->getPanierActif($_SESSION['user_id']);
    // Vérifie la quantité déjà dans le panier pour ce produit
    $dbQte = Database::getInstance()->getConnection();
    if ($taille) {
        $stmtQte = $dbQte->prepare("SELECT COALESCE(SUM(quantite), 0) FROM ligne_panier WHERE panier_id = ? AND produit_id = ? AND taille = ?");
        $stmtQte->execute([$panierId, $produitId, $taille]);
    } else {
        $stmtQte = $dbQte->prepare("SELECT COALESCE(SUM(quantite), 0) FROM ligne_panier WHERE panier_id = ? AND produit_id = ? AND taille IS NULL");
        $stmtQte->execute([$panierId, $produitId]);
    }
    $qteExistante = intval($stmtQte->fetchColumn());
    if ($qteExistante + $quantite > $stockDisponible) {
        $reste = $stockDisponible - $qteExistante;
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Stock insuffisant. Disponible : ' . max(0, $reste)]); exit; }
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Stock insuffisant. Seulement ' . max(0, $reste) . ' article(s) disponible(s).'];
        redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php');
    }
    // Ajoute le produit au panier
    $resultat = $panier->ajouterProduit($panierId, $produitId, $quantite, $taille);
    $nbArticles = $panier->getNombreArticles($panierId);
} else {
    // Vérifie la quantité déjà dans le panier invité (même taille)
    $qteExistante = 0;
    foreach ($panier->getGuestCart() as $item) {
        if ($item['produit_id'] == $produitId && ($item['taille'] ?? null) == $taille) { $qteExistante = $item['quantite']; break; }
    }
    if ($qteExistante + $quantite > $stockDisponible) {
        $reste = $stockDisponible - $qteExistante;
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Stock insuffisant. Disponible : ' . max(0, $reste)]); exit; }
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Stock insuffisant. Seulement ' . max(0, $reste) . ' article(s) disponible(s).'];
        redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php');
    }
    // Sinon, utilise le panier invité en session
    $resultat = $panier->guestAjouterProduit($produitId, $quantite, $taille);
    $nbArticles = $panier->guestGetNombreArticles();
}

// Si la requête est AJAX, retourne les résultats au format JSON
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $resultat['success'],
        'message' => $resultat['message'],
        'count' => $nbArticles
    ]);
    exit;
}

// Définit un message flash selon le succès ou l'échec de l'opération
$_SESSION['flash'] = $resultat['success']
    ? ['type' => 'success', 'message' => $resultat['message']]
    : ['type' => 'danger', 'message' => $resultat['message']];

// Redirige vers la page précédente ou vers le panier
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/pages/panier.php';
redirect($referer);
