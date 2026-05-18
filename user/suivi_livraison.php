<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../classes/Livreur.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Suivi livraison';
$commandeObj = new Commande();
$livraisonObj = new Livraison();

$allCommandes = $commandeObj->getByUtilisateur($_SESSION['user_id']);
$suivis = [];
foreach ($allCommandes as $cmd) {
    $liv = $livraisonObj->getByCommande($cmd['id']);
    if ($liv) {
        $livreurNom = '';
        if ($liv['livreur_id']) {
            $livreurObj = new Livreur();
            $lv = $livreurObj->getById($liv['livreur_id']);
            $livreurNom = $lv ? $lv['nom'] : '';
        }
        $suivis[] = [
            'commande' => $cmd,
            'livraison' => $liv,
            'livreur_nom' => $livreurNom,
        ];
    }
}

require_once __DIR__ . '/../includes/header.php';
$activePage = 'suivi';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div id="suivi-content" class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Livraison</div>
        <h1 class="dash-page-title">Suivi de livraison</h1>
        <p class="dash-page-sub">Suivez l'état de vos colis en temps réel</p>
    </div>

    <?php if (empty($suivis)): ?>
    <div class="table-card" style="padding:64px 32px;text-align:center;">
        <i class="fas fa-box-open" style="font-size:48px;color:var(--gray-200);margin-bottom:16px;display:block;"></i>
        <p class="text-muted">Aucune livraison en cours.</p>
        <p class="text-xs text-muted" style="margin-top:4px;">Passez une commande pour voir le suivi ici.</p>
        <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark btn-sm" style="margin-top:16px;"><i class="fas fa-shopping-bag"></i> Commander</a>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:20px;">
        <?php foreach ($suivis as $s):
            $cmd = $s['commande'];
            $liv = $s['livraison'];
            $refCmd = 'CMD-' . str_pad($cmd['id'], 6, '0', STR_PAD_LEFT);
            $refLiv = 'LIV-' . str_pad($liv['id'], 4, '0', STR_PAD_LEFT);
        ?>
        <div class="table-card" style="overflow:visible;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--gray-100);">
                <div class="flex justify-between items-center">
                    <div>
                        <strong style="font-size:15px;">#<?= $refCmd ?></strong>
                        <span class="text-xs text-muted" style="margin-left:8px;"><?= $refLiv ?></span>
                    </div>
                    <?= getStatutBadge($liv['statut']) ?>
                </div>
                <div class="text-xs text-muted" style="margin-top:4px;"><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></div>
            </div>

            <div style="padding:24px 40px;">
                <?php
                $etapes = [
                    'En attente' => ['label' => 'Commande validée', 'icon' => 'fa-check-circle', 'desc' => 'Votre commande a été enregistrée'],
                    'Prêt à expédier' => ['label' => 'Préparation', 'icon' => 'fa-box', 'desc' => 'Votre colis est en cours de préparation'],
                    'En route' => ['label' => 'En route', 'icon' => 'fa-truck', 'desc' => 'Le livreur a démarré la livraison'],
                    'Livrée' => ['label' => 'Livrée', 'icon' => 'fa-gift', 'desc' => 'Colis livré avec succès !'],
                ];
                $statutActuel = $liv['statut'];
                if ($statutActuel === 'En cours') $statutActuel = 'En route';
                $statutIndex = array_keys($etapes);

                if ($statutActuel === 'Annulée' || $statutActuel === 'Échouée') {
                    echo '<div style="text-align:center;padding:20px 0;"><i class="fas fa-times-circle" style="font-size:36px;color:var(--danger);margin-bottom:8px;display:block;"></i><p style="font-weight:600;">Livraison ' . strtolower($statutActuel) . '</p></div>';
                } else {
                ?>
                <div style="position:relative;display:flex;justify-content:space-between;align-items:flex-start;">
                    <?php foreach ($etapes as $key => $e):
                        $done = array_search($key, $statutIndex) <= array_search($statutActuel, $statutIndex);
                        $current = $key === $statutActuel;
                    ?>
                    <div style="display:flex;flex-direction:column;align-items:center;flex:1;position:relative;text-align:center;">
                        <?php if ($key !== array_key_first($etapes)): ?>
                        <div style="position:absolute;top:20px;right:50%;width:100%;height:3px;background:<?= $done ? 'var(--success)' : 'var(--gray-200)' ?>;z-index:0;"></div>
                        <?php endif; ?>
                        <div style="width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $current ? 'var(--success)' : ($done ? '#f0fdf4' : 'var(--gray-100)') ?>;border:2px solid <?= $current ? 'var(--success)' : ($done ? 'var(--success)' : 'var(--gray-200)') ?>;color:<?= $current ? 'white' : ($done ? 'var(--success)' : 'var(--gray-400)') ?>;font-size:16px;z-index:1;transition:all 0.3s;">
                            <i class="fas <?= $e['icon'] ?>"></i>
                        </div>
                        <div style="margin-top:10px;max-width:100px;">
                            <div style="font-size:12px;font-weight:<?= $current ? '700' : '600' ?>;color:<?= $current ? 'var(--success)' : ($done ? 'var(--dark)' : 'var(--gray-400)') ?>;"><?= $e['label'] ?></div>
                            <div style="font-size:10px;color:var(--gray-400);margin-top:2px;"><?= $e['desc'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php } ?>
            </div>

            <?php if ($liv['livreur_nom'] || $liv['livreur_id']): ?>
            <div style="padding:14px 20px;border-top:1px solid var(--gray-100);background:var(--gray-50);display:flex;align-items:center;gap:12px;justify-content:space-between;flex-wrap:wrap;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-motorcycle" style="color:var(--dark);font-size:16px;"></i>
                    <span class="text-sm"><strong>Livreur :</strong> <?= securiser($s['livreur_nom'] ?: 'Assigné') ?></span>
                </div>
                <a href="<?= BASE_URL ?>/user/detail_commande.php?id=<?= $cmd['id'] ?>" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-external-link-alt"></i> Détails commande
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop</span><span>v1.0.0</span></div>
</div>
</div>
<script>
(function(){
    var currentUrl = window.location.href;
    var pollId = null;
    function actualiser() {
        fetch(currentUrl)
            .then(function(r){ return r.text(); })
            .then(function(html){
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newContent = doc.getElementById('suivi-content');
                var oldContent = document.getElementById('suivi-content');
                if (newContent && oldContent) {
                    oldContent.style.transition = 'opacity 0.3s ease';
                    oldContent.style.opacity = '0';
                    setTimeout(function(){
                        oldContent.innerHTML = newContent.innerHTML;
                        oldContent.style.opacity = '1';
                    }, 300);
                }
                pollId = setTimeout(actualiser, 10000);
            })
            .catch(function(){ pollId = setTimeout(actualiser, 10000); });
    }
    pollId = setTimeout(actualiser, 10000);
})();
</script>
</body></html>
