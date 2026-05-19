<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? securiser($pageTitle) . ' — ' : '' ?>CLAUDI SHOP</title>
    <meta name="description" content="CLAUDI SHOP – Mode & Accessoires Homme / Femme / Enfant – Cotonou, Bénin. Paiement MTN MoMo & Moov Money.">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=3">
    <?php if (!empty($pageStyles)): foreach ((array)$pageStyles as $s): ?>
    <link rel="stylesheet" href="<?= $s ?>">
    <?php endforeach; endif; ?>
    <link rel="icon" type="image/svg+xml" href="<?= ASSETS_URL ?>/images/brand/favicon.svg">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#1f2937">
    <script defer src="<?= ASSETS_URL ?>/js/script.js"></script>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>

<div id="page-loader"><div class="loader-spinner"></div></div>
