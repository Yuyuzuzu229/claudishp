<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/ZoneLivraison.php';
require_once __DIR__ . '/../classes/Notification.php';

$pageTitle = 'Finaliser la commande';
$pageStyles = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'];

$panierObj = new Panier();
$isGuest = !isLoggedIn();

if ($isGuest) {
    $lignes = $panierObj->guestGetLignes();
    $total = $panierObj->guestCalculerTotal();
    $nbArticles = $panierObj->guestGetNombreArticles();
} else {
    $panierId = $panierObj->getPanierActif($_SESSION['user_id']);
    $lignes = $panierObj->getLignes($panierId);
    $total = $panierObj->calculerTotal($panierId);
    $nbArticles = $panierObj->getNombreArticles($panierId);
}

if (empty($lignes)) { redirect(BASE_URL . '/pages/panier.php'); }

$zoneObj = new ZoneLivraison();
$zones = $zoneObj->getActives();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="padding-top:28px;padding-bottom:60px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= BASE_URL ?>/pages/panier.php">Panier</a>
        <span class="breadcrumb-sep">/</span>
        <span>Finaliser la commande</span>
    </div>
    <h1 style="font-size:24px;font-weight:800;margin-bottom:28px;">Finaliser la commande</h1>

    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/actions/commander.php" id="form-commande">
    <div class="checkout-layout">
        <div>
            <!-- GEOLOCALISATION -->
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-card-header"><span class="table-card-title">Où livrer ?</span></div>
                <div style="padding:20px;">
                    <p class="text-sm text-muted" style="margin-bottom:12px;">Utilisez ma position pour déterminer votre zone de livraison ou saisissez votre adresse manuellement.</p>
                    <button type="button" id="btn-geoloc" class="btn btn-dark" style="margin-bottom:12px;">
                        <i class="fas fa-location-dot"></i> Me géolocaliser
                    </button>
                    <button type="button" id="btn-manual" class="btn btn-outline-dark">
                        <i class="fas fa-pen"></i> Saisir mon adresse
                    </button>

                    <div id="map-container" style="display:none;margin-top:12px;border:1px solid var(--gray-200);">
                        <div id="map" style="height:280px;"></div>
                        <div id="geoloc-info" style="padding:12px;background:var(--gray-50);font-size:13px;"></div>
                    </div>

                    <div id="manual-fields" style="display:none;margin-top:14px;">
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
                            <select name="zone_id" id="input-zone" class="form-control">
                                <option value="">Sélectionnez votre zone</option>
                                <?php foreach ($zones as $z): ?>
                                <option value="<?= $z['id'] ?>" data-tarif="<?= $z['tarif'] ?>">
                                    <?= securiser($z['nom']) ?> — <?= number_format($z['tarif'], 0, ',', ' ') ?> FCFA
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="manual-frais" class="text-sm font-semibold" style="margin-top:6px;color:var(--success);display:none;"></div>
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="input-latitude" value="">
                    <input type="hidden" name="longitude" id="input-longitude" value="">
                    <input type="hidden" name="zone_id_auto" id="input-zone-auto" value="">
                </div>
            </div>

            <!-- INFOS CLIENT -->
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-card-header"><span class="table-card-title">Informations personnelles</span></div>
                <div style="padding:24px;">
                    <div class="form-group">
                        <label>Nom complet <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="nom_complet" class="form-control" value="<?= securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?>" required>
                    </div>
                    <?php if ($isGuest): ?>
                    <div class="form-group">
                        <label>Email <span style="color:var(--danger);">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= securiser($_SESSION['guest_email'] ?? '') ?>" required placeholder="votre@email.com">
                    </div>
                    <?php endif; ?>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Téléphone <span style="color:var(--danger);">*</span></label>
                        <div class="flex gap-2">
                            <select class="form-control" style="width:90px;flex-shrink:0;"><option>+229</option><option>+228</option><option>+225</option></select>
                            <input type="tel" name="telephone" class="form-control" placeholder="90 12 34 56" value="<?= securiser($_SESSION['guest_telephone'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODE RETRAIT -->
            <div class="table-card" style="margin-bottom:20px;">
                <div class="table-card-header"><span class="table-card-title">Mode de retrait</span></div>
                <div style="padding:20px;display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--gray-200);cursor:pointer;">
                        <input type="radio" name="mode_retrait" value="Livraison" checked style="accent-color:var(--dark);">
                        <div><div class="font-semibold text-sm">Livraison à domicile</div><div class="text-xs text-muted">Livraison rapide partout au Bénin</div></div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--gray-200);cursor:pointer;">
                        <input type="radio" name="mode_retrait" value="Retrait en boutique" style="accent-color:var(--dark);">
                        <div><div class="font-semibold text-sm">Retrait en boutique</div><div class="text-xs text-muted">Wologede, Mairie – disponible sous 24h</div></div>
                        <span class="text-sm font-bold" style="margin-left:auto;">Gratuit</span>
                    </label>
                </div>
            </div>

            <!-- MODE PAIEMENT -->
            <div class="table-card">
                <div class="table-card-header"><span class="table-card-title">Mode de paiement</span></div>
                <div style="padding:20px;display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--gray-200);cursor:pointer;">
                        <input type="radio" name="mode_paiement" value="MTN MoMo" checked style="accent-color:var(--dark);">
                        <div><div class="font-semibold text-sm">MTN Mobile Money</div><div class="text-xs text-muted">Paiement sécurisé via MTN MoMo</div></div>
                        <i class="fas fa-mobile-alt" style="margin-left:auto;font-size:20px;color:var(--warning);"></i>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--gray-200);cursor:pointer;">
                        <input type="radio" name="mode_paiement" value="Moov Money" style="accent-color:var(--dark);">
                        <div><div class="font-semibold text-sm">Moov Money</div><div class="text-xs text-muted">Paiement sécurisé via Moov Money</div></div>
                        <i class="fas fa-mobile-alt" style="margin-left:auto;font-size:20px;color:var(--info);"></i>
                    </label>
                </div>
            </div>
        </div>

        <!-- DROITE : récap -->
        <div>
            <div class="panier-recap">
                <h3>Récapitulatif de commande</h3>
                <?php foreach ($lignes as $ligne): ?>
                <div class="flex justify-between items-center" style="padding:8px 0;border-bottom:1px solid var(--gray-100);">
                    <div class="flex gap-2 items-center">
                        <div class="panier-table-img" style="width:40px;height:40px;font-size:14px;"><i class="fas fa-tshirt"></i></div>
                        <div>
                            <div class="text-sm font-semibold"><?= securiser($ligne['nom']) ?></div>
                            <div class="text-xs text-muted">x<?= $ligne['quantite'] ?></div>
                        </div>
                    </div>
                    <div>
                        <strong class="text-sm"><?= formatPrix($ligne['prix_unitaire'] * $ligne['quantite']) ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="recap-row"><span class="text-muted">Sous-total</span><strong><?= formatPrix($total) ?></strong></div>
                <div class="recap-row" id="recap-livraison" style="display:none;">
                    <span class="text-muted">Livraison</span>
                    <strong id="recap-frais">0 FCFA</strong>
                </div>
                <hr>
                <div class="recap-row"><strong style="font-size:15px;">Total</strong><strong class="recap-total" style="font-size:20px;" id="recap-total"><?= formatPrix($total) ?></strong></div>
                <div class="secure-badge">
                    <i class="fas fa-shield-alt"></i>
                    <div><div class="font-semibold text-sm">Paiement 100% sécurisé</div><div class="text-xs text-muted">Vos transactions sont protégées.</div></div>
                </div>
                <button type="submit" class="btn btn-dark btn-block btn-lg">Confirmer la commande</button>
                <p class="text-xs text-muted text-center" style="margin-top:10px;"><i class="fas fa-lock" style="margin-right:4px;"></i>Paiement sécurisé</p>
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var SHOP_LAT = <?= SHOP_LAT ?>;
    var SHOP_LNG = <?= SHOP_LNG ?>;
    var map = null;
    var marker = null;
    var shopMarker = null;
    var userLat = null;
    var userLng = null;

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

    function updateTotal(frais) {
        var f = parseFloat(frais) || 0;
        if (f > 0) { recapLivraison.style.display = 'flex'; recapFrais.textContent = f.toLocaleString('fr-FR') + ' FCFA'; }
        else { recapLivraison.style.display = 'none'; }
        recapTotal.textContent = (totalBase + f).toLocaleString('fr-FR') + ' FCFA';
    }

    function initMap(lat, lng, zoneName, tarif) {
        if (!map) {
            map = L.map(mapEl).setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        } else { map.setView([lat, lng], 14); }
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.bindPopup('Votre position');
        if (!shopMarker) {
            shopMarker = L.marker([SHOP_LAT, SHOP_LNG], { icon: L.divIcon({ className: '', html: '<i class="fas fa-store" style="font-size:22px;color:var(--dark);"></i>', iconSize: [30, 30], iconAnchor: [15, 15] }) }).addTo(map);
            shopMarker.bindPopup('Boutique ClaudiShop');
        }
        marker.on('dragend', function () { var pos = marker.getLatLng(); userLat = pos.lat; userLng = pos.lng; inputLat.value = userLat; inputLng.value = userLng; detectZone(userLat, userLng); });
        mapContainer.style.display = 'block';
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    function reverseGeocode(lat, lng, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&accept-language=fr', true);
        xhr.onload = function () { if (xhr.status === 200) { var data = JSON.parse(xhr.responseText); callback(data.display_name || null); } else { callback(null); } };
        xhr.onerror = function () { callback(null); };
        xhr.send();
    }

    function detectZone(lat, lng) {
        var found = null, minDist = Infinity;
        var zones = <?= json_encode(array_map(function($z) { return ['id' => $z['id'], 'nom' => $z['nom'], 'tarif' => (float)$z['tarif'], 'lat' => (float)($z['latitude'] ?? 0), 'lng' => (float)($z['longitude'] ?? 0)]; }, $zones)) ?>;
        zones.forEach(function (z) { if (!z.lat || !z.lng) return; var d = Math.sqrt(Math.pow(lat - z.lat, 2) + Math.pow(lng - z.lng, 2)); if (d < minDist) { minDist = d; found = z; } });
        reverseGeocode(lat, lng, function (adresse) {
            var posLabel = adresse ? '<strong>Position :</strong> ' + adresse + '<br>' : '';
            if (found) {
                inputZoneAuto.value = found.id;
                geolocInfo.innerHTML = posLabel + '<span class="text-muted">Frais de livraison : <strong>' + found.tarif.toLocaleString('fr-FR') + ' FCFA</strong></span>';
                updateTotal(found.tarif);
            } else {
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

    function demarrerGeoloc() {
        if (!navigator.geolocation) { ouvrirCarteManuelle(); return; }
        btnGeoloc.disabled = true; btnGeoloc.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation...';
        navigator.geolocation.getCurrentPosition(function (pos) {
            userLat = pos.coords.latitude; userLng = pos.coords.longitude;
            inputLat.value = userLat; inputLng.value = userLng;
            manualFields.style.display = 'none'; initMap(userLat, userLng); detectZone(userLat, userLng);
            btnGeoloc.disabled = false; btnGeoloc.innerHTML = '<i class="fas fa-location-dot"></i> Me géolocaliser'; btnGeoloc.classList.add('active');
        }, function (err) {
            btnGeoloc.disabled = false; btnGeoloc.innerHTML = '<i class="fas fa-location-dot"></i> Me géolocaliser';
            if (err.code === 1) ouvrirCarteManuelle(); else { alert('Erreur de localisation.'); ouvrirCarteManuelle(); }
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    function ouvrirCarteManuelle() {
        manualFields.style.display = 'none'; btnGeoloc.classList.add('active');
        if (!map) initMap(SHOP_LAT, SHOP_LNG); else { mapContainer.style.display = 'block'; setTimeout(function () { map.invalidateSize(); }, 200); }
        geolocInfo.innerHTML = '<span style="color:var(--warning);font-weight:600;">👆 Cliquez sur la carte pour placer votre position</span><br><span class="text-muted">Vous pouvez aussi faire glisser le marqueur.</span>';
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

    btnGeoloc.addEventListener('click', demarrerGeoloc);

    btnManual.addEventListener('click', function () {
        manualFields.style.display = 'block'; mapContainer.style.display = 'none'; btnGeoloc.classList.remove('active');
        inputLat.value = ''; inputLng.value = ''; inputZoneAuto.value = '';
        if (inputZone.value) { var opt = inputZone.options[inputZone.selectedIndex]; var tarif = parseFloat(opt.dataset.tarif) || 0; manualFrais.textContent = 'Frais de livraison : ' + tarif.toLocaleString('fr-FR') + ' FCFA'; manualFrais.style.display = tarif > 0 ? 'block' : 'none'; updateTotal(tarif); }
        else { manualFrais.style.display = 'none'; updateTotal(0); }
    });

    if (inputZone) {
        inputZone.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex]; var tarif = parseFloat(opt.dataset.tarif) || 0;
            if (opt && opt.value) { manualFrais.textContent = 'Frais de livraison : ' + tarif.toLocaleString('fr-FR') + ' FCFA'; manualFrais.style.display = 'block'; updateTotal(tarif); }
            else { manualFrais.style.display = 'none'; updateTotal(0); }
        });
    }

    modeRetraitRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            var geoGroup = document.getElementById('map-container');
            var manualGroup = document.getElementById('manual-fields');
            if (this.value === 'Retrait en boutique') {
                if (geoGroup) geoGroup.style.display = 'none';
                if (manualGroup) manualGroup.style.display = 'none';
                updateTotal(0);
            } else {
                if (btnGeoloc.classList.contains('active')) { if (geoGroup) geoGroup.style.display = 'block'; }
                else { if (manualGroup) manualGroup.style.display = 'block'; }
            }
        });
    });

    document.getElementById('form-commande').addEventListener('submit', function (e) {
        var modeRetrait = document.querySelector('input[name="mode_retrait"]:checked');
        if (modeRetrait && modeRetrait.value === 'Retrait en boutique') return;
        var manualSelect = document.getElementById('input-zone');
        if (btnGeoloc.classList.contains('active')) {
            if (!inputLat.value || !inputLng.value) { e.preventDefault(); alert('Veuillez attendre la fin de la géolocalisation.'); return; }
            if (manualSelect) manualSelect.disabled = true;
            var za = document.getElementById('input-zone-auto');
            if (za && za.value) { var zInput = document.createElement('input'); zInput.type = 'hidden'; zInput.name = 'zone_id'; zInput.value = za.value; this.appendChild(zInput); }
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
