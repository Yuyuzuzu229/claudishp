<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion des classes nécessaires
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/ZoneLivraison.php';
require_once __DIR__ . '/../classes/Notification.php';

// Définition du titre de la page
$pageTitle = 'Finaliser la commande';
// Styles supplémentaires (Leaflet pour la carte)
$pageStyles = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'];

// Instanciation de l'objet Panier
$panierObj = new Panier();
// Vérification si l'utilisateur est un invité
$isGuest = !isLoggedIn();

// Récupération des données du panier selon le statut de l'utilisateur
if ($isGuest) {
    // Récupération du panier depuis la session pour les invités
    $lignes = $panierObj->guestGetLignes();
    $total = $panierObj->guestCalculerTotal();
    $nbArticles = $panierObj->guestGetNombreArticles();
} else {
    // Récupération du panier depuis la base de données pour les utilisateurs connectés
    $panierId = $panierObj->getPanierActif($_SESSION['user_id']);
    $lignes = $panierObj->getLignes($panierId);
    $total = $panierObj->calculerTotal($panierId);
    $nbArticles = $panierObj->getNombreArticles($panierId);
}

// Redirection vers le panier si aucun article
if (empty($lignes)) { redirect(BASE_URL . '/pages/panier.php'); }

// Récupération des zones de livraison actives
$zoneObj = new ZoneLivraison();
$zones = $zoneObj->getActives();

