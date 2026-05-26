<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$commandeId = intval($_POST['commande_id'] ?? 0);
$paiementId = intval($_POST['paiement_id'] ?? 0);

if (!$commandeId || !$paiementId) {
    $_SESSION['error'] = 'Paramètres invalides.';
    redirect(BASE_URL . '/index.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT p.*, c.montant_total, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.id = ? AND p.commande_id = ? AND c.utilisateur_id = ?");
$stmt->execute([$paiementId, $commandeId, $_SESSION['user_id']]);
$paiement = $stmt->fetch();

if (!$paiement) {
    $_SESSION['error'] = 'Paiement introuvable.';
    redirect(BASE_URL . '/index.php');
}

$reference = 'SIM-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));

$stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = ?, date_paiement = NOW() WHERE id = ?");
$stmt->execute([$reference, $paiementId]);

$stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ?");
$stmt->execute([$commandeId]);

$notif = new Notification();
$notif->creer(
    $_SESSION['user_id'],
    'Paiement confirmé',
    'Votre paiement de ' . formatPrix($paiement['montant_total']) . ' a été confirmé. Réf : ' . $reference
);

$_SESSION['success'] = 'Paiement effectué avec succès !';
redirect(BASE_URL . '/user/detail_commande.php?id=' . $commandeId);
