<?php
// Callback appelé par FedaPay après le paiement via FedaCheckout
// FedaPay redirige vers : callback_url?token=CHECKOUT_TOKEN&reference=...
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/FedaPay.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Commande.php';

$checkoutToken = $_GET['token'] ?? '';
$reference = $_GET['reference'] ?? '';

if (empty($checkoutToken)) {
    $_SESSION['error'] = 'Aucun token de paiement reçu.';
    redirect(BASE_URL . '/index.php');
}

$pdo = getPdo();

// Chercher le paiement par le token FedaCheckout
$stmt = $pdo->prepare("SELECT p.*, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.fedacheckout_token = ?");
$stmt->execute([$checkoutToken]);
$paiement = $stmt->fetch();

if (!$paiement) {
    // Chercher par référence
    $stmt = $pdo->prepare("SELECT p.*, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.reference_transaction = ?");
    $stmt->execute([$reference]);
    $paiement = $stmt->fetch();
}

if (!$paiement) {
    $_SESSION['error'] = 'Paiement introuvable.';
    redirect(BASE_URL . '/index.php');
}

$fedapay = new FedaPay();
$resultat = $fedapay->verifierCheckout($checkoutToken);

if ($resultat['success']) {
    // Mettre à jour le paiement
    $stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = ?, date_paiement = NOW() WHERE id = ?");
    $stmt->execute([$resultat['reference_transaction'], $paiement['id']]);

    // Mettre à jour la commande
    $stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ?");
    $stmt->execute([$paiement['commande_id']]);

    // Notification
    $notif = new Notification();
    $notif->creer(
        $paiement['utilisateur_id'],
        'Paiement confirmé',
        'Votre paiement de ' . formatPrix($paiement['montant']) . ' a été confirmé. Réf : ' . $resultat['reference_transaction']
    );

    $_SESSION['success'] = 'Paiement effectué avec succès !';
    redirect(BASE_URL . '/user/detail_commande.php?id=' . $paiement['commande_id']);
}

$_SESSION['error'] = 'Le paiement n\'a pas abouti. Statut : ' . ($resultat['statut'] ?? 'inconnu');
redirect(BASE_URL . '/user/detail_commande.php?id=' . $paiement['commande_id']);
