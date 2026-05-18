<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Categorie.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Utilisateur.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Avis.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Dashboard Admin';
$produitObj = new Produit();
$categorieObj = new Categorie();
$commandeObj = new Commande();
$utilisateurObj = new Utilisateur();
$paiementObj = new Paiement();
$avisObj = new Avis();

$stats = [
    'produits' => $produitObj->getNombre(),
    'categories' => $categorieObj->getNombre(),
    'commandes' => $commandeObj->getNombre(),
    'total_ventes' => $commandeObj->getTotalVentes(),
    'utilisateurs' => $utilisateurObj->getNombre(),
    'total_paiements' => $paiementObj->getTotalPaiements(),
    'avis' => $avisObj->getNombre(),
];

$dernieresCommandes = $commandeObj->getDernieresCommandes(6);

$pdo = getPdo();
$stockFaible = $pdo->query("SELECT id, nom, stock FROM produit WHERE stock <= 5 AND stock > 0 ORDER BY stock ASC LIMIT 3")->fetchAll();
$statsStatuts = $pdo->query("SELECT statut, COUNT(*) as nb FROM commande GROUP BY statut")->fetchAll();
$totalCmd = $stats['commandes'] ?: 1;
$statutPourcent = [];
foreach ($statsStatuts as $s) {
    $statutPourcent[$s['statut']] = round($s['nb'] / $totalCmd * 100);
}
$ventes7j = $pdo->query("SELECT DATE(date_commande) as jour, SUM(montant_total) as total FROM commande WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND statut != 'Annulée' GROUP BY DATE(date_commande) ORDER BY jour")->fetchAll();
$ventesMax = 1;
foreach ($ventes7j as $v) if ($v['total'] > $ventesMax) $ventesMax = $v['total'];
$derniersAvis = $pdo->query("SELECT a.*, u.nom, u.prenom, p.nom as produit_nom FROM avis a JOIN utilisateur u ON a.utilisateur_id = u.id JOIN produit p ON a.produit_id = p.id WHERE a.statut = 'Publié' ORDER BY a.date_creation DESC LIMIT 3")->fetchAll();
$dernieresNotifs = $pdo->query("SELECT n.*, u.nom, u.prenom FROM notification n LEFT JOIN utilisateur u ON n.utilisateur_id = u.id ORDER BY n.date_envoi DESC LIMIT 3")->fetchAll();
$derniersPaiements = $pdo->query("SELECT p.*, c.nom_complet FROM paiement p JOIN commande c ON p.commande_id = c.id ORDER BY p.date_paiement DESC LIMIT 3")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
$adminPage = 'dashboard';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Tableau de bord</div>
        <h1 class="dash-page-title">Vue d'ensemble</h1>
    </div>

    <!-- KPI -->
    <div class="kpi-grid kpi-grid-4">
        <div class="kpi-card"><div><div class="kpi-label">Commandes totales</div><div class="kpi-value"><?= number_format($stats['commandes']) ?></div><div class="kpi-trend">▲ +12% ce mois</div></div><i class="fas fa-receipt kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Chiffre d'affaires</div><div class="kpi-value" style="font-size:18px;"><?= formatPrix($stats['total_ventes']) ?></div><div class="kpi-trend">▲ +8% vs mois dernier</div></div><i class="fas fa-dollar-sign kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Clients actifs</div><div class="kpi-value"><?= number_format($stats['utilisateurs']) ?></div><div class="kpi-trend">▲ +5 nouveaux aujourd'hui</div></div><i class="fas fa-users kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Taux de livraison</div><div class="kpi-value">94.2%</div><div class="kpi-trend down">▼ -0.8% vs semaine passée</div></div><i class="fas fa-truck kpi-icon"></i></div>
    </div>

    <div class="dash-two-col" style="margin-bottom:18px;">
        <!-- GRAPHE (placeholder) -->
        <div class="table-card">
            <div class="table-card-header">
                <div>
                    <span class="table-card-title">Ventes des 7 derniers jours</span>
                    <p class="text-xs text-muted" style="margin-top:2px;">Chiffre d'affaires journalier (FCFA)</p>
                </div>
            </div>
            <div style="padding:20px;">
                <div style="height:200px;background:var(--color-bg-main);border:1px solid var(--color-border);display:flex;align-items:flex-end;gap:6px;padding:16px;border-radius:var(--radius-sm);">
                    <?php
                    $days = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
                    for ($i = 0; $i < 7; $i++):
                        $jourData = $ventes7j[$i] ?? null;
                        $h = $jourData ? round($jourData['total'] / $ventesMax * 100) : 0;
                        $montant = $jourData ? formatPrix($jourData['total']) : '—';
                    ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;">
                        <span style="font-size:8px;color:var(--color-text-muted);font-weight:600;"><?= $montant ?></span>
                        <div style="width:100%;background:var(--color-chart-bar);height:<?= max($h,2) ?>%;border-radius:var(--radius-sm) var(--radius-sm) 0 0;"></div>
                        <span style="font-size:10px;color:var(--color-text-muted);"><?= $days[$i] ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="flex gap-4 mt-2" style="margin-top:12px;">
                    <div class="flex gap-2 items-center"><div style="width:12px;height:3px;background:var(--color-chart-bar);"></div><span class="text-xs text-muted">Ventes journalières</span></div>
                    <div class="flex gap-2 items-center"><div style="width:12px;height:2px;background:var(--color-text-muted);border-top:2px dashed;"></div><span class="text-xs text-muted">Tendance</span></div>
                </div>
            </div>
        </div>

        <!-- COMMANDES RECENTES -->
        <div class="table-card">
            <div class="table-card-header">
                <span class="table-card-title">Commandes récentes</span>
                <a href="<?= BASE_URL ?>/admin/commandes.php" class="section-link" style="font-size:12px;">Voir tout <i class="fas fa-arrow-right"></i></a>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Client</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
                <tbody>
                <?php if (empty($dernieresCommandes)): ?>
                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-400);">Aucune commande.</td></tr>
                <?php else: foreach ($dernieresCommandes as $cmd): ?>
                <tr>
                    <td><strong>#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                    <td class="text-sm"><?= securiser(($cmd['prenom'] ?? '') . ' ' . ($cmd['nom'] ?? '')) ?></td>
                    <td class="text-sm"><?= formatPrix($cmd['montant_total']) ?></td>
                    <td><?= getStatutBadge($cmd['statut']) ?></td>
                    <td class="text-xs text-muted"><?= date('d/m/y', strtotime($cmd['date_commande'])) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div style="padding:10px 16px;border-top:1px solid var(--gray-100);font-size:12px;color:var(--gray-400);display:flex;justify-content:space-between;align-items:center;">
                <span>Affichage 1-<?= count($dernieresCommandes) ?> sur <?= $stats['commandes'] ?> commandes</span>
                <div style="display:flex;gap:4px;">
                    <span class="page-btn" style="cursor:default;">&lt;</span>
                    <span class="page-btn active">1</span>
                    <span class="page-btn">&gt;</span>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-three-col">
        <!-- STATUTS COMMANDES -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Statuts commandes</span></div>
            <div style="padding:20px;display:flex;align-items:center;gap:20px;">
                <div style="width:100px;height:100px;border-radius:50%;border:10px solid var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <div style="text-align:center;"><div style="font-size:18px;font-weight:800;color:var(--color-text-primary);"><?= $stats['commandes'] ?></div><div style="font-size:9px;color:var(--color-text-muted);">commandes</div></div>
                </div>
                <div style="flex:1;">
                    <?php $couleursStatut = ['Livrée'=>'var(--color-chart-donut-1)','En route'=>'var(--color-chart-donut-2)','En préparation'=>'var(--color-chart-donut-3)','Confirmée'=>'var(--color-chart-donut-3)','En attente'=>'var(--color-chart-donut-3)','Annulée'=>'var(--color-chart-donut-4)']; ?>
                    <?php foreach ($statutPourcent as $statut => $pct): ?>
                    <div class="flex justify-between text-xs text-muted" style="margin-bottom:5px;">
                        <span><?= getStatutBadge($statut) ?></span>
                        <div style="height:4px;background:<?= $couleursStatut[$statut] ?? 'var(--gray-200)' ?>;width:<?= $pct ?>%;border-radius:var(--radius-pill);margin-left:8px;"></div>
                        <span style="margin-left:4px;"><?= $pct ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ALERTES -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Alertes &amp; Activité récente</span></div>
            <div style="padding:0 14px;">
                <?php if (!empty($dernieresCommandes)): foreach (array_slice($dernieresCommandes,0,3) as $cmd): ?>
                <div class="flex gap-3 items-start" style="padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <div class="notif-icon"><i class="fas fa-receipt"></i></div>
                    <div style="flex:1;"><div class="text-sm font-semibold">Commande #<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></div><div class="text-xs text-muted"><?= securiser(($cmd['prenom'] ?? '') . ' ' . ($cmd['nom'] ?? '')) ?> — <?= formatPrix($cmd['montant_total']) ?> &bull; <?= date('d/m/y H:i', strtotime($cmd['date_commande'])) ?></div></div>
                    <span><?= getStatutBadge($cmd['statut']) ?></span>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($derniersPaiements)): foreach (array_slice($derniersPaiements,0,3) as $pmt): ?>
                <div class="flex gap-3 items-start" style="padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <div class="notif-icon" style="color:var(--success);border-color:var(--success);"><i class="fas fa-credit-card"></i></div>
                    <div><div class="text-sm font-semibold">Paiement <?= $pmt['statut'] === 'Confirmé' ? 'confirmé' : $pmt['statut'] ?> #<?= $pmt['commande_id'] ?></div><div class="text-xs text-muted"><?= securiser($pmt['nom_complet'] ?? '') ?> — <?= securiser($pmt['mode']) ?> &bull; <?= formatPrix($pmt['montant']) ?></div></div>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($derniersAvis)): foreach (array_slice($derniersAvis,0,3) as $av): ?>
                <div class="flex gap-3 items-start" style="padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <div class="notif-icon"><i class="fas fa-star"></i></div>
                    <div><div class="text-sm font-semibold">Avis — <?= securiser($av['produit_nom']) ?></div><div class="text-xs text-muted"><?= securiser(($av['prenom'] ?? '') . ' ' . ($av['nom'] ?? '')) ?> — <?= str_repeat('★', $av['note']) ?><?= str_repeat('☆', 5 - $av['note']) ?></div></div>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($stockFaible)): foreach ($stockFaible as $p): ?>
                <div class="flex gap-3 items-start" style="padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <div class="notif-icon" style="color:var(--warning);border-color:var(--warning);"><i class="fas fa-exclamation-triangle"></i></div>
                    <div style="flex:1;"><div class="text-sm font-semibold">Stock faible — <?= securiser($p['nom']) ?></div><div class="text-xs text-muted">Plus que <?= intval($p['stock']) ?> unités restantes</div></div>
                    <a href="<?= BASE_URL ?>/admin/produits.php" class="btn btn-outline-dark btn-sm" style="padding:3px 10px;font-size:11px;">Réappro.</a>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($dernieresNotifs)): foreach (array_slice($dernieresNotifs,0,2) as $n): ?>
                <div class="flex gap-3 items-start" style="padding:12px 0;">
                    <div class="notif-icon"><i class="fas fa-bell"></i></div>
                    <div style="flex:1;"><div class="text-sm font-semibold"><?= securiser($n['titre'] ?? 'Notification') ?></div><div class="text-xs text-muted"><?= securiser(mb_substr($n['message'],0,80)) ?> &bull; <?= date('d/m/y H:i', strtotime($n['date_envoi'] ?? $n['date_creation'])) ?></div></div>
                </div>
                <?php endforeach; endif; ?>
                <?php if (empty($dernieresCommandes) && empty($stockFaible) && empty($derniersPaiements) && empty($derniersAvis) && empty($dernieresNotifs)): ?>
                <p class="text-muted text-sm" style="padding:16px;">Aucune activité récente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACCÈS RAPIDE MODULES -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Accès rapide aux modules</span></div>
            <div class="quick-access" style="grid-template-columns:repeat(3,1fr);padding:14px;">
                <a href="<?= BASE_URL ?>/admin/utilisateurs.php" class="quick-access-item"><i class="fas fa-users"></i><span>Utilisateurs</span><small><?= $stats['utilisateurs'] ?></small></a>
                <a href="<?= BASE_URL ?>/admin/produits.php" class="quick-access-item"><i class="fas fa-box"></i><span>Produits</span><small><?= $stats['produits'] ?></small></a>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="quick-access-item"><i class="fas fa-tag"></i><span>Catégories</span><small><?= $stats['categories'] ?></small></a>
                <a href="<?= BASE_URL ?>/admin/commandes.php" class="quick-access-item"><i class="fas fa-receipt"></i><span>Commandes</span><small>12 new</small></a>
                <a href="<?= BASE_URL ?>/admin/livraisons.php" class="quick-access-item"><i class="fas fa-truck"></i><span>Livraisons</span><small>8 actives</small></a>
                <a href="<?= BASE_URL ?>/admin/zones.php" class="quick-access-item"><i class="fas fa-map-marker-alt"></i><span>Zones</span><small>5 zones</small></a>
                <a href="<?= BASE_URL ?>/admin/livreurs.php" class="quick-access-item"><i class="fas fa-motorcycle"></i><span>Livreurs</span></a>
                <a href="<?= BASE_URL ?>/admin/paiements.php" class="quick-access-item"><i class="fas fa-credit-card"></i><span>Paiements</span></a>
                <a href="<?= BASE_URL ?>/admin/notifications.php" class="quick-access-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
            </div>
        </div>
    </div>
</div>
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop Admin</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
</body></html>
