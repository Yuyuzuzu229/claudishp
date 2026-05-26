<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Commande pour gérer les commandes
require_once __DIR__ . '/../classes/Commande.php';
// Inclusion de la classe Livraison pour gérer les livraisons
require_once __DIR__ . '/../classes/Livraison.php';
// Inclusion de la classe Livreur pour gérer les livreurs
require_once __DIR__ . '/../classes/Livreur.php';

// Vérification : rediriger vers la connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Suivi livraison';
// Instanciation des objets nécessaires
$commandeObj = new Commande();
$livraisonObj = new Livraison();

// Récupération de toutes les commandes de l'utilisateur
$allCommandes = $commandeObj->getByUtilisateur($_SESSION['user_id']);
// Initialisation du tableau des suivis
$suivis = [];
// Boucle sur chaque commande pour récupérer les informations de livraison
foreach ($allCommandes as $cmd) {
    $liv = $livraisonObj->getByCommande($cmd['id']);
    // Si une livraison existe pour cette commande
    if ($liv) {
        $livreurNom = '';
        // Si un livreur est assigné, récupérer son nom
        if ($liv['livreur_id']) {
            $livreurObj = new Livreur();
            $lv = $livreurObj->getById($liv['livreur_id']);
            $livreurNom = $lv ? $lv['nom'] : '';
        }
        // Ajout des données au tableau des suivis
        $suivis[] = [
            'commande' => $cmd,
            'livraison' => $liv,
            'livreur_nom' => $livreurNom,
        ];
    }
}

// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'suivi';
?>
<!-- Début du layout du tableau de bord -->
<div class="dashboard-layout">
<?php // Inclusion de la barre latérale utilisateur ?>
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php // Inclusion de la barre supérieure du tableau de bord ?>
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<!-- Conteneur principal avec ID pour le rafraîchissement AJAX -->
<div id="suivi-content" class="dash-content">
    <!-- En-tête de page -->
    <div class="dash-page-header">
        <div class="dash-page-label">Livraison</div>
        <h1 class="dash-page-title">Suivi de livraison</h1>
        <p class="dash-page-sub">Suivez l'état de vos colis en temps réel</p>
    </div>

    <?php // Vérification si l'utilisateur a des livraisons en cours ?>
    <?php if (empty($suivis)): ?>
    <!-- Message si aucune livraison -->
    <div class="table-card" style="padding:64px 32px;text-align:center;">
        <i class="fas fa-box-open" style="font-size:48px;color:var(--gray-200);margin-bottom:16px;display:block;"></i>
        <p class="text-muted">Aucune livraison en cours.</p>
        <p class="text-xs text-muted" style="margin-top:4px;">Passez une commande pour voir le suivi ici.</p>
        <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark btn-sm" style="margin-top:16px;"><i class="fas fa-shopping-bag"></i> Commander</a>
    </div>
    <?php else: ?>
    <!-- Liste des suivis de livraison -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <?php // Boucle sur chaque suivi de livraison ?>
        <?php foreach ($suivis as $s):
            $cmd = $s['commande'];
            $liv = $s['livraison'];
            // Génération des références
            $refCmd = 'CMD-' . str_pad($cmd['id'], 6, '0', STR_PAD_LEFT);
            $refLiv = 'LIV-' . str_pad($liv['id'], 4, '0', STR_PAD_LEFT);
        ?>
        <!-- Carte d'une livraison -->
        <div class="table-card" style="overflow:visible;">
            <!-- En-tête : référence commande et statut -->
            <div style="padding:16px 20px;border-bottom:1px solid var(--gray-100);">
                <div class="flex justify-between items-center">
                    <div>
                        <strong style="font-size:15px;">#<?= $refCmd ?></strong>
                        <span class="text-xs text-muted" style="margin-left:8px;"><?= $refLiv ?></span>
                    </div>
                    <?= getStatutBadge($liv['statut']) ?>
                </div>
                <!-- Date de la commande -->
                <div class="text-xs text-muted" style="margin-top:4px;"><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></div>
            </div>

            <!-- Barre de progression des étapes de livraison -->
            <div style="padding:24px 40px;">
                <?php
                // Définition des étapes de livraison avec leurs libellés et icônes
                $etapes = [
                    'En attente' => ['label' => 'Commande validée', 'icon' => 'fa-check-circle', 'desc' => 'Votre commande a été enregistrée'],
                    'Prêt à expédier' => ['label' => 'Préparation', 'icon' => 'fa-box', 'desc' => 'Votre colis est en cours de préparation'],
                    'En route' => ['label' => 'En route', 'icon' => 'fa-truck', 'desc' => 'Le livreur a démarré la livraison'],
                    'Livrée' => ['label' => 'Livrée', 'icon' => 'fa-gift', 'desc' => 'Colis livré avec succès !'],
                ];
                // Normalisation du statut actuel
                $statutActuel = $liv['statut'];
                if ($statutActuel === 'En cours') $statutActuel = 'En route';
                $statutIndex = array_keys($etapes);

                // Si la livraison est annulée ou échouée, affichage d'un message spécifique
                if ($statutActuel === 'Annulée' || $statutActuel === 'Échouée') {
                    echo '<div style="text-align:center;padding:20px 0;"><i class="fas fa-times-circle" style="font-size:36px;color:var(--danger);margin-bottom:8px;display:block;"></i><p style="font-weight:600;">Livraison ' . strtolower($statutActuel) . '</p></div>';
                } else {
                ?>
                <!-- Barre de progression visuelle -->
                <div style="position:relative;display:flex;justify-content:space-between;align-items:flex-start;">
                    <?php // Boucle sur chaque étape ?>
                    <?php foreach ($etapes as $key => $e):
                        // Étape déjà accomplie
                        $done = array_search($key, $statutIndex) <= array_search($statutActuel, $statutIndex);
                        // Étape actuelle
                        $current = $key === $statutActuel;
                    ?>
                    <div style="display:flex;flex-direction:column;align-items:center;flex:1;position:relative;text-align:center;">
                        <?php // Barre de connexion entre les étapes ?>
                        <?php if ($key !== array_key_first($etapes)): ?>
                        <div style="position:absolute;top:20px;right:50%;width:100%;height:3px;background:<?= $done ? 'var(--success)' : 'var(--gray-200)' ?>;z-index:0;"></div>
                        <?php endif; ?>
                        <!-- Cercle indicateur -->
                        <div style="width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $current ? 'var(--success)' : ($done ? '#f0fdf4' : 'var(--gray-100)') ?>;border:2px solid <?= $current ? 'var(--success)' : ($done ? 'var(--success)' : 'var(--gray-200)') ?>;color:<?= $current ? 'white' : ($done ? 'var(--success)' : 'var(--gray-400)') ?>;font-size:16px;z-index:1;transition:all 0.3s;">
                            <i class="fas <?= $e['icon'] ?>"></i>
                        </div>
                        <!-- Libellé et description -->
                        <div style="margin-top:10px;max-width:100px;">
                            <div style="font-size:12px;font-weight:<?= $current ? '700' : '600' ?>;color:<?= $current ? 'var(--success)' : ($done ? 'var(--dark)' : 'var(--gray-400)') ?>;"><?= $e['label'] ?></div>
                            <div style="font-size:10px;color:var(--gray-400);margin-top:2px;"><?= $e['desc'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php } ?>
            </div>

            <!-- Informations du livreur en bas de carte -->
            <?php if ($liv['livreur_nom'] || $liv['livreur_id']): ?>
            <div style="padding:14px 20px;border-top:1px solid var(--gray-100);background:var(--gray-50);display:flex;align-items:center;gap:12px;justify-content:space-between;flex-wrap:wrap;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-motorcycle" style="color:var(--dark);font-size:16px;"></i>
                    <span class="text-sm"><strong>Livreur :</strong> <?= securiser($s['livreur_nom'] ?: 'Assigné') ?></span>
                </div>
                <!-- Lien vers le détail de la commande -->
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
<!-- Pied de page -->
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop</span><span>v1.0.0</span></div>
</div>
</div>
<!-- Script JavaScript de rafraîchissement automatique de la page -->
<script>
(function(){
    var currentUrl = window.location.href;
    // Fonction de rafraîchissement périodique toutes les 5 secondes
    function actualiser() {
        fetch(currentUrl)
            .then(function(r){ return r.text(); })
            .then(function(html){
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newContent = doc.getElementById('suivi-content');
                var oldContent = document.getElementById('suivi-content');
                // Mise à jour du contenu si la section existe
                if (newContent && oldContent) {
                    oldContent.innerHTML = newContent.innerHTML;
                }
                setTimeout(actualiser, 5000);
            })
            // En cas d'erreur, réessayer dans 5 secondes
            .catch(function(){ setTimeout(actualiser, 5000); });
    }
    setTimeout(actualiser, 5000);
})();
</script>
</body></html>
