<?php
require_once __DIR__ . '/../config/config.php';
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Localisation boutique';
$adminPage = 'boutique';
$success = $error = '';

// Sauvegarde du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat  = (float)($_POST['latitude']  ?? SHOP_LAT);
    $lng  = (float)($_POST['longitude'] ?? SHOP_LNG);
    $addr = trim($_POST['adresse'] ?? SHOP_ADDRESS);
    if (saveShopPosition($lat, $lng, $addr)) {
        $success = 'Position de la boutique mise à jour avec succès.';
    } else {
        $error = 'Erreur lors de l\'enregistrement. Vérifiez que la table configuration_boutique existe.';
    }
}

// Position actuelle
$shopLat = getShopLat();
$shopLng = getShopLng();
$shopAddr = getShopAddress();

require_once __DIR__ . '/../includes/header.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Jost:wght@300;400;500;600&display=swap');
.boutique-settings {
  --dark: #111111; --border: #e8e8e4; --bg: #f5f4f0; --success: #27ae60; --danger: #e94560;
}
.boutique-settings .card-settings {
  background:#fff; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.07); overflow:hidden; margin-bottom:20px;
}
.boutique-settings .card-settings__header {
  padding:18px 24px 14px; border-bottom:1px solid var(--border);
}
.boutique-settings .card-settings__title { font-size:.95rem; font-weight:600; color:var(--dark); }
.boutique-settings .card-settings__body { padding:20px 24px; }
.boutique-settings .form-group { margin-bottom:16px; }
.boutique-settings .form-group label { display:block; font-size:.8rem; font-weight:600; color:#555; margin-bottom:4px; }
.boutique-settings .form-control {
  width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:6px; font-size:.85rem;
  background:#fff; transition:border-color .15s; box-sizing:border-box;
}
.boutique-settings .form-control:focus { outline:none; border-color:var(--dark); }
.boutique-settings .btn {
  display:inline-flex; align-items:center; gap:8px; padding:10px 24px; border:none; border-radius:6px;
  font-size:.85rem; font-weight:600; cursor:pointer; transition:opacity .15s; text-decoration:none;
}
.boutique-settings .btn:hover { opacity:.85; }
.boutique-settings .btn-dark { background:var(--dark); color:#fff; }
.boutique-settings .btn-outline-dark { background:transparent; border:1px solid var(--dark); color:var(--dark); }
.boutique-settings .btn-success { background:var(--success); color:#fff; }
.boutique-settings .alert { padding:12px 18px; border-radius:6px; font-size:.85rem; margin-bottom:16px; }
.boutique-settings .alert-success { background:#e8f5e9; color:#2e7d32; }
.boutique-settings .alert-danger { background:#fce4ec; color:#c62828; }
.boutique-settings .flex { display:flex; }
.boutique-settings .gap-3 { gap:12px; }
#map-boutique { height:380px; border-radius:8px; border:1px solid var(--border); }
.current-coords { font-size:.8rem; color:#666; margin:8px 0 16px; }
.current-coords strong { color:var(--dark); }
</style>

<div class="boutique-settings">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Configuration</div>
        <h1 class="dash-page-title">Localisation de la boutique</h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= securiser($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= securiser($error) ?></div><?php endif; ?>

    <form method="POST" id="form-boutique">
    <div class="card-settings">
        <div class="card-settings__header">
            <span class="card-settings__title">Position GPS & Adresse</span>
        </div>
        <div class="card-settings__body">
            <p style="font-size:.82rem;color:#666;margin:0 0 12px 0;">
                Déplacez le marqueur sur la carte ou utilisez le bouton "Ma position" pour définir
                l'emplacement de votre boutique. Les coordonnées GPS et l'adresse seront sauvegardées
                en base de données — plus besoin de modifier le code.
            </p>

            <div id="map-boutique"></div>

            <div class="current-coords">
                Position actuelle :
                <strong id="display-lat"><?= $shopLat ?></strong> /
                <strong id="display-lng"><?= $shopLng ?></strong>
                &nbsp;&mdash;&nbsp;
                <span id="display-addr"><?= securiser($shopAddr) ?></span>
            </div>

            <div class="flex gap-3" style="margin-bottom:16px;">
                <button type="button" id="btn-geoloc" class="btn btn-outline-dark">
                    <i class="fas fa-location-dot"></i> Ma position
                </button>
            </div>

            <div class="form-group">
                <label>Adresse de la boutique</label>
                <input type="text" name="adresse" id="input-adresse" class="form-control"
                       value="<?= securiser($shopAddr) ?>" required>
            </div>

            <input type="hidden" name="latitude" id="input-latitude" value="<?= $shopLat ?>">
            <input type="hidden" name="longitude" id="input-longitude" value="<?= $shopLng ?>">

            <button type="submit" class="btn btn-dark">
                <i class="fas fa-save"></i> Enregistrer la position
            </button>
        </div>
    </div>
    </form>

    <div class="card-settings">
        <div class="card-settings__header">
            <span class="card-settings__title">Valeur par défaut (code)</span>
        </div>
        <div class="card-settings__body">
            <p style="font-size:.82rem;color:#666;margin:0;">
                Les constantes dans <code>config/config.php</code> servent de valeur de secours.
                Tant que vous enregistrez une position via ce formulaire, celle-ci est prioritaire.
                Si la base de données venait à être réinitialisée, la boutique reviendrait à :
                <strong>6.3650, 2.4330 — Wologede, Mairie, Cotonou</strong>.
            </p>
        </div>
    </div>

</div>
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop Admin</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop – Tous droits réservés</span>
</div>
</div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var map, marker;
    var lat = <?= $shopLat ?>;
    var lng = <?= $shopLng ?>;

    var inputLat  = document.getElementById('input-latitude');
    var inputLng  = document.getElementById('input-longitude');
    var inputAddr = document.getElementById('input-adresse');
    var displayLat  = document.getElementById('display-lat');
    var displayLng  = document.getElementById('display-lng');
    var displayAddr = document.getElementById('display-addr');

    function initMap(lat, lng) {
        if (!map) {
            map = L.map('map-boutique').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap'
            }).addTo(map);
        } else {
            map.setView([lat, lng], 15);
        }

        if (marker) map.removeLayer(marker);

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.bindPopup('Boutique ClaudiShop');

        marker.on('dragend', function () {
            var pos = marker.getLatLng();
            updatePosition(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker([e.latlng.lat, e.latlng.lng], { draggable: true }).addTo(map);
            marker.bindPopup('Boutique ClaudiShop');
            marker.on('dragend', function () {
                var pos = marker.getLatLng();
                updatePosition(pos.lat, pos.lng);
            });
            updatePosition(e.latlng.lat, e.latlng.lng);
        });

        updatePosition(lat, lng);
    }

    function updatePosition(newLat, newLng) {
        inputLat.value = newLat.toFixed(7);
        inputLng.value = newLng.toFixed(7);
        displayLat.textContent = newLat.toFixed(7);
        displayLng.textContent = newLng.toFixed(7);

        // Reverse geocoding
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + newLat + '&lon=' + newLng + '&accept-language=fr', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                if (data && data.display_name) {
                    inputAddr.value = data.display_name;
                    displayAddr.textContent = data.display_name;
                }
            }
        };
        xhr.send();
    }

    document.getElementById('btn-geoloc').addEventListener('click', function () {
        if (!navigator.geolocation) { alert('La géolocalisation n\'est pas supportée par votre navigateur.'); return; }
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation...';
        navigator.geolocation.getCurrentPosition(function (pos) {
            initMap(pos.coords.latitude, pos.coords.longitude);
            document.getElementById('btn-geoloc').disabled = false;
            document.getElementById('btn-geoloc').innerHTML = '<i class="fas fa-location-dot"></i> Ma position';
        }, function () {
            alert('Impossible de vous localiser. Déplacez le marqueur manuellement sur la carte.');
            document.getElementById('btn-geoloc').disabled = false;
            document.getElementById('btn-geoloc').innerHTML = '<i class="fas fa-location-dot"></i> Ma position';
        }, { enableHighAccuracy: true, timeout: 10000 });
    });

    initMap(lat, lng);
})();
</script>
</body></html>
