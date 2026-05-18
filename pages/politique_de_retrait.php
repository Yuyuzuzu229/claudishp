<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Politique de retrait';
$pageStyles = [BASE_URL . '/assets/css/pages.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-banner"><div class="container"><h1>Politique de retrait</h1><p>Modalités de retrait en point de vente.</p></div></div>
<div class="container page-content">
    <div class="page-section">
        <h2>Retrait en magasin</h2>
        <p>Vous pouvez retirer votre commande directement à notre boutique située à <strong>Wologuèdè, Mairie, Cotonou</strong>. Le retrait est gratuit et disponible du lundi au samedi.</p>
        <ul class="page-list">
            <li><strong>Horaires :</strong> 9h00 – 18h00 (Lun-Sam)</li>
            <li><strong>Adresse :</strong> Wologuèdè, Mairie, Cotonou</li>
            <li><strong>Délai de retrait :</strong> 7 jours après notification de disponibilité</li>
        </ul>
    </div>
    <div class="page-section">
        <h2>Documents requis</h2>
        <p>Pour retirer votre commande, présentez-vous avec :</p>
        <ul class="page-list">
            <li>Votre numéro de commande</li>
            <li>Une pièce d'identité (CNIB ou passeport)</li>
            <li>Le reçu de paiement (si paiement déjà effectué)</li>
        </ul>
    </div>
    <div class="page-section">
        <h2>Retrait par un tiers</h2>
        <p>Si vous mandatez une autre personne pour retirer votre commande, celle-ci doit présenter une copie de votre pièce d'identité et une autorisation écrite (simple message WhatsApp accepté).</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
