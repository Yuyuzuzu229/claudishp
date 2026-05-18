<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livraison.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$commandeId = intval($_POST['commande_id'] ?? 0);

if (!$commandeId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

$livraisonObj = new Livraison();
$livraison = $livraisonObj->getByCommande($commandeId);

if (!$livraison) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Livraison introuvable']);
    exit;
}

if ($livraison['statut'] === 'En route' || $livraison['statut'] === 'Prêt à expédier') {
    $livraisonObj->updateStatut($livraison['id'], 'En cours');
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
exit;
