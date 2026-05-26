<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Produit
require_once __DIR__ . '/../classes/Produit.php';
// Inclusion de la classe Categorie
require_once __DIR__ . '/../classes/Categorie.php';
// Inclusion de la classe Commande
require_once __DIR__ . '/../classes/Commande.php';
// Inclusion de la classe Utilisateur
require_once __DIR__ . '/../classes/Utilisateur.php';
// Inclusion de la classe Paiement
require_once __DIR__ . '/../classes/Paiement.php';
// Inclusion de la classe Avis
require_once __DIR__ . '/../classes/Avis.php';
// Inclusion de la classe Livraison
require_once __DIR__ . '/../classes/Livraison.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Dashboard Admin';
// Instanciation de l'objet Produit
$produitObj = new Produit();
// Instanciation de l'objet Categorie
$categorieObj = new Categorie();
// Instanciation de l'objet Commande
$commandeObj = new Commande();
// Instanciation de l'objet Utilisateur
$utilisateurObj = new Utilisateur();
// Instanciation de l'objet Paiement
$paiementObj = new Paiement();
// Instanciation de l'objet Avis
$avisObj = new Avis();
// Instanciation de l'objet Livraison
$livraisonObj = new Livraison();

// Définition des périodes pour les KPI comparatifs
$debutMois = date('Y-m-01 00:00:00');
$finMois = date('Y-m-t 23:59:59');
$debutMoisPrec = date('Y-m-01 00:00:00', strtotime('-1 month'));
$finMoisPrec = date('Y-m-t 23:59:59', strtotime('-1 month'));

// Stats comparatives
$commandesMois = $commandeObj->getNombreByPeriode($debutMois, $finMois);
$commandesMoisPrec = $commandeObj->getNombreByPeriode($debutMoisPrec, $finMoisPrec);
$evolCommandes = $commandesMoisPrec > 0 ? round(($commandesMois - $commandesMoisPrec) / $commandesMoisPrec * 100) : ($commandesMois > 0 ? 100 : 0);

$ventesMois = $commandeObj->getTotalVentesByPeriode($debutMois, $finMois);
$ventesMoisPrec = $commandeObj->getTotalVentesByPeriode($debutMoisPrec, $finMoisPrec);
$evolVentes = $ventesMoisPrec > 0 ? round(($ventesMois - $ventesMoisPrec) / $ventesMoisPrec * 100) : ($ventesMois > 0 ? 100 : 0);

$utilisateursMois = $utilisateurObj->getNombreByPeriode($debutMois, $finMois);
$utilisateursMoisPrec = $utilisateurObj->getNombreByPeriode($debutMoisPrec, $finMoisPrec);
$evolUtilisateurs = $utilisateursMoisPrec > 0 ? round(($utilisateursMois - $utilisateursMoisPrec) / $utilisateursMoisPrec * 100) : ($utilisateursMois > 0 ? 100 : 0);

$tauxLivraison = $livraisonObj->getTauxLivraison();

// Récupération des statistiques générales pour les KPI du tableau de bord
$stats = [
    'produits' => $produitObj->getNombre(),
    'categories' => $categorieObj->getNombre(),
    'commandes' => $commandeObj->getNombre(),
    'total_ventes' => $commandeObj->getTotalVentes(),
    'utilisateurs' => $utilisateurObj->getNombre(),
    'total_paiements' => $paiementObj->getTotalPaiements(),
    'avis' => $avisObj->getNombre(),
];

// Récupération des 6 dernières commandes pour l'affichage dans le tableau récent
$dernieresCommandes = $commandeObj->getDernieresCommandes(6);

