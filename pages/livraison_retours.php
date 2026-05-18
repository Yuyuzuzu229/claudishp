<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Livraison & Retours';
$pageStyles = [BASE_URL . '/assets/css/pages.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-banner"><div class="container"><h1>Livraison &amp; Retours</h1><p>Tout savoir sur nos conditions de livraison et d'échange.</p></div></div>
<div class="container page-content">
    <div class="page-section">
        <h2>Livraison</h2>
        <p>Nous livrons partout au Bénin. Les délais de livraison sont de 24h à 72h ouvrées selon votre localisation. La livraison est gratuite pour toute commande de 500 000 FCFA ou plus.</p>
        <ul class="page-list">
            <li><strong>Cotonou &amp; périphéries :</strong> 24h – 48h</li>
            <li><strong>Autres villes du Bénin :</strong> 48h – 72h</li>
            <li><strong>Livraison gratuite :</strong> dès 500 000 FCFA d'achat</li>
        </ul>
    </div>
    <div class="page-section">
        <h2>Retours &amp; Échanges</h2>
        <p>Vous disposez de <strong>7 jours</strong> après réception pour retourner ou échanger un article. Les articles doivent être dans leur état d'origine, non portés et avec leurs étiquettes.</p>
        <p><strong>Procédure :</strong></p>
        <ol class="page-list">
            <li>Contactez-nous via WhatsApp ou email pour initier le retour.</li>
            <li>Emballez l'article dans son emballage d'origine.</li>
            <li>Nous vous indiquons l'adresse de retour.</li>
            <li>L'échange est traité sous 48h après réception.</li>
        </ol>
        <p><em>Note : Les frais de retour sont à la charge du client sauf en cas d'erreur de notre part.</em></p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
