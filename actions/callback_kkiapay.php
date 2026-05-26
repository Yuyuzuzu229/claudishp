<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Kkiapay.php';
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$transactionId = $input['transaction_id'] ?? $input['transactionId'] ?? '';
$status = $input['status'] ?? '';
$commandeId = $input['data'] ?? '';

if (empty($transactionId)) {
    http_response_code(400);
    exit('Missing transaction_id');
}

$kkiapay = new Kkiapay();
$resultat = $kkiapay->verifierPaiement($transactionId);

if ($resultat && $resultat['success']) {
    $pdo = getPdo();
    $stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', token = ?, date_paiement = NOW() WHERE commande_id = ? AND statut = 'En attente'");
    $stmt->execute([$transactionId, $commandeId]);

    $stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ? AND statut != 'Confirmée'");
    $stmt->execute([$commandeId]);
}

http_response_code(200);
exit('OK');
