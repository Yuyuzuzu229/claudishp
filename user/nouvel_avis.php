<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Avis pour gérer les avis clients
require_once __DIR__ . '/../classes/Avis.php';
// Inclusion de la classe Panier pour gérer le panier
require_once __DIR__ . '/../classes/Panier.php';
// Inclusion de la classe Notification pour gérer les notifications
require_once __DIR__ . '/../classes/Notification.php';

// Vérification : rediriger vers la connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Nouvel avis';
// Instanciation de l'objet Avis
$avisObj = new Avis();
// Récupération des produits que l'utilisateur peut évaluer (achetés et non encore notés)
$produits = $avisObj->getProduitsAchetables($_SESSION['user_id']);

// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'avis';
?>
<!-- Début du layout du tableau de bord -->
<div class="dashboard-layout">
<?php // Inclusion de la barre latérale utilisateur ?>
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php // Inclusion de la barre supérieure du tableau de bord ?>
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <!-- En-tête de page avec lien retour -->
    <div class="dash-page-header">
        <div class="dash-page-label"><a href="<?= BASE_URL ?>/user/mes_avis.php" style="color:var(--gray-400);"><i class="fas fa-arrow-left"></i> Mes avis</a></div>
        <h1 class="dash-page-title">Donner un avis</h1>
        <p class="dash-page-sub">Sélectionnez un produit que vous avez acheté pour laisser votre évaluation.</p>
    </div>

    <!-- Carte contenant les produits à évaluer -->
    <div class="table-card">
        <?php // Vérification si des produits sont disponibles pour évaluation ?>
        <?php if (empty($produits)): ?>
        <!-- Message si tous les produits ont déjà été évalués -->
        <div style="padding:48px;text-align:center;">
            <i class="fas fa-check-circle" style="font-size:40px;color:var(--success, #10B981);margin-bottom:16px;display:block;"></i>
            <p class="text-muted" style="margin-bottom:4px;">Vous avez déjà évalué tous vos produits achetés.</p>
            <p class="text-xs text-muted">Ou vous n'avez pas encore de commande confirmée.</p>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark" style="margin-top:16px;">Découvrir nos produits</a>
        </div>
        <?php else: ?>
        <!-- En-tête indiquant le nombre de produits à évaluer -->
        <div class="table-card-header"><span class="table-card-title">Produits à évaluer (<?= count($produits) ?>)</span></div>
        <!-- Liste des produits avec formulaire d'évaluation -->
        <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
            <?php // Boucle sur chaque produit achetable ?>
            <?php foreach ($produits as $p): ?>
            <div style="border:1px solid var(--gray-200);border-radius:6px;padding:12px;">
                <!-- Formulaire d'ajout d'avis -->
                <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_avis.php" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <!-- Champ caché pour l'ID du produit -->
                    <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                    <!-- Icône du produit -->
                    <div style="width:48px;height:48px;border-radius:4px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-tshirt" style="color:var(--gray-400);font-size:18px;"></i>
                    </div>
                    <!-- Nom et prix du produit -->
                    <div style="flex:1;min-width:140px;">
                        <div class="text-sm font-semibold"><?= securiser($p['nom']) ?></div>
                        <div class="text-xs text-muted"><?= renderPrix($p['prix'], $p['solde_prix']) ?></div>
                    </div>
                    <!-- Système de notation par étoiles interactif -->
                    <div class="star-rating" data-produit="<?= $p['id'] ?>" style="display:flex;gap:4px;font-size:22px;color:var(--gray-300);cursor:pointer;">
                        <?php // Boucle de génération des 5 étoiles ?>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span data-value="<?= $i ?>" class="star">☆</span>
                        <?php endfor; ?>
                    </div>
                    <!-- Champ caché pour stocker la note sélectionnée -->
                    <input type="hidden" name="note" class="note-input" value="5">
                    <!-- Champ de commentaire -->
                    <input type="text" name="commentaire" placeholder="Votre commentaire..." class="form-control" style="flex:1;min-width:180px;padding:8px 10px;font-size:13px;border:1px solid var(--gray-200);border-radius:4px;">
                    <!-- Bouton de publication -->
                    <button type="submit" class="btn btn-dark" style="white-space:nowrap;padding:8px 16px;font-size:13px;">Publier</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Pied de page -->
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
<!-- Script JavaScript pour gérer l'interaction avec les étoiles -->
<script>
// Attente du chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Parcours de tous les conteneurs d'étoiles
    document.querySelectorAll('.star-rating').forEach(function(container) {
        var input = container.parentElement.querySelector('.note-input');
        // Ajout des événements sur chaque étoile
        container.querySelectorAll('.star').forEach(function(star) {
            // Clic sur une étoile : définit la note
            star.addEventListener('click', function() {
                var val = parseInt(this.dataset.value);
                if (input) input.value = val;
                // Mise à jour visuelle des étoiles
                container.querySelectorAll('.star').forEach(function(s, idx) {
                    s.textContent = idx < val ? '★' : '☆';
                    s.style.color = idx < val ? 'var(--warning, #F59E0B)' : 'var(--gray-300)';
                });
            });
            // Survol d'une étoile : prévisualisation
            star.addEventListener('mouseenter', function() {
                var val = parseInt(this.dataset.value);
                container.querySelectorAll('.star').forEach(function(s, idx) {
                    s.textContent = idx < val ? '★' : '☆';
                    s.style.color = idx < val ? 'var(--warning, #F59E0B)' : 'var(--gray-300)';
                });
            });
        });
        // Sortie de la zone : retour à la note réelle
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