// Inclusion de l'en-tête HTML et de la barre de navigation
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="padding-top:28px;padding-bottom:60px;">
    <!-- Fil d'Ariane -->
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= BASE_URL ?>/pages/panier.php">Panier</a>
        <span class="breadcrumb-sep">/</span>
        <span>Finaliser la commande</span>
    </div>
    <h1 style="font-size:24px;font-weight:800;margin-bottom:28px;">Finaliser la commande</h1>

    <?php // Affichage des messages d'erreur stockés en session ?>
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <?php // Formulaire principal de commande ?>
    <form method="POST" action="<?= BASE_URL ?>/actions/commander.php" id="form-commande">
    <div class="checkout-layout">
        <div>
            <!-- SECTION MODE DE RETRAIT (en premier) -->
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-card-header"><span class="table-card-title">Mode de retrait</span></div>
                <div style="padding:20px;display:flex;flex-direction:column;gap:10px;">
                    <?php // Option livraison à domicile (sélectionnée par défaut) ?>
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--gray-200);cursor:pointer;">
                        <input type="radio" name="mode_retrait" value="Livraison" checked style="accent-color:var(--dark);">
                        <div><div class="font-semibold text-sm">Livraison à domicile</div><div class="text-xs text-muted">Livraison rapide partout au Bénin</div></div>
                    </label>
                    <?php // Option retrait en boutique (gratuit) ?>
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--gray-200);cursor:pointer;">
                        <input type="radio" name="mode_retrait" value="Retrait en boutique" style="accent-color:var(--dark);">
                        <div><div class="font-semibold text-sm">Retrait en boutique</div><div class="text-xs text-muted"><?= securiser(getShopAddress()) ?> – disponible sous 24h</div></div>
                        <span class="text-sm font-bold" style="margin-left:auto;">Gratuit</span>
                    </label>
                </div>
            </div>

            <!-- SECTION GÉOLOCALISATION (visible seulement si Livraison) -->
            <div class="table-card" id="section-geoloc" style="margin-bottom:20px;">
                <div class="table-card-header"><span class="table-card-title">Où livrer ?</span></div>
                <div style="padding:20px;">
                    <p class="text-sm text-muted" style="margin-bottom:12px;">Utilisez ma position pour déterminer votre zone de livraison ou saisissez votre adresse manuellement.</p>

                    <?php // Conteneur de la carte (caché par défaut) ?>
                    <div id="map-container" style="display:none;margin-bottom:12px;border:1px solid var(--gray-200);">
                        <div id="map" style="height:280px;"></div>
                        <div id="geoloc-info" style="padding:12px;background:var(--gray-50);font-size:13px;"></div>
                    </div>

                    <?php // Champs de saisie manuelle (cachés par défaut) ?>
                    <div id="manual-fields" style="display:none;margin-bottom:14px;">
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" name="ville" id="input-ville" class="form-control" placeholder="Ex : Cotonou, Abomey-Calavi...">
                        </div>
                        <div class="form-group">
                            <label>Quartier / Rue</label>
                            <input type="text" name="adresse" id="input-adresse" class="form-control" placeholder="Ex : Akpakpa, rue 123...">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Zone de livraison</label>
                            <?php // Sélecteur des zones de livraison ?>
                            <style>.zone-select option[value=""]{display:none}</style>
                            <select name="zone_id" id="input-zone" class="form-control zone-select">
                                <option value="" disabled selected>Sélectionnez votre zone</option>
                                <?php // Boucle sur les zones disponibles ?>
                                <?php foreach ($zones as $z): ?>
                                <option value="<?= $z['id'] ?>" data-tarif="<?= $z['tarif'] ?>">
                                    <?= securiser($z['nom']) ?> — <?= number_format($z['tarif'], 0, ',', ' ') ?> FCFA
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php // Affichage des frais de livraison pour la saisie manuelle ?>
                            <div id="manual-frais" class="text-sm font-semibold" style="margin-top:6px;color:var(--success);display:none;"></div>
                        </div>
                    </div>

                    <?php // Boutons géolocalisation et saisie manuelle (en bas) ?>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button" id="btn-geoloc" class="btn btn-outline-dark">
                            <i class="fas fa-location-dot"></i> Me géolocaliser
                        </button>
                        <button type="button" id="btn-manual" class="btn btn-outline-dark">
                            <i class="fas fa-pen"></i> Saisir mon adresse
                        </button>
                    </div>

                    <?php // Champs cachés contenant les coordonnées de géolocalisation ?>
                    <input type="hidden" name="latitude" id="input-latitude" value="">
                    <input type="hidden" name="longitude" id="input-longitude" value="">
                    <input type="hidden" name="zone_id_auto" id="input-zone-auto" value="">
                </div>
            </div>
            <!-- SECTION INFORMATIONS PERSONNELLES -->
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-card-header"><span class="table-card-title">Informations personnelles</span></div>
                <div style="padding:24px;">
                    <?php // Champ nom complet (prérempli si connecté) ?>
                    <div class="form-group">
                        <label>Nom complet <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="nom_complet" class="form-control" value="<?= securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?>" required>
                    </div>
                    <?php // Champ email (visible seulement pour les invités) ?>
                    <?php if ($isGuest): ?>
                    <div class="form-group">
                        <label>Email <span style="color:var(--danger);">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= securiser($_SESSION['guest_email'] ?? '') ?>" required placeholder="votre@email.com">
                    </div>
                    <?php endif; ?>
                    <?php // Champ téléphone avec sélecteur d'indicatif ?>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Téléphone <span style="color:var(--danger);">*</span></label>
                        <div class="flex gap-2">
                            <input type="tel" name="telephone" class="form-control" placeholder="+229 01 XX XX XX XX" value="<?= securiser($_SESSION['guest_telephone'] ?? '') ?>" required inputmode="numeric" oninput="var p='',d=this.value.replace(/[^0-9\+]/g,'');if(d[0]==='+'){var m=d.match(/^(\+22[589])/);if(m){p=m[1]+' ';d=d.slice(m[1].length)}else{p='+';d=d.slice(1)}}d=d.replace(/[^0-9]/g,'').slice(0,10);if(d.length>2)d=d.slice(0,2)+' '+d.slice(2);if(d.length>5)d=d.slice(0,5)+' '+d.slice(5);if(d.length>8)d=d.slice(0,8)+' '+d.slice(8);if(d.length>11)d=d.slice(0,11)+' '+d.slice(11);this.value=p+d">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paiement via Kkiapay (opérateur choisi dans le widget) -->
            <input type="hidden" name="mode_paiement" value="Kkiapay">
        </div>

        <!-- COLONNE DROITE : RÉCAPITULATIF DE COMMANDE -->
        <div>
            <div class="panier-recap">
                <h3>Récapitulatif de commande</h3>
                <?php // Boucle sur chaque ligne du panier pour afficher les articles ?>
                <?php foreach ($lignes as $ligne): ?>
                <div class="flex justify-between items-center" style="padding:8px 0;border-bottom:1px solid var(--gray-100);">
                    <div class="flex gap-2 items-center">
                        <div class="panier-table-img" style="width:40px;height:40px;font-size:14px;"><i class="fas fa-tshirt"></i></div>
                        <div>
                            <?php // Nom du produit ?>
                            <div class="text-sm font-semibold"><?= securiser($ligne['nom']) ?></div>
                            <?php if (!empty($ligne['taille'])): ?><div class="text-xs text-muted">Taille : <?= securiser($ligne['taille']) ?></div><?php endif; ?>
                            <?php // Quantité ?>
                            <div class="text-xs text-muted">x<?= $ligne['quantite'] ?></div>
                        </div>
                    </div>
                    <?php // Prix total pour cette ligne ?>
                    <div>
                        <strong class="text-sm"><?= formatPrix($ligne['prix_unitaire'] * $ligne['quantite']) ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php // Sous-total ?>
                <div class="recap-row"><span class="text-muted">Sous-total</span><strong><?= formatPrix($total) ?></strong></div>
                <?php // Frais de livraison (cachés par défaut, affichés via JS) ?>
                <div class="recap-row" id="recap-livraison" style="display:none;">
                    <span class="text-muted">Livraison</span>
                    <strong id="recap-frais">0 FCFA</strong>
                </div>
                <hr>
                <?php // Total général (mis à jour dynamiquement par JS) ?>
                <div class="recap-row"><strong style="font-size:15px;">Total</strong><strong class="recap-total" style="font-size:20px;" id="recap-total"><?= formatPrix($total) ?></strong></div>
                <?php // Badge de sécurité ?>
                <div class="secure-badge">
                    <i class="fas fa-shield-alt"></i>
                    <div><div class="font-semibold text-sm">Paiement 100% sécurisé</div><div class="text-xs text-muted">Vos transactions sont protégées.</div></div>
                </div>
                <?php // Bouton de confirmation de commande ?>
                <button type="submit" class="btn btn-dark btn-block btn-lg">Confirmer la commande</button>
                <p class="text-xs text-muted text-center" style="margin-top:10px;"><i class="fas fa-lock" style="margin-right:4px;"></i>Paiement sécurisé</p>
                <?php // Information pour les invités : création automatique d'un compte ?>
                <?php if ($isGuest): ?>
                <div class="text-xs text-muted text-center" style="margin-top:8px;padding:8px;background:var(--gray-50);border-radius:4px;">
                    <i class="fas fa-info-circle"></i> Un compte client sera créé automatiquement avec votre email après la commande.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </form>
