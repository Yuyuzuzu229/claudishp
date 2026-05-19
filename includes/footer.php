<?php
if (!isset($categorie) || !($categorie instanceof Categorie)) {
    require_once __DIR__ . '/../classes/Categorie.php';
    $categorie = new Categorie();
}
$categoriesFooter = $categorie->getForNav();
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand"><span>CLAUDI</span><span>SHOP</span></div>
                <p>Mode &amp; accessoires — Homme, Femme, Enfant.<br>Cotonou, Bénin. Livraison partout au Bénin.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div>
                <h4>Collections</h4>
                <ul>
                    <?php foreach ($categoriesFooter as $cat): ?>
                    <li><a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $cat['id'] ?>"><?= securiser($cat['nom']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4>Mon compte</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/pages/connexion.php">Connexion / Inscription</a></li>
                    <li><a href="<?= BASE_URL ?>/user/mes_commandes.php">Mes commandes</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/panier.php">Mon panier</a></li>
                    <li><a href="<?= BASE_URL ?>/user/mes_avis.php">Mes avis</a></li>
                </ul>
            </div>
            <div>
                <h4>Aide</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/pages/livraison_retours.php">Livraison &amp; retours</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/guide_des_tailles.php">Guide des tailles</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/politique_de_retrait.php">Politique de retrait</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/faq.php">FAQ</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/aide.php">Aide</a></li>
                    <li><a href="<?= BASE_URL ?>/driver/connexion.php">Accès livreur</a></li>
                </ul>
            </div>
            <div>
                <h4>Nous suivre</h4>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook me-2"></i>Facebook</a></li>
                    <li><a href="#"><i class="fab fa-instagram me-2"></i>Instagram</a></li>
                    <li><a href="#"><i class="fab fa-whatsapp me-2"></i>WhatsApp</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money &middot; Guide des tailles disponible en chaque point gratuit
        </div>
    </div>
</footer>
</body>
</html>
