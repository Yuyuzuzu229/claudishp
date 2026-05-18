<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'FAQ';
$pageStyles = [BASE_URL . '/assets/css/pages.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-banner"><div class="container"><h1>Foire aux questions</h1><p>Les réponses à vos questions les plus fréquentes.</p></div></div>
<div class="container page-content">
    <div class="page-section">
        <div class="faq-item">
            <h3 class="faq-q">Quels moyens de paiement acceptez-vous ?</h3>
            <p class="faq-a">Nous acceptons MTN MoMo et Moov Money. Le paiement est sécurisé et confirmé instantanément.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Combien de temps faut-il pour être livré ?</h3>
            <p class="faq-a">Les délais sont de 24h à 72h ouvrées selon votre localisation. Cotonou et environs : 24-48h. Autres villes : 48-72h.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Puis-je retourner un article ?</h3>
            <p class="faq-a">Oui, sous 7 jours après réception. L'article doit être dans son état d'origine, non porté et avec ses étiquettes. Voir notre page <a href="<?= BASE_URL ?>/pages/livraison_retours.php">Livraison &amp; retours</a>.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Comment suivre ma commande ?</h3>
            <p class="faq-a">Connectez-vous à votre espace client, rubrique "Mes commandes". Vous pouvez suivre l'état de votre livraison en temps réel.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Proposez-vous des cartes cadeaux ?</h3>
            <p class="faq-a">Pas encore, mais cette fonctionnalité arrive bientôt !</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Comment vous contacter ?</h3>
            <p class="faq-a">Par WhatsApp au +229 01 99 99 99 99, par email à contact@claudishop.com, ou via nos réseaux sociaux Facebook et Instagram.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Les prix affichés sont-ils TTC ?</h3>
            <p class="faq-a">Oui, tous nos prix sont toutes taxes comprises (TTC).</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Puis-je annuler ma commande ?</h3>
            <p class="faq-a">Vous pouvez annuler tant que la commande n'a pas été préparée. Contactez-nous rapidement via WhatsApp.</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
