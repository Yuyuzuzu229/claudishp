<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Produit.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); exit; }
    redirect(BASE_URL . '/index.php');
}

$produitId = intval($_POST['produit_id']);
$quantite = intval($_POST['quantite'] ?? 1);
$quantite = max(1, $quantite);

$panier = new Panier();

if (isLoggedIn()) {
    $panierId = $panier->getPanierActif($_SESSION['user_id']);
    $resultat = $panier->ajouterProduit($panierId, $produitId, $quantite);
    $nbArticles = $panier->getNombreArticles($panierId);
} else {
    $resultat = $panier->guestAjouterProduit($produitId, $quantite);
    $nbArticles = $panier->guestGetNombreArticles();
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $resultat['success'],
        'message' => $resultat['message'],
        'count' => $nbArticles
    ]);
    exit;
}

$_SESSION['flash'] = $resultat['success']
    ? ['type' => 'success', 'message' => $resultat['message']]
    : ['type' => 'danger', 'message' => $resultat['message']];

$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/pages/panier.php';
redirect($referer);
