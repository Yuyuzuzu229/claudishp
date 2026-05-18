<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn()) { $user = $_SESSION; }
$pageTitle = 'Aide';
$pageStyles = [BASE_URL . '/assets/css/pages.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-banner"><div class="container"><h1>Aide</h1><p>Besoin d'assistance ? Nous sommes là pour vous.</p></div></div>
<div class="container page-content">
    <div class="page-section">
        <h2>Comment passer une commande ?</h2>
        <p>Parcourez notre catalogue, sélectionnez vos articles, ajoutez-les au panier et suivez les étapes de paiement. Vous recevrez une confirmation par email.</p>
    </div>
    <div class="page-section">
        <h2>Puis-je modifier ma commande ?</h2>
        <p>Une fois la commande validée, les modifications ne sont plus possibles. Contactez-nous rapidement via WhatsApp ou email pour toute demande d'annulation.</p>
    </div>
    <div class="page-section">
        <h2>Comment suivre ma commande ?</h2>
        <p>Connectez-vous à votre compte et rendez-vous dans "Mes commandes" pour suivre l'état de votre livraison en temps réel.</p>
    </div>
    <div class="page-section">
        <h2>Que faire en cas de problème ?</h2>
        <p>Contactez notre service client via WhatsApp au +229 01 99 99 99 99 ou par email à contact@claudishop.com. Nous vous répondrons sous 24h.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
