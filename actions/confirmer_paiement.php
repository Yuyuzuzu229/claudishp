<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/FedaPay.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$token = securiser($_POST['token'] ?? '');
$commandeId = intval($_POST['commande_id'] ?? 0);
$paiementId = intval($_POST['paiement_id'] ?? 0);

if (empty($token) || !$commandeId || !$paiementId) {
    $_SESSION['error'] = 'Paramètres invalides.';
    redirect(BASE_URL . '/index.php');
}

$fedapay = new FedaPay();

// Si les clés API réelles sont configurées → utiliser FedaCheckout
if ($fedapay->estModeReel()) {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT p.*, c.montant_total, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.id = ? AND p.token = ?");
    $stmt->execute([$paiementId, $token]);
    $paiement = $stmt->fetch();

    if (!$paiement) {
        $_SESSION['error'] = 'Paiement introuvable.';
        redirect(BASE_URL . '/index.php');
    }

    $callbackUrl = PUBLIC_URL . '/actions/paiement_callback.php';
    $reference = $fedapay->genererReference();
    $description = 'Commande #' . str_pad($commandeId, 6, '0', STR_PAD_LEFT);

    $checkout = $fedapay->creerCheckout(
        $paiement['montant_total'],
        $reference,
        $description,
        $paiement['telephone_paiement'],
        $callbackUrl
    );

    if ($checkout['success'] && !empty($checkout['url'])) {
        // Sauvegarder le token FedaCheckout
        $stmt = $pdo->prepare("UPDATE paiement SET fedacheckout_token = ?, reference_transaction = ? WHERE id = ?");
        $stmt->execute([$checkout['token'], $reference, $paiementId]);

        // Rediriger vers FedaCheckout
        redirect($checkout['url']);
    }

    // Si FedaCheckout a échoué, continuer en simulation
}

// ─── MODE SIMULATION (fallback) ──────────────────────────────────
$resultat = $fedapay->simulerPaiement($token);

if (!$resultat['success']) {
    $_SESSION['error'] = $resultat['message'];
    redirect(BASE_URL . '/pages/paiement.php?token=' . $token);
}

$pdo = getPdo();

$stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = ?, date_paiement = NOW() WHERE id = ?");
$stmt->execute([$resultat['reference_transaction'], $paiementId]);

$stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ?");
$stmt->execute([$commandeId]);

$montant = $_SESSION['fedapay_montant'] ?? 0;
$notifObj = new Notification();
$notifObj->creer($_SESSION['user_id'], 'Paiement confirmé',
    'Votre paiement de ' . formatPrix($montant) . ' pour la commande #' . $commandeId . ' a été confirmé. Référence : ' . $resultat['reference_transaction']);

$fedapay->annulerPaiement($token);

$_SESSION['success'] = 'Paiement effectué avec succès ! Réf : ' . $resultat['reference_transaction'];
redirect(BASE_URL . '/user/detail_commande.php?id=' . $commandeId);
