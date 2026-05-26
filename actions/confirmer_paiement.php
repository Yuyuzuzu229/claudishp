<?php
// Inclusion des fichiers de configuration et des classes nécessaires
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/FedaPay.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../config/database.php';

// Vérifie si l'utilisateur est connecté et si la requête est POST
if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère et sécurise les données du formulaire
$token = securiser($_POST['token'] ?? '');
$commandeId = intval($_POST['commande_id'] ?? 0);
$paiementId = intval($_POST['paiement_id'] ?? 0);
$avecOtp = isset($_POST['avec_otp']) && $_POST['avec_otp'] === '1';
$codeOtp = securiser($_POST['code_otp'] ?? '');

// Vérifie que tous les paramètres requis sont présents
if (empty($token) || !$commandeId || !$paiementId) {
    $_SESSION['error'] = 'Paramètres invalides.';
    redirect(BASE_URL . '/index.php');
}

$fedapay = new FedaPay();

// Si les clés API réelles sont configurées -> utiliser FedaCheckout
if ($fedapay->estModeReel()) {
    $pdo = getPdo();
    // Récupère les informations du paiement et de la commande associée
    $stmt = $pdo->prepare("SELECT p.*, c.montant_total, c.utilisateur_id FROM paiement p JOIN commande c ON p.commande_id = c.id WHERE p.id = ? AND p.token = ?");
    $stmt->execute([$paiementId, $token]);
    $paiement = $stmt->fetch();

    // Si le paiement n'est pas trouvé, redirige avec une erreur
    if (!$paiement) {
        $_SESSION['error'] = 'Paiement introuvable.';
        redirect(BASE_URL . '/index.php');
    }

    $callbackUrl = PUBLIC_URL . '/verifier.php';
    $reference = $fedapay->genererReference();
    $description = 'Commande #' . str_pad($commandeId, 6, '0', STR_PAD_LEFT);

    // Crée une session FedaCheckout avec Mobile Money
    $checkout = $fedapay->creerCheckout(
        $paiement['montant_total'],
        $reference,
        $description,
        $paiement['telephone_paiement'],
        $callbackUrl,
        $paiement['mode']
    );

    // Si le checkout est créé avec succès, sauvegarde le token et redirige
    if ($checkout['success'] && !empty($checkout['url'])) {
        // Sauvegarde le token FedaCheckout en base
        $stmt = $pdo->prepare("UPDATE paiement SET fedacheckout_token = ?, reference_transaction = ? WHERE id = ?");
        $stmt->execute([$checkout['token'], $reference, $paiementId]);

        // Redirige vers FedaCheckout
        redirect($checkout['url']);
    }

    // Si FedaCheckout a échoué, continue en mode simulation
}

// --- MODE SIMULATION Mobile Money (fallback) ----------------------
// Simule le paiement Mobile Money avec vérification OTP si demandé
$resultat = $fedapay->simulerPaiement($token, $avecOtp ? $codeOtp : null);

// Si la simulation échoue (OTP invalide), redirige avec une erreur
if (!$resultat['success']) {
    $_SESSION['error'] = $resultat['message'];
    redirect(BASE_URL . '/pages/paiement.php?token=' . $token);
}

$pdo = getPdo();

// Met à jour le statut du paiement à "Confirmé"
$stmt = $pdo->prepare("UPDATE paiement SET statut = 'Confirmé', reference_transaction = ?, date_paiement = NOW() WHERE id = ?");
$stmt->execute([$resultat['reference_transaction'], $paiementId]);
// Met à jour le statut de la commande à "Confirmée"
$stmt = $pdo->prepare("UPDATE commande SET statut = 'Confirmée' WHERE id = ?");
$stmt->execute([$commandeId]);

// Récupère le montant et le mode de paiement pour la notification
$montant = $_SESSION['fedapay_montant'] ?? 0;
$modePaiement = $_SESSION['fedapay_mode'] ?? 'Mobile Money';
$notifObj = new Notification();
$notifObj->creer($_SESSION['user_id'], 'Paiement ' . $modePaiement . ' confirmé',
    'Votre paiement de ' . formatPrix($montant) . ' pour la commande #' . $commandeId . ' a été confirmé via ' . $modePaiement . '. Réf : ' . $resultat['reference_transaction']);

// Annule le token de paiement temporaire
$fedapay->annulerPaiement($token);

// Message de succès et redirection vers le détail de la commande
$_SESSION['success'] = 'Paiement ' . $modePaiement . ' effectué avec succès ! Réf : ' . $resultat['reference_transaction'];
redirect(BASE_URL . '/user/detail_commande.php?id=' . $commandeId);
