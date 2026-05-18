<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Notification.php';

$pageTitle = 'Mon panier';
$panierObj = new Panier();

$isGuest = !isLoggedIn();

if ($isGuest) {
    $lignes = $panierObj->guestGetLignes();
    $total = $panierObj->guestCalculerTotal();
    $nbArticles = $panierObj->guestGetNombreArticles();
} else {
    $panierId = $panierObj->getPanierActif($_SESSION['user_id']);
    $lignes = $panierObj->getLignes($panierId);
    $total = $panierObj->calculerTotal($panierId);
    $nbArticles = $panierObj->getNombreArticles($panierId);
}

require_once __DIR__ . '/../includes/header.php';
$activePage = 'panier';
?>

<?php if (!$isGuest): /* Dashboard layout for logged-in users */ ?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Mon compte</div>
        <h1 class="dash-page-title">Mon panier</h1>
        <p class="dash-page-sub">Vérifiez vos articles et passez votre commande.</p>
    </div>
<?php else: /* Public layout for guests */ ?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">/</span>
        <span>Mon panier</span>
    </div>
    <h1 style="font-size:28px;font-weight:800;margin-bottom:8px;">Mon panier</h1>
    <p class="text-muted" style="margin-bottom:28px;">Révisez vos articles avant de commander.</p>
<?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <?php if (empty($lignes)): ?>
    <div class="empty-state" style="border:1px solid var(--gray-200);">
        <i class="fas fa-shopping-cart"></i>
        <p>Votre panier est vide.</p>
        <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark">Découvrir nos produits</a>
    </div>
    <?php else: ?>
    <div class="checkout-layout">
        <div>
            <div class="table-card">
                <div class="table-card-header">
                    <span class="table-card-title">Articles dans votre panier (<?= $nbArticles ?>)</span>
                </div>
                <table>
                    <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($lignes as $i => $ligne):
                        $ligneId = $isGuest ? $i : $ligne['id'];
                    ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="panier-table-img"><i class="fas fa-tshirt"></i></div>
                                <div>
                                    <div class="text-sm font-semibold"><?= securiser($ligne['nom']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php if (!empty($ligne['solde_prix']) && $ligne['solde_prix'] > 0): ?><span class="prix-solde"><?= formatPrix($ligne['prix_unitaire']) ?></span> <span class="prix-barre"><?= formatPrix($ligne['prix_actuel']) ?></span><?php else: ?><?= formatPrix($ligne['prix_unitaire']) ?><?php endif; ?></td>
                        <td>
                            <form method="POST" action="<?= BASE_URL ?>/actions/modifier_panier.php" class="qty-control">
                                <input type="hidden" name="ligne_id" value="<?= $ligneId ?>">
                                <button type="button" class="qty-btn" onclick="var inp=this.nextElementSibling;inp.value=Math.max(0,parseInt(inp.value)-1);this.closest('form').submit();"><i class="fas fa-minus"></i></button>
                                <input type="number" name="quantite" class="qty-input" value="<?= $ligne['quantite'] ?>" min="0" max="<?= $ligne['stock'] ?>">
                                <button type="button" class="qty-btn" onclick="var inp=this.previousElementSibling;inp.value=Math.min(<?= $ligne['stock'] ?>,parseInt(inp.value)+1);this.closest('form').submit();"><i class="fas fa-plus"></i></button>
                            </form>
                        </td>
                        <td><strong><?= formatPrix($ligne['prix_unitaire'] * $ligne['quantite']) ?></strong></td>
                        <td><a href="<?= BASE_URL ?>/actions/supprimer_panier.php?ligne_id=<?= $ligneId ?>" style="color:var(--gray-400);font-size:14px;" onclick="return confirm('Supprimer cet article ?')"><i class="fas fa-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="flex justify-between" style="padding:14px 16px;border-top:1px solid var(--gray-100);">
                    <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
                    <a href="<?= BASE_URL ?>/actions/vider_panier.php<?= $isGuest ? '' : '?panier_id=' . $panierId ?>" style="font-size:13px;color:var(--gray-500);display:flex;align-items:center;gap:6px;" onclick="return confirm('Vider le panier ?')"><i class="fas fa-trash"></i> Vider le panier</a>
                </div>
            </div>
        </div>
        <div class="panier-recap">
            <h3>Récapitulatif de panier</h3>
            <div class="recap-row"><span class="text-muted">Sous-total (<?= $nbArticles ?> articles)</span><strong><?= formatPrix($total) ?></strong></div>
            <hr>
            <div class="recap-row"><strong style="font-size:16px;">Total TTC</strong><strong class="recap-total"><?= formatPrix($total) ?></strong></div>
            <div class="secure-badge">
                <i class="fas fa-shield-alt"></i>
                <div><div class="font-semibold" style="font-size:13px;">Paiement 100% sécurisé</div><div class="text-xs text-muted" style="margin-top:2px;">Vos transactions sont protégées par un chiffrement SSL.</div></div>
            </div>
            <a href="<?= BASE_URL ?>/pages/checkout.php" class="btn btn-dark btn-block btn-lg">Valider panier</a>
            <p class="text-xs text-muted text-center" style="margin-top:10px;"><i class="fas fa-lock" style="margin-right:4px;"></i>Paiement sécurisé</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="why-buy" style="margin-top:28px;">
        <div class="why-buy-item"><i class="fas fa-truck"></i><h4>Livraison rapide</h4><p>Partout au Bénin</p></div>
        <div class="why-buy-item"><i class="fas fa-undo"></i><h4>Retour facile</h4><p>Sous 7 jours</p></div>
        <div class="why-buy-item"><i class="fas fa-shield-alt"></i><h4>Paiement sécurisé</h4><p>100% sécurisé</p></div>
        <div class="why-buy-item"><i class="fas fa-headset"></i><h4>Support client</h4><p>7j/7 à votre écoute</p></div>
    </div>

<?php if (!$isGuest): /* Close dashboard layout */ ?>
</div>
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
<?php else: /* Close public layout */ ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>
</body></html>
