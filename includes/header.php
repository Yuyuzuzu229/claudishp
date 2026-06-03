<!DOCTYPE html>
<!-- Déclaration du type de document HTML5 -->
<html lang="fr">
<!-- Début du document HTML avec la langue française -->
<head>
    <!-- Métadonnées : encodage UTF-8 et viewport responsive -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titre dynamique de la page avec échappement et sécurisation -->
    <title><?= isset($pageTitle) ? securiser($pageTitle) . ' — ' : '' ?>CLAUDI SHOP</title>
    <!-- Description meta pour le référencement -->
    <meta name="description" content="CLAUDI SHOP – Mode & Accessoires Homme / Femme / Enfant – Cotonou, Bénin. Paiement MTN MoMo & Moov Money.">
    <!-- Préconnexion aux CDN pour optimiser le chargement des ressources -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Chargement de Font Awesome via CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chargement du fichier CSS principal avec cache-busting -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=3">
    <!-- Chargement des feuilles de style supplémentaires si définies -->
    <?php if (!empty($pageStyles)): foreach ((array)$pageStyles as $s): ?>
    <link rel="stylesheet" href="<?= $s ?>">
    <?php endforeach; endif; ?>
    <!-- Définition du favicon au format SVG -->
    <link rel="icon" type="image/svg+xml" href="<?= ASSETS_URL ?>/images/brand/favicon.svg">
    <!-- Lien vers le manifeste de l'application progressive (PWA) -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <!-- Couleur du thème pour la barre d'outils du navigateur mobile -->
    <meta name="theme-color" content="#1f2937">
    <!-- Chargement différé du fichier JavaScript principal -->
    <script defer src="<?= ASSETS_URL ?>/js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input, textarea').forEach(function(el) {
            el.addEventListener('blur', function() { this.value = this.value.trim(); });
            el.addEventListener('input', function() {
                if (this.tagName === 'TEXTAREA') return;
                var start = this.selectionStart;
                var end = this.selectionEnd;
                var trimmed = this.value.trimStart();
                var diff = this.value.length - trimmed.length;
                this.value = trimmed;
                if (this === document.activeElement) {
                    this.setSelectionRange(Math.max(0, start - diff), Math.max(0, end - diff));
                }
            });
        });
        document.querySelectorAll('form').forEach(function(f) {
            f.addEventListener('submit', function() {
                this.querySelectorAll('input, textarea').forEach(function(i) { i.value = i.value.trim(); });
            });
        });
    });
    </script>
    <!-- Métadonnées pour l'application web mobile -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>

<!-- Écran de chargement (loader) de la page -->
<div id="page-loader"><div class="loader-spinner"></div></div>
