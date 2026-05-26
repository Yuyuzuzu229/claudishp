<?php
// Callback appelé par FedaPay après le paiement via FedaCheckout
// FedaPay redirige vers : callback_url?token=CHECKOUT_TOKEN&reference=...
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/FedaPay.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Commande.php';

// Récupère le token de checkout et la référence depuis l'URL
$checkoutToken = $_GET['token'] ?? '';
$reference = $_GET['reference'] ?? '';

// Si aucun token n'est fourni, redirige vers l'accueil
if (empty($checkoutToken)) {
    $_SESSION['error'] = 'Aucun token de paiement reçu.';
    redirect(BASE_URL . '/index.php');
}

$pdo = getPdo();

// Cherche le paiement par le token FedaCheckout
$stmt = $pdo->prepare("SELECT p.*, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.fedacheckout_token = ?");
$stmt->execute([$checkoutToken]);
$paiement = $stmt->fetch();

// Si aucun paiement trouvé par token, cherche par référence
if (!$paiement) {
    $stmt = $pdo->prepare("SELECT p.*, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.reference_transaction = ?");
    $stmt->execute([$reference]);
    $paiement = $stmt->fetch();
}

// Si aucun paiement trouvé, redirige avec une erreur
if (!$paiement) {
    $_SESSION['error'] = 'Paiement introuvable.';
    redirect(BASE_URL . '/index.php');
}

// Instancie FedaPay et vérifie le statut du checkout
$fedapay = new FedaPay();
$resultat = $fedapay->verifierCheckout($checkoutToken);

// Si le paiement est confirmé
if ($resultat['success']) {
    // Met à jour le statut du paiement à "Confirmé"
    $stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = ?, date_paiement = NOW() WHERE id = ?");
    $stmt->execute([$resultat['reference_transaction'], $paiement['id']]);

    // Met à jour le statut de la commande à "Confirmée"
    $stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ?");
    $stmt->execute([$paiement['commande_id']]);

    // Crée une notification de confirmation pour l'utilisateur
    $notif = new Notification();
    $notif->creer(
        $paiement['utilisateur_id'],
        'Paiement confirmé',
        'Votre paiement de ' . formatPrix($paiement['montant']) . ' a été confirmé. Réf : ' . $resultat['reference_transaction']
    );

    // Message de succès et redirection vers le détail de la commande
    $_SESSION['success'] = 'Paiement effectué avec succès !';
    redirect(BASE_URL . '/user/detail_commande.php?id=' . $paiement['commande_id']);
}

// Si le paiement a échoué, affiche une erreur avec le statut
$_SESSION['error'] = 'Le paiement n\'a pas abouti. Statut : ' . ($resultat['statut'] ?? 'inconnu');
redirect(BASE_URL . '/user/detail_commande.php?id=' . $paiement['commande_id']);
