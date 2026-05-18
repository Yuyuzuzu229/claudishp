<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Categorie.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/pages/connexion.php');
}

$token = $_GET['token'] ?? '';
if (empty($token)) {
    redirect(BASE_URL . '/index.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT p.*, c.montant_total, c.id as commande_id, u.prenom, u.nom FROM paiement p JOIN commande c ON p.commande_id = c.id JOIN utilisateur u ON c.utilisateur_id = u.id WHERE p.token = ? AND c.utilisateur_id = ?");
$stmt->execute([$token, $_SESSION['user_id']]);
$paiement = $stmt->fetch();

if (!$paiement) {
    $_SESSION['error'] = 'Transaction introuvable.';
    redirect(BASE_URL . '/index.php');
}

$pageTitle = 'Paiement';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<style>
  body { animation: none !important; }
  .panier-recap { position: static !important; }
</style>
<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="max-width:520px;margin:0 auto;">

        <!-- Header -->
        <div class="text-center" style="margin-bottom:28px;">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-lock" style="font-size:22px;color:#fff;"></i>
            </div>
            <h1 style="font-size:22px;font-weight:700;margin-bottom:4px;">Paiement sécurisé</h1>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Récapitulatif -->
        <div class="panier-recap" style="margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;">Récapitulatif</h3>
            <div class="recap-row">
                <span class="text-muted">Commande</span>
                <strong>#<?= $paiement['commande_id'] ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Client</span>
                <strong><?= securiser($paiement['prenom']) ?> <?= securiser($paiement['nom']) ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Montant</span>
                <strong class="recap-total" style="color:var(--color-primary);"><?= formatPrix($paiement['montant_total']) ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Mode</span>
                <strong><?= securiser($paiement['mode']) ?></strong>
            </div>
            <?php if (!empty($paiement['telephone_paiement'])): ?>
            <div class="recap-row">
                <span class="text-muted">Téléphone</span>
                <strong><?= securiser($paiement['telephone_paiement']) ?></strong>
            </div>
            <?php endif; ?>
            <div class="recap-row">
                <span class="text-muted">Statut</span>
                <span class="badge badge-warning">En attente</span>
            </div>
        </div>

        <!-- Paiement -->
        <div class="panier-recap" style="margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;">Confirmer le paiement</h3>

            <div class="secure-badge" style="margin-bottom:16px;">
                <i class="fas fa-shield-alt" style="color:var(--success);font-size:20px;"></i>
                <div>
                    <strong style="font-size:13px;">Paiement sécurisé</strong>
                    <p class="text-muted" style="font-size:11px;margin:2px 0 0;">Vos informations sont cryptées.</p>
                </div>
            </div>

            <div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;">
                <span style="padding:5px 10px;border:1px solid var(--gray-200);font-size:11px;"><i class="fas fa-mobile-alt"></i> MTN</span>
                <span style="padding:5px 10px;border:1px solid var(--gray-200);font-size:11px;"><i class="fas fa-mobile-alt"></i> Moov</span>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/actions/confirmer_paiement.php">
                <input type="hidden" name="token" value="<?= securiser($token) ?>">
                <input type="hidden" name="commande_id" value="<?= $paiement['commande_id'] ?>">
                <input type="hidden" name="paiement_id" value="<?= $paiement['id'] ?>">

                <button type="submit" class="btn btn-dark btn-block btn-lg" style="padding:14px;font-size:16px;">
                    <i class="fas fa-lock"></i> Confirmer le paiement — <?= formatPrix($paiement['montant_total']) ?>
                </button>
            </form>

            <div class="text-center" style="margin-top:12px;">
                <a href="<?= BASE_URL ?>/actions/annuler_paiement.php?token=<?= securiser($token) ?>&commande_id=<?= $paiement['commande_id'] ?>" class="btn btn-outline-dark btn-sm" style="color:var(--gray-500);border-color:var(--gray-300);">
                    <i class="fas fa-times"></i> Annuler et revenir au panier
                </a>
            </div>
        </div>

        <!-- Sécurité -->
        <div style="border:1px solid var(--gray-200);padding:16px;background:var(--gray-50);">
            <h3 style="font-size:13px;font-weight:700;margin-bottom:10px;">Paiement 100% sécurisé</h3>
            <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--gray-500);">
                <span><i class="fas fa-check-circle" style="color:var(--success);"></i> Cryptage SSL</span>
                <span><i class="fas fa-check-circle" style="color:var(--success);"></i> Données protégées</span>
                <span><i class="fas fa-check-circle" style="color:var(--success);"></i> Sans risque</span>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