</div>

<?php // Bibliothèque Leaflet pour la carte interactive ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Fonction anonyme auto-exécutée pour encapsuler les variables
(function () {
    // Constantes : positions de la boutique
    var SHOP_LAT = <?= getShopLat() ?>;
    var SHOP_LNG = <?= getShopLng() ?>;
    var map = null;
    var marker = null;
    var shopMarker = null;
    var userLat = null;
    var userLng = null;

    // Références aux éléments DOM
    var btnGeoloc = document.getElementById('btn-geoloc');
    var btnManual = document.getElementById('btn-manual');
    var mapContainer = document.getElementById('map-container');
    var mapEl = document.getElementById('map');
    var geolocInfo = document.getElementById('geoloc-info');
    var manualFields = document.getElementById('manual-fields');
    var inputLat = document.getElementById('input-latitude');
    var inputLng = document.getElementById('input-longitude');
    var inputZoneAuto = document.getElementById('input-zone-auto');
    var inputZone = document.getElementById('input-zone');
    var inputVille = document.getElementById('input-ville');
    var inputAdresse = document.getElementById('input-adresse');
    var manualFrais = document.getElementById('manual-frais');
    var recapLivraison = document.getElementById('recap-livraison');
    var recapFrais = document.getElementById('recap-frais');
    var recapTotal = document.getElementById('recap-total');
    var modeRetraitRadios = document.querySelectorAll('input[name="mode_retrait"]');
    var totalBase = <?= json_encode((float)$total) ?>;

    // Fonction de mise à jour du récapitulatif avec les frais de livraison
    function updateTotal(frais) {
        var f = parseFloat(frais) || 0;
        if (f > 0) { recapLivraison.style.display = 'flex'; recapFrais.textContent = f.toLocaleString('fr-FR') + ' FCFA'; }
        else { recapLivraison.style.display = 'none'; }
        recapTotal.textContent = (totalBase + f).toLocaleString('fr-FR') + ' FCFA';
    }

    // Fonction d'initialisation de la carte Leaflet
    function initMap(lat, lng, zoneName, tarif) {
        if (!map) {
            // Création de la carte si elle n'existe pas
            map = L.map(mapEl).setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        } else { map.setView([lat, lng], 14); }
        // Suppression du marqueur précédent
        if (marker) map.removeLayer(marker);
        // Nouveau marqueur positionnable
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.bindPopup('Votre position');
        // Marqueur de la boutique (créé une seule fois)
        if (!shopMarker) {
            shopMarker = L.marker([SHOP_LAT, SHOP_LNG], { icon: L.divIcon({ className: '', html: '<i class="fas fa-store" style="font-size:22px;color:var(--dark);"></i>', iconSize: [30, 30], iconAnchor: [15, 15] }) }).addTo(map);
            shopMarker.bindPopup('Boutique ClaudiShop');
        }
        // Écouteur d'événement pour le glisser-déposer du marqueur
        marker.on('dragend', function () { var pos = marker.getLatLng(); userLat = pos.lat; userLng = pos.lng; inputLat.value = userLat; inputLng.value = userLng; detectZone(userLat, userLng); });
        mapContainer.style.display = 'block';
        // Redimensionnement de la carte après affichage
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    // Fonction de géocodage inversé via Nominatim
    var _geoxhr = null;
    function reverseGeocode(lat, lng, callback) {
        if (_geoxhr) { _geoxhr.abort(); }
        _geoxhr = new XMLHttpRequest();
        _geoxhr.open('GET', 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&accept-language=fr', true);
        _geoxhr.onload = function () { if (_geoxhr.status === 200) { try { var data = JSON.parse(_geoxhr.responseText); callback(data.display_name || null); } catch(e) { callback(null); } } else { callback(null); } };
        _geoxhr.onerror = function () { callback(null); };
        _geoxhr.send();
    }

    // Fonction de détection de la zone de livraison par proximité
    function detectZone(lat, lng) {
        var found = null, minDist = Infinity;
        var zones = <?= json_encode(array_map(function($z) { return ['id' => $z['id'], 'nom' => $z['nom'], 'tarif' => (float)$z['tarif'], 'lat' => (float)($z['latitude'] ?? 0), 'lng' => (float)($z['longitude'] ?? 0)]; }, $zones)) ?>;
        // Calcul de la distance entre la position et chaque zone
        zones.forEach(function (z) { if (!z.lat || !z.lng) return; var d = Math.sqrt(Math.pow(lat - z.lat, 2) + Math.pow(lng - z.lng, 2)); if (d < minDist) { minDist = d; found = z; } });
        // Géocodage inversé pour obtenir l'adresse
        reverseGeocode(lat, lng, function (adresse) {
            var posLabel = adresse ? '<strong>Position :</strong> ' + adresse + '<br>' : '';
            if (found) {
                // Zone trouvée : affichage des frais
                inputZoneAuto.value = found.id;
                geolocInfo.innerHTML = posLabel + '<span class="text-muted">Frais de livraison : <strong>' + found.tarif.toLocaleString('fr-FR') + ' FCFA</strong></span>';
                updateTotal(found.tarif);
            } else {
                // Aucune zone trouvée : estimation des frais par distance
                inputZoneAuto.value = '';
                var R = 6371, dLat = (lat - SHOP_LAT) * Math.PI / 180, dLng = (lng - SHOP_LNG) * Math.PI / 180;
                var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(SHOP_LAT * Math.PI / 180) * Math.cos(lat * Math.PI / 180) * Math.sin(dLng/2) * Math.sin(dLng/2);
                var distKm = R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
                var fraisEstime = Math.round(distKm * <?= DELIVERY_PRICE_PER_KM ?>);
                geolocInfo.innerHTML = posLabel + '<span class="text-muted">Distance boutique → client : <strong>' + distKm.toFixed(1) + ' km</strong></span><br><span style="color:var(--warning);">Frais estimés : <strong>' + fraisEstime.toLocaleString('fr-FR') + ' FCFA</strong></span>';
                updateTotal(fraisEstime);
            }
        });
    }

    // Fonction de démarrage de la géolocalisation automatique
    function demarrerGeoloc() {
        // Si déjà localisé, réafficher la carte sans relancer le GPS
        if (btnGeoloc.classList.contains('active') && userLat && userLng) {
            manualFields.style.display = 'none';
            mapContainer.style.display = 'block';
            setTimeout(function () { if (map) map.invalidateSize(); }, 200);
            return;
        }
        if (!navigator.geolocation) { ouvrirCarteManuelle(); return; }
        btnGeoloc.disabled = true; btnGeoloc.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation...';
        navigator.geolocation.getCurrentPosition(function (pos) {
            userLat = pos.coords.latitude; userLng = pos.coords.longitude;
            // Rejeter les positions trop éloignées de la boutique (> 2 degrés ≈ 220 km)
            if (Math.abs(userLat - SHOP_LAT) > 2 || Math.abs(userLng - SHOP_LNG) > 2) {
                btnGeoloc.disabled = false; btnGeoloc.innerHTML = '<i class="fas fa-location-dot"></i> Me géolocaliser';
                ouvrirCarteManuelle();
                return;
            }
            inputLat.value = userLat; inputLng.value = userLng;
            manualFields.style.display = 'none'; initMap(userLat, userLng); detectZone(userLat, userLng);
            btnGeoloc.disabled = false; btnGeoloc.innerHTML = '<i class="fas fa-location-dot"></i> Me géolocaliser'; btnGeoloc.classList.add('active');
        }, function (err) {
            btnGeoloc.disabled = false; btnGeoloc.innerHTML = '<i class="fas fa-location-dot"></i> Me géolocaliser';
            if (err.code === 1) ouvrirCarteManuelle(); else { alert('Erreur de localisation.'); ouvrirCarteManuelle(); }
        }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 });
    }

    // Fonction d'ouverture de la carte manuelle (clic sur la carte)
    function ouvrirCarteManuelle() {
        manualFields.style.display = 'none'; btnGeoloc.classList.add('active');
        if (!map) initMap(SHOP_LAT, SHOP_LNG); else { mapContainer.style.display = 'block'; setTimeout(function () { map.invalidateSize(); }, 200); }
        geolocInfo.innerHTML = '<span style="color:var(--warning);font-weight:600;">👆 Cliquez sur la carte pour placer votre position</span><br><span class="text-muted">Vous pouvez aussi faire glisser le marqueur.</span>';
        // Écouteur de clic sur la carte pour placer un marqueur (éviter les doublons)
        map.off('click');
        map.on('click', function (e) {
            userLat = e.latlng.lat; userLng = e.latlng.lng;
            inputLat.value = userLat; inputLng.value = userLng;
            if (marker) map.removeLayer(marker);
            marker = L.marker([userLat, userLng], { draggable: true }).addTo(map);
            marker.bindPopup('Votre position');
            marker.on('dragend', function () { var pos = marker.getLatLng(); userLat = pos.lat; userLng = pos.lng; inputLat.value = userLat; inputLng.value = userLng; detectZone(userLat, userLng); });
            detectZone(userLat, userLng);
        });
    }

    // Écouteur d'événement : clic sur le bouton de géolocalisation
    btnGeoloc.addEventListener('click', demarrerGeoloc);

    // Écouteur d'événement : clic sur le bouton de saisie manuelle
    btnManual.addEventListener('click', function () {
        manualFields.style.display = 'block'; mapContainer.style.display = 'none'; btnGeoloc.classList.remove('active');
        inputLat.value = ''; inputLng.value = ''; inputZoneAuto.value = '';
        // Mise à jour des frais si une zone est déjà sélectionnée
        if (inputZone.value) { var opt = inputZone.options[inputZone.selectedIndex]; var tarif = parseFloat(opt.dataset.tarif) || 0; manualFrais.textContent = 'Frais de livraison : ' + tarif.toLocaleString('fr-FR') + ' FCFA'; manualFrais.style.display = tarif > 0 ? 'block' : 'none'; updateTotal(tarif); }
        else { manualFrais.style.display = 'none'; updateTotal(0); }
    });

    // Écouteur de changement sur le sélecteur de zone (mode manuel)
    if (inputZone) {
        inputZone.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex]; var tarif = parseFloat(opt.dataset.tarif) || 0;
            if (opt && opt.value) { manualFrais.textContent = 'Frais de livraison : ' + tarif.toLocaleString('fr-FR') + ' FCFA'; manualFrais.style.display = 'block'; updateTotal(tarif); }
            else { manualFrais.style.display = 'none'; updateTotal(0); }
        });
    }

    // Écouteurs de changement sur les radios de mode de retrait
    modeRetraitRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            var sectionGeoloc = document.getElementById('section-geoloc');
            var geoGroup = document.getElementById('map-container');
            var manualGroup = document.getElementById('manual-fields');
            if (this.value === 'Retrait en boutique') {
                // Masquer toute la section géolocalisation si retrait en boutique
                if (sectionGeoloc) sectionGeoloc.style.display = 'none';
                updateTotal(0);
            } else {
                // Réafficher la section géolocalisation si livraison
                if (sectionGeoloc) sectionGeoloc.style.display = 'block';
                // Réafficher les groupes selon le dernier choix actif
                if (btnGeoloc.classList.contains('active')) {
                    if (geoGroup) { geoGroup.style.display = 'block'; if (map) setTimeout(function () { map.invalidateSize(); }, 200); }
                } else if (manualGroup && manualGroup.dataset.wasOpen) { manualGroup.style.display = 'block'; }
            }
        });
    });

    // Marquer les manual-fields comme ouverts quand on les affiche
    if (manualFields) {
        var origBtnManualClick = btnManual.onclick;
        btnManual.addEventListener('click', function () {
            manualFields.dataset.wasOpen = '1';
        });
    }

    // Validation du formulaire avant soumission
    document.getElementById('form-commande').addEventListener('submit', function (e) {
        var modeRetrait = document.querySelector('input[name="mode_retrait"]:checked');
        // Pas de vérification si retrait en boutique
        if (modeRetrait && modeRetrait.value === 'Retrait en boutique') return;
        var manualSelect = document.getElementById('input-zone');
        // Vérification : l'utilisateur doit avoir choisi géoloc OU saisie manuelle
        var geolocActif = btnGeoloc.classList.contains('active');
        var manuelActif = manualFields && manualFields.style.display !== 'none';
        if (!geolocActif && !manuelActif) {
            e.preventDefault();
            alert('Veuillez vous géolocaliser ou saisir votre adresse avant de continuer.');
            return;
        }
        // Vérification de la géolocalisation
        if (geolocActif) {
            if (!inputLat.value || !inputLng.value) { e.preventDefault(); alert('Veuillez attendre la fin de la géolocalisation.'); return; }
            if (manualSelect) manualSelect.disabled = true;
            var za = document.getElementById('input-zone-auto');
            // Ajout d'un champ caché pour la zone détectée automatiquement
            if (za && za.value) { var zInput = document.createElement('input'); zInput.type = 'hidden'; zInput.name = 'zone_id'; zInput.value = za.value; this.appendChild(zInput); }
        } else {
            // Mode saisie manuelle : vérifier qu'une zone est sélectionnée
            if (manualSelect && !manualSelect.value) { e.preventDefault(); alert('Veuillez sélectionner votre zone de livraison.'); return; }
        }
    });
})();
</script>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../includes/footer.php'; ?>
