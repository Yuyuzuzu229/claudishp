<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Avis.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Nouvel avis';
$avisObj = new Avis();
$produits = $avisObj->getProduitsAchetables($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
$activePage = 'avis';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label"><a href="<?= BASE_URL ?>/user/mes_avis.php" style="color:var(--gray-400);"><i class="fas fa-arrow-left"></i> Mes avis</a></div>
        <h1 class="dash-page-title">Donner un avis</h1>
        <p class="dash-page-sub">Sélectionnez un produit que vous avez acheté pour laisser votre évaluation.</p>
    </div>

    <div class="table-card">
        <?php if (empty($produits)): ?>
        <div style="padding:48px;text-align:center;">
            <i class="fas fa-check-circle" style="font-size:40px;color:var(--success, #10B981);margin-bottom:16px;display:block;"></i>
            <p class="text-muted" style="margin-bottom:4px;">Vous avez déjà évalué tous vos produits achetés.</p>
            <p class="text-xs text-muted">Ou vous n'avez pas encore de commande confirmée.</p>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark" style="margin-top:16px;">Découvrir nos produits</a>
        </div>
        <?php else: ?>
        <div class="table-card-header"><span class="table-card-title">Produits à évaluer (<?= count($produits) ?>)</span></div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($produits as $p): ?>
            <div style="border:1px solid var(--gray-200);border-radius:6px;padding:12px;">
                <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_avis.php" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                    <div style="width:48px;height:48px;border-radius:4px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-tshirt" style="color:var(--gray-400);font-size:18px;"></i>
                    </div>
                    <div style="flex:1;min-width:140px;">
                        <div class="text-sm font-semibold"><?= securiser($p['nom']) ?></div>
                        <div class="text-xs text-muted"><?= renderPrix($p['prix'], $p['solde_prix']) ?></div>
                    </div>
                    <div class="star-rating" data-produit="<?= $p['id'] ?>" style="display:flex;gap:4px;font-size:22px;color:var(--gray-300);cursor:pointer;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span data-value="<?= $i ?>" class="star">☆</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="note" class="note-input" value="5">
                    <input type="text" name="commentaire" placeholder="Votre commentaire..." class="form-control" style="flex:1;min-width:180px;padding:8px 10px;font-size:13px;border:1px solid var(--gray-200);border-radius:4px;">
                    <button type="submit" class="btn btn-dark" style="white-space:nowrap;padding:8px 16px;font-size:13px;">Publier</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.star-rating').forEach(function(container) {
        var input = container.parentElement.querySelector('.note-input');
        container.querySelectorAll('.star').forEach(function(star) {
            star.addEventListener('click', function() {
                var val = parseInt(this.dataset.value);
                if (input) input.value = val;
                container.querySelectorAll('.star').forEach(function(s, idx) {
                    s.textContent = idx < val ? '★' : '☆';
                    s.style.color = idx < val ? 'var(--warning, #F59E0B)' : 'var(--gray-300)';
                });
            });
            star.addEventListener('mouseenter', function() {
                var val = parseInt(this.dataset.value);
                container.querySelectorAll('.star').forEach(function(s, idx) {
                    s.textContent = idx < val ? '★' : '☆';
                    s.style.color = idx < val ? 'var(--warning, #F59E0B)' : 'var(--gray-300)';
                });
            });
        });
        container.addEventListener('mouseleave', function() {
            var val = parseInt(input ? input.value : 5);
            container.querySelectorAll('.star').forEach(function(s, idx) {
                s.textContent = idx < val ? '★' : '☆';
                s.style.color = idx < val ? 'var(--warning, #F59E0B)' : 'var(--gray-300)';
            });
        });
    });
});
</script>
</body>
</html>
