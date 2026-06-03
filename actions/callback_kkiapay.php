<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Kkiapay.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$transactionId = $input['transaction_id'] ?? $input['transactionId'] ?? '';
$status = $input['status'] ?? '';
$commandeId = intval($input['data'] ?? '');

if (empty($transactionId) || !$commandeId) {
    http_response_code(400);
    exit('Missing parameters');
}

$kkiapay = new Kkiapay();
$resultat = $kkiapay->verifierPaiement($transactionId);

if ($resultat && $resultat['success']) {
    $pdo = getPdo();

    $stmt = $pdo->prepare("SELECT p.id, p.commande_id, c.utilisateur_id, c.montant_total FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.commande_id = ? AND p.statut = 'En attente' LIMIT 1");
    $stmt->execute([$commandeId]);
    $paiement = $stmt->fetch();

    if ($paiement) {
        // Récupère l'opérateur utilisé (mtn-benin, moov-benin, wave-benin)
        $operateur = !empty($resultat['source_common_name']) ? $resultat['source_common_name'] : 'kkiapay';
        $stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', token = ?, mode = ?, date_paiement = NOW() WHERE id = ?");
        $stmt->execute([$transactionId, $operateur, $paiement['id']]);

        // Met à jour aussi le mode_paiement dans commande pour l'affichage
        $stmt = $pdo->prepare("UPDATE commande SET mode_paiement = ? WHERE id = ?");
        $stmt->execute([$operateur, $paiement['commande_id']]);

        $stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ? AND statut NOT IN ('Confirmée','Livrée')");
        $stmt->execute([$commandeId]);

        $notif = new Notification();
        $notif->creer(
            $paiement['utilisateur_id'],
            'Paiement Kkiapay confirmé',
            'Votre paiement de ' . formatPrix($paiement['montant_total']) . ' via Kkiapay a été confirmé. Réf : ' . $transactionId
        );
    }
}

http_response_code(200);
exit('OK');
