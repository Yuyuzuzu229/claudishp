<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/FedaPay.php';

$token = securiser($_GET['token'] ?? '');
$commandeId = intval($_GET['commande_id'] ?? 0);

if (empty($token)) {
    redirect(BASE_URL . '/index.php');
}

$fedapay = new FedaPay();
$fedapay->annulerPaiement($token);

if ($commandeId) {
    $pdo = getPdo();
    $stmt = $pdo->prepare("UPDATE paiement SET statut = 'Échoué' WHERE commande_id = ? AND statut = 'En attente'");
    $stmt->execute([$commandeId]);
    $stmt = $pdo->prepare("UPDATE commande SET statut = 'Annulée' WHERE id = ? AND statut = 'Confirmée'");
    $stmt->execute([$commandeId]);

    $stmt = $pdo->prepare("SELECT livreur_id FROM livraison WHERE commande_id = ? AND livreur_id IS NOT NULL");
    $stmt->execute([$commandeId]);
    $livreur = $stmt->fetch();
    if ($livreur && $livreur['livreur_id']) {
        $pdo->prepare("UPDATE livreur SET statut = 'Disponible' WHERE id = ?")->execute([$livreur['livreur_id']]);
    }
    require_once __DIR__ . '/../classes/Livraison.php';
    $liv = new Livraison();
    $liv->assignerAutomatique();
}

$_SESSION['error'] = 'Paiement annulé.';
redirect(BASE_URL . '/pages/panier.php');