// Connexion à la base de données via PDO
$pdo = getPdo();
// Requête SQL : récupération des produits dont le stock est faible (entre 1 et 5), triés par stock croissant, limité à 3
$stockFaible = $pdo->query("SELECT id, nom, stock FROM produit WHERE stock <= 5 AND stock > 0 ORDER BY stock ASC LIMIT 3")->fetchAll();
// Requête SQL : comptage des commandes groupé par statut
$statsStatuts = $pdo->query("SELECT statut, COUNT(*) as nb FROM commande GROUP BY statut")->fetchAll();
// Évite la division par zéro si aucune commande n'existe
$totalCmd = $stats['commandes'] ?: 1;
$statutPourcent = [];
// Boucle de calcul du pourcentage de chaque statut par rapport au total des commandes
foreach ($statsStatuts as $s) {
    $statutPourcent[$s['statut']] = round($s['nb'] / $totalCmd * 100);
}
// Requête SQL : récupération des ventes des 7 derniers jours, groupées par jour (hors commandes annulées)
$ventes7jRaw = $pdo->query("SELECT DATE(date_commande) as jour, SUM(montant_total) as total FROM commande WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND statut != 'Annulée' GROUP BY DATE(date_commande) ORDER BY jour")->fetchAll();
$ventesMax = 1;
// Boucle pour déterminer le montant de vente maximum sur la période (pour l'échelle du graphique)
foreach ($ventes7jRaw as $v) if ($v['total'] > $ventesMax) $ventesMax = $v['total'];
// Requête SQL : récupération des 3 derniers avis publiés avec les informations utilisateur et produit associées
$derniersAvis = $pdo->query("SELECT a.*, u.nom, u.prenom, p.nom as produit_nom FROM avis a JOIN utilisateur u ON a.utilisateur_id = u.id JOIN produit p ON a.produit_id = p.id WHERE a.statut = 'Publié' ORDER BY a.date_creation DESC LIMIT 3")->fetchAll();
// Requête SQL : récupération des 3 dernières notifications avec les informations utilisateur associées
$dernieresNotifs = $pdo->query("SELECT n.*, u.nom, u.prenom FROM notification n LEFT JOIN utilisateur u ON n.utilisateur_id = u.id ORDER BY n.date_envoi DESC LIMIT 3")->fetchAll();
// Requête SQL : récupération des 3 derniers paiements avec le nom complet du client
$derniersPaiements = $pdo->query("SELECT p.*, c.nom_complet FROM paiement p JOIN commande c ON p.commande_id = c.id ORDER BY p.date_paiement DESC LIMIT 3")->fetchAll();

// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'dashboard';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Jost:wght@300;400;500;600&display=swap');
.dashboard-layout {
  --red: #e94560; --red-dark: #c0392b; --dark: #111111; --dark-2: #1c1c1c;
  --navy: #1a1a2e; --white: #fff; --bg: #f5f4f0; --bg-2: #f9f9f7;
  --border: #e8e8e4; --text: #222; --text-2: #555; --text-3: #999;
  --green: #27ae60; --amber: #f0c040; --blue: #1565c0;
  --font-display: 'Cormorant Garamond', Georgia, serif;
  --font-body: 'Jost', 'Trebuchet MS', sans-serif;
}
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
.kpi-card {
  border-radius:10px; padding:22px 24px; display:flex; justify-content:space-between; align-items:flex-start;
  box-shadow:0 4px 16px rgba(0,0,0,.10); position:relative; overflow:hidden;
}
.kpi-card--navy  { background:linear-gradient(135deg,#1a1a2e,#16213e); }
.kpi-card--red   { background:linear-gradient(135deg,#e94560,#a0253a); }
.kpi-card--green { background:linear-gradient(135deg,#1a6b3c,#145230); }
.kpi-card--amber { background:linear-gradient(135deg,#b7791f,#92600a); }
.kpi-card__label { font-size:.65rem; color:rgba(255,255,255,.6); letter-spacing:2px; text-transform:uppercase; margin-bottom:10px; }
.kpi-card__value { font-size:2rem; font-weight:700; color:#fff; line-height:1; }
.kpi-card__value--sm { font-size:1.5rem; }
.kpi-card__trend { font-size:.7rem; color:rgba(255,255,255,.6); margin-top:10px; }
.kpi-card__trend--up { color:#7fff9a; }
.kpi-card__trend--down { color:#ffb3b3; }
.kpi-card__icon { font-size:2.2rem; opacity:.8; }
.row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.row-3 { display:grid; grid-template-columns:1fr 1.15fr 1fr; gap:20px; }
.card { background:#fff; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.07); overflow:hidden; }
.card__header {
  padding:18px 24px 14px; display:flex; align-items:baseline; justify-content:space-between;
  border-bottom:1px solid var(--bg-2);
}
.card__title { font-size:.95rem; font-weight:600; color:var(--text); }
.card__subtitle { font-size:.7rem; color:var(--text-3); margin-top:2px; }
.card__link { font-size:.75rem; color:var(--red); font-weight:500; text-decoration:none; }
.card__link:hover { text-decoration:underline; }
.card__body { padding:16px 24px; }
.chart-wrap { padding:16px 24px 20px; }
.chart-bars { display:flex; align-items:flex-end; gap:10px; height:160px; padding:0 0 8px; border-bottom:1px solid var(--border); }
.chart-bar-group { display:flex; flex-direction:column; align-items:center; gap:6px; flex:1; }
.chart-bar {
  width:100%; background:linear-gradient(180deg,var(--red),var(--red-dark));
  border-radius:4px 4px 0 0; position:relative; transition:opacity .2s; min-height:4px;
}
.chart-bar:hover { opacity:.85; }
.chart-bar__tooltip {
  position:absolute; top:-28px; left:50%; transform:translateX(-50%);
  background:var(--navy); color:white; font-size:.6rem; padding:3px 7px;
  border-radius:4px; white-space:nowrap; display:none;
}
.chart-bar:hover .chart-bar__tooltip { display:block; }
.chart-label { font-size:.65rem; color:var(--text-3); }
.chart-legend { display:flex; gap:16px; padding:10px 0 0; font-size:.7rem; color:var(--text-3); }
.chart-legend__dot { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:4px; }
table { width:100%; border-collapse:collapse; font-size:.8rem; }
thead tr { background:var(--bg-2); }
th {
  padding:10px 16px; text-align:left; font-size:.65rem; font-weight:600; color:var(--text-3);
  letter-spacing:1px; text-transform:uppercase; white-space:nowrap;
}
td { padding:11px 16px; border-bottom:1px solid var(--bg-2); color:var(--text-2); vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:var(--bg); }
.alert-list { display:flex; flex-direction:column; gap:8px; padding:14px 20px; }
.alert-item {
  display:flex; align-items:center; gap:12px; background:var(--bg-2); border-radius:6px;
  padding:10px 14px; font-size:.78rem;
}
.alert-item__icon { font-size:1.1rem; flex-shrink:0; }
.alert-item__body { flex:1; }
.alert-item__title { font-weight:500; color:var(--text); }
.alert-item__sub { font-size:.68rem; color:var(--text-3); margin-top:1px; }
.alert-item__btn {
  padding:4px 12px; border-radius:6px; border:none; font-size:.68rem; font-weight:600;
  cursor:pointer; text-decoration:none; transition:opacity .15s;
}
.alert-item__btn:hover { opacity:.85; }
.alert-item__btn--navy { background:var(--navy); color:white; }
.quick-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; padding:14px 20px; }
.quick-item {
  background:var(--bg); border-radius:6px; padding:14px 10px; display:flex; flex-direction:column;
  align-items:center; gap:6px; cursor:pointer; transition:background .15s,transform .15s; text-decoration:none; color:inherit;
}
.quick-item:hover { background:var(--border); transform:translateY(-2px); }
.quick-item__icon { font-size:1.4rem; }
.quick-item__label { font-size:.72rem; color:var(--text); text-align:center; }
.quick-item__count { font-size:.65rem; color:var(--red); font-weight:600; }
.quick-item__badge { background:var(--red); color:white; font-size:.6rem; padding:1px 7px; border-radius:8px; font-weight:600; }
.donut-legend { display:grid; grid-template-columns:1fr 1fr; gap:6px 16px; width:100%; }
.donut-legend__item { display:flex; align-items:center; gap:6px; font-size:.72rem; color:var(--text-2); }
.donut-legend__dot { width:10px; height:10px; border-radius:2px; flex-shrink:0; }
.badge {
  display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:12px;
  font-size:.68rem; font-weight:500; white-space:nowrap;
}
.badge--green  { background:#e8f5e9; color:#2e7d32; }
.badge--red    { background:#fce4ec; color:#c62828; }
.badge--blue   { background:#e3f2fd; color:#1565c0; }
.badge--amber  { background:#fff8e1; color:#f57f17; }
.badge--grey   { background:#f5f5f5; color:#616161; }
</style>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Tableau de bord</div>
        <h1 class="dash-page-title">Vue d'ensemble</h1>
    </div>

    <!-- KPI : Affichage des indicateurs clés de performance -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--navy">
            <div>
                <div class="kpi-card__label">Commandes totales</div>
                <div class="kpi-card__value"><?= number_format($stats['commandes']) ?></div>
                <div class="kpi-card__trend <?= $evolCommandes >= 0 ? 'kpi-card__trend--up' : 'kpi-card__trend--down' ?>"><?= $evolCommandes >= 0 ? '▲' : '▼' ?> <?= abs($evolCommandes) ?>% ce mois</div>
            </div>
            <div class="kpi-card__icon">📋</div>
        </div>
        <div class="kpi-card kpi-card--red">
            <div>
                <div class="kpi-card__label">Chiffre d'affaires</div>
                <div class="kpi-card__value kpi-card__value--sm"><?= formatPrix($stats['total_ventes']) ?></div>
                <div class="kpi-card__trend <?= $evolVentes >= 0 ? 'kpi-card__trend--up' : 'kpi-card__trend--down' ?>"><?= $evolVentes >= 0 ? '▲' : '▼' ?> <?= abs($evolVentes) ?>% vs mois dernier</div>
            </div>
            <div class="kpi-card__icon">💰</div>
        </div>
        <div class="kpi-card kpi-card--green">
            <div>
                <div class="kpi-card__label">Clients actifs</div>
                <div class="kpi-card__value"><?= number_format($stats['utilisateurs']) ?></div>
                <div class="kpi-card__trend <?= $evolUtilisateurs >= 0 ? 'kpi-card__trend--up' : 'kpi-card__trend--down' ?>"><?= $evolUtilisateurs >= 0 ? '▲' : '▼' ?> <?= abs($evolUtilisateurs) ?>% ce mois</div>
            </div>
            <div class="kpi-card__icon">👥</div>
        </div>
        <div class="kpi-card kpi-card--amber">
            <div>
                <div class="kpi-card__label">Taux de livraison</div>
                <div class="kpi-card__value"><?= number_format($tauxLivraison, 1) ?>%</div>
                <div class="kpi-card__trend">Taux de livraison global</div>
            </div>
            <div class="kpi-card__icon">🚚</div>
        </div>
    </div>

    <div class="row-2">
        <!-- GRAPHE : Histogramme des ventes des 7 derniers jours -->
        <div class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Ventes des 7 derniers jours</div>
                    <div class="card__subtitle">Chiffre d'affaires journalier (FCFA)</div>
                </div>
            </div>
            <div class="chart-wrap">
                <div class="chart-bars" id="chart-ventes7j">
                    <?php
                    // Tableau des abréviations des jours de la semaine
                    $days = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
                    // Boucle sur les 7 derniers jours pour générer les barres du graphique
                    for ($i = 6; $i >= 0; $i--):
                        // Calcul de la date du jour courant dans la boucle
                        $d = date('Y-m-d', strtotime("-$i days"));
                        $jourData = null;
                        // Recherche des données de vente pour ce jour précis
                        foreach ($ventes7jRaw as $v) { if ($v['jour'] === $d) { $jourData = $v; break; } }
                        // Calcul de la hauteur de la barre en pourcentage par rapport au max
                        $h = $jourData ? round($jourData['total'] / $ventesMax * 100) : 0;
                        // Formatage du montant pour l'affichage dans l'infobulle
                        $montant = $jourData ? formatPrix($jourData['total']) : '—';
                        // Calcul de l'index du jour pour récupérer son libellé
                        $idx = (int)date('N') - $i - 1;
                        // Ajustement si l'index devient négatif
                        if ($idx < 0) $idx += 7;
                        $dayLabel = $days[$idx];
                    ?>
                    <div class="chart-bar-group">
                        <div class="chart-bar" style="height:<?= max($h,4) ?>%">
                            <span class="chart-bar__tooltip"><?= $montant ?></span>
                        </div>
                        <span class="chart-label"><?= $dayLabel ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="chart-legend">
                    <span><span class="chart-legend__dot" style="background:var(--red);"></span> Ventes journalières</span>
                </div>
            </div>
        </div>

        <!-- COMMANDES RECENTES : Tableau des dernières commandes -->
        <div class="card">
            <div class="card__header">
                <span class="card__title">Commandes récentes</span>
                <a href="<?= BASE_URL ?>/admin/commandes.php" class="card__link">Voir tout →</a>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Client</th><th>Montant</th><th>Statut</th></tr></thead>
                <tbody>
                <?php if (empty($dernieresCommandes)): ?>
                <!-- Si aucune commande n'existe, affichage d'un message par défaut -->
                <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-3);">Aucune commande.</td></tr>
                <?php else: foreach ($dernieresCommandes as $cmd): ?>
                <!-- Boucle d'affichage de chaque commande dans une ligne du tableau -->
                <tr>
                    <td style="font-weight:600;">#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td><?= securiser(($cmd['prenom'] ?? '') . ' ' . ($cmd['nom'] ?? '')) ?></td>
                    <td><?= formatPrix($cmd['montant_total']) ?></td>
                    <td><?= getStatutBadge($cmd['statut']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row-3">
        <!-- STATUTS COMMANDES / DONUT : Graphique en anneau des statuts des commandes -->
        <div class="card">
            <div class="card__header"><span class="card__title">Statuts commandes</span></div>
            <div class="card__body" style="display:flex;gap:24px;align-items:center;">
                <svg width="160" height="160" viewBox="0 0 180 180" id="donut-commandes" style="flex-shrink:0;">
                    <circle cx="90" cy="90" r="70" fill="none" stroke="#f0f0f0" stroke-width="28"/>
                    <?php
                    // Couleurs attribuées à chaque statut dans le donut
                    $couleursDonut = ['#2ecc71','#f39c12','#e74c3c','#3498db','#95a5a6','#9b59b6'];
                    // Circonférence du cercle pour le calcul des dasharray
                    $circ = 2 * M_PI * 70;
                    $offset = 0;
                    // Boucle de création des segments du donut pour chaque statut
                    foreach ($statsStatuts as $si => $s):
                        // Calcul de la longueur du segment proportionnelle au nombre de commandes
                        $dash = ($s['nb'] / $totalCmd) * $circ;
                        $col = $couleursDonut[$si % count($couleursDonut)];
                    ?>
                    <circle cx="90" cy="90" r="70" fill="none" stroke="<?= $col ?>" stroke-width="28"
                        stroke-dasharray="<?= $dash ?> <?= $circ ?>" stroke-dashoffset="<?= -$offset ?>"
                        transform="rotate(-90 90 90)"/>
                    <?php $offset += $dash; endforeach; ?>
                    <text x="90" y="85" text-anchor="middle" font-size="20" font-weight="700" fill="#222"><?= $stats['commandes'] ?></text>
                    <text x="90" y="104" text-anchor="middle" font-size="10" fill="#aaa">commandes</text>
                </svg>
                <div class="donut-legend">
                    <?php foreach ($statsStatuts as $si => $s):
                        $col = $couleursDonut[$si % count($couleursDonut)];
                    ?>
                    <div class="donut-legend__item">
                        <span class="donut-legend__dot" style="background:<?= $col ?>"></span>
                        <?= $s['statut'] ?> <strong><?= $s['nb'] ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ALERTES : Liste des alertes et activités récentes -->
        <div class="card">
            <div class="card__header"><span class="card__title">Alertes &amp; Activité récente</span></div>
            <div class="alert-list">
                <?php if (!empty($dernieresCommandes)): foreach (array_slice($dernieresCommandes,0,3) as $cmd): ?>
                <!-- Affichage des 3 dernières commandes comme alertes -->
                <div class="alert-item">
                    <div class="alert-item__icon">🧾</div>
                    <div class="alert-item__body">
                        <div class="alert-item__title">Commande #<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></div>
                        <div class="alert-item__sub"><?= securiser(($cmd['prenom'] ?? '') . ' ' . ($cmd['nom'] ?? '')) ?> — <?= formatPrix($cmd['montant_total']) ?></div>
                    </div>
                    <?= getStatutBadge($cmd['statut']) ?>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($stockFaible)): foreach ($stockFaible as $p): ?>
                <!-- Affichage des alertes de stock faible -->
                <div class="alert-item">
                    <div class="alert-item__icon">⚠️</div>
                    <div class="alert-item__body">
                        <div class="alert-item__title">Stock faible — <?= securiser($p['nom']) ?></div>
                        <div class="alert-item__sub">Plus que <?= intval($p['stock']) ?> unités restantes</div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/produits.php" class="alert-item__btn alert-item__btn--navy">Réappro.</a>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($derniersAvis)): foreach (array_slice($derniersAvis,0,3) as $av): ?>
                <!-- Affichage des 3 derniers avis clients -->
                <div class="alert-item">
                    <div class="alert-item__icon">⭐</div>
                    <div class="alert-item__body">
                        <div class="alert-item__title">Avis — <?= securiser($av['produit_nom']) ?></div>
                        <div class="alert-item__sub"><?= str_repeat('★', $av['note']) ?><?= str_repeat('☆', 5 - $av['note']) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                <?php if (empty($dernieresCommandes) && empty($stockFaible) && empty($derniersAvis)): ?>
                <!-- Message affiché si aucune activité récente n'est disponible -->
                <p style="padding:16px;color:var(--text-3);font-size:.8rem;">Aucune activité récente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACCÈS RAPIDE : Grille de liens rapides vers les modules d'administration -->
        <div class="card">
            <div class="card__header"><span class="card__title">Accès rapide aux modules</span></div>
            <div class="quick-grid">
                <a href="<?= BASE_URL ?>/admin/utilisateurs.php" class="quick-item">
                    <span class="quick-item__icon">👥</span>
                    <span class="quick-item__label">Utilisateurs</span>
                    <span class="quick-item__count"><?= $stats['utilisateurs'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/admin/produits.php" class="quick-item">
                    <span class="quick-item__icon">📦</span>
                    <span class="quick-item__label">Produits</span>
                    <span class="quick-item__count"><?= $stats['produits'] ?></span>
                </a>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="quick-item">
                    <span class="quick-item__icon">🏷</span>
                    <span class="quick-item__label">Catégories</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/commandes.php" class="quick-item">
                    <span class="quick-item__icon">🧾</span>
                    <span class="quick-item__label">Commandes</span>
                    <span class="quick-item__badge">!</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/livraisons.php" class="quick-item">
                    <span class="quick-item__icon">🚚</span>
                    <span class="quick-item__label">Livraisons</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/zones.php" class="quick-item">
                    <span class="quick-item__icon">🗺</span>
                    <span class="quick-item__label">Zones</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/livreurs.php" class="quick-item">
                    <span class="quick-item__icon">🏍</span>
                    <span class="quick-item__label">Livreurs</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/paiements.php" class="quick-item">
                    <span class="quick-item__icon">💳</span>
                    <span class="quick-item__label">Paiements</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/notifications.php" class="quick-item">
                    <span class="quick-item__icon">🔔</span>
                    <span class="quick-item__label">Notifications</span>
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Pied de page du tableau de bord -->
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop Admin</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
</body></html>
