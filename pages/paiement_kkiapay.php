<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Kkiapay.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/pages/connexion.php');
}

$pdo = getPdo();
$commandeId = intval($_GET['commande_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, c.montant_total, c.statut, u.prenom, u.nom
    FROM paiement p
    JOIN commande c ON p.commande_id = c.id
    JOIN utilisateur u ON c.utilisateur_id = u.id
    WHERE p.commande_id = ? AND c.utilisateur_id = ?
    ORDER BY p.id DESC LIMIT 1
");
$stmt->execute([$commandeId, $_SESSION['user_id']]);
$paiement = $stmt->fetch();

if (!$paiement) {
    $_SESSION['error'] = 'Commande introuvable.';
    redirect(BASE_URL . '/index.php');
}

$kkiapay = new Kkiapay();
$publicKey = $kkiapay->estConfigure() ? KKIAPAY_PUBLIC_KEY : '';
$useSimulation = !$kkiapay->estConfigure();
$_SESSION['kkiapay_commande_id'] = $commandeId;

$pageTitle = 'Paiement sécurisé';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<style>
  .kkiapay-container { max-width: 480px; margin: 0 auto; padding-top: 32px; padding-bottom: 48px; }
  .panier-recap { position: static !important; }
</style>

<div class="container">
    <div class="kkiapay-container">

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="panier-recap" style="margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;">Récapitulatif</h3>
            <div class="recap-row">
                <span class="text-muted">Commande</span>
                <strong>#<?= $paiement['commande_id'] ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Client</span>
                <strong><?= securiser($paiement['prenom'] . ' ' . $paiement['nom']) ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Montant</span>
                <strong class="recap-total" style="font-size:24px;color:#111;"><?= formatPrix($paiement['montant_total']) ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Paiement</span>
                <strong><?= renderModePaiement($paiement['mode'] ?? '') ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Statut</span>
                <span class="badge badge-warning">En attente</span>
            </div>
        </div>

        <div class="panier-recap" style="margin-bottom:16px;text-align:center;">
            <h3 style="font-size:17px;font-weight:700;margin-bottom:8px;">Paiement sécurisé</h3>
            <p class="text-muted" style="font-size:13px;margin-bottom:20px;">
                Cliquez sur le bouton ci-dessous pour payer via Mobile Money.
            </p>

            <?php if ($useSimulation): ?>
                <form method="POST" action="<?= BASE_URL ?>/actions/confirmer_paiement_simple.php">
                    <input type="hidden" name="commande_id" value="<?= $paiement['commande_id'] ?>">
                    <input type="hidden" name="paiement_id" value="<?= $paiement['id'] ?>">
                    <button type="submit" class="btn btn-dark btn-block btn-lg" style="padding:14px;font-size:16px;">
                        <i class="fas fa-lock"></i> Payer <?= formatPrix($paiement['montant_total']) ?> (simulation)
                    </button>
                </form>
                <p class="text-xs text-muted" style="margin-top:10px;">
                    Mode simulation — les clés API Kkiapay ne sont pas configurées.
                </p>
            <?php else: ?>
                <script src="https://cdn.kkiapay.me/k.js"></script>
                <kkiapay-widget
                    amount="<?= $paiement['montant_total'] ?>"
                    key="<?= $publicKey ?>"
                    sandbox="true"
                    position="center"
                    paymentmethod="momo"
                    countries="BJ"
                    data="<?= $paiement['commande_id'] ?>"
                    callback="<?= BASE_URL ?>/actions/callback_kkiapay.php">
                </kkiapay-widget>
            <?php endif; ?>
        </div>

        <div style="border:1px solid var(--gray-200);padding:16px;background:var(--gray-50);">
            <h3 style="font-size:13px;font-weight:700;margin-bottom:10px;">Moyens de paiement acceptés</h3>
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <span style="padding:6px 14px;background:#ffcc00;color:#000;border-radius:6px;font-size:12px;font-weight:600;">
                    <i class="fas fa-mobile-alt"></i> MTN MoMo
                </span>
                <span style="padding:6px 14px;background:#00aaff;color:#fff;border-radius:6px;font-size:12px;font-weight:600;">
                    <i class="fas fa-mobile-alt"></i> Moov Money
                </span>
                <span style="padding:6px 14px;background:#6366f1;color:#fff;border-radius:6px;font-size:12px;font-weight:600;">
                    <i class="fas fa-mobile-alt"></i> Wave
                </span>
                <span style="font-size:11px;color:var(--gray-400);margin-left:auto;">
                    <i class="fas fa-lock"></i> Transaction sécurisée
                </span>
            </div>
        </div>

    </div>
</div>

<?php if (!$useSimulation): ?>
<script>
(function() {
    function handleSuccess(data) {
        var txId = data.transaction_id || data.transactionId || '';
        window.location.href = '<?= BASE_URL ?>/verifier.php?id=' + txId + '&kkiapay=1';
    }
    function handleFailed(data) {
        alert('Paiement échoué. Veuillez réessayer.');
    }
    if (typeof addKkiapayListener === 'function') {
        addKkiapayListener('success', handleSuccess);
        addKkiapayListener('failed', handleFailed);
    }
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
