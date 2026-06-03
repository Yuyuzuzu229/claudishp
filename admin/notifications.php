<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Notifications';
$notifObj2 = new Notification();

// Traitement des actions de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'supprimer' && !empty($_POST['id'])) {
        $notifObj2->supprimer(intval($_POST['id']));
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Notification supprimée.'];
    } elseif ($action === 'supprimer_plusieurs' && !empty($_POST['ids'])) {
        $ids = array_map('intval', (array)$_POST['ids']);
        $notifObj2->supprimerPlusieurs($ids);
        $_SESSION['flash'] = ['type' => 'success', 'message' => count($ids) . ' notification(s) supprimée(s).'];
    }
    redirect(BASE_URL . '/admin/notifications.php');
}

$notifObj2->marquerToutesLues($_SESSION['user_id']);
$notifs = $notifObj2->getAll();

require_once __DIR__ . '/../includes/header.php';
$adminPage = 'notifications';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Communication</div>
        <h1 class="dash-page-title">Notifications</h1>
        <p class="dash-page-sub">Toutes les notifications système</p>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= securiser($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Notifications récentes (<?= count($notifs) ?>)</span>
            <?php if (!empty($notifs)): ?>
            <div style="display:flex;align-items:center;gap:10px;">
                <label style="font-size:13px;display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" id="select-all" onchange="toggleAll(this)">
                    Tout sélectionner
                </label>
                <button class="btn btn-outline-dark btn-sm" id="btn-delete-selected" onclick="deleteSelected()" style="display:none;">
                    <i class="fas fa-trash"></i> Supprimer la sélection
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div>
        <?php if (empty($notifs)): ?>
        <div style="padding:32px;text-align:center;color:var(--gray-400);">Aucune notification.</div>
        <?php else: foreach (array_slice($notifs,0,30) as $n): ?>
        <div class="notif-item" style="gap:10px;">
            <input type="checkbox" class="notif-check" value="<?= $n['id'] ?>" style="flex-shrink:0;" onchange="updateDeleteBtn()">
            <div class="notif-icon"><i class="fas fa-bell"></i></div>
            <div class="notif-content" style="flex:1;">
                <div class="notif-title"><?= securiser($n['titre'] ?? 'Notification') ?></div>
                <div class="notif-sub"><?= securiser(mb_substr($n['message'],0,150)) ?></div>
                <div class="notif-time">
                    <?php $ts = strtotime($n['date_envoi'] ?? ''); if ($ts && $ts > strtotime('2000-01-01')): ?><?= date('d/m/Y H:i', $ts) ?><?php else: ?><span style="color:var(--gray-400)">Date inconnue</span><?php endif; ?>
                    &middot; <span class="badge badge-<?= $n['canal'] === 'WhatsApp' ? 'success' : ($n['canal'] === 'Email' ? 'info' : 'secondary') ?>"><?= $n['canal'] ?></span>
                    &middot; <?= securiser(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? '')) ?>
                </div>
            </div>
            <form method="POST" onsubmit="return confirm('Supprimer cette notification ?')" style="flex-shrink:0;">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                <button type="submit" class="action-btn danger" title="Supprimer" style="padding:6px 8px;"><i class="fas fa-times"></i></button>
            </form>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Formulaire caché pour la suppression groupée -->
    <form method="POST" id="form-bulk-delete" style="display:none;">
        <input type="hidden" name="action" value="supprimer_plusieurs">
        <div id="bulk-ids-container"></div>
    </form>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>

<script>
function toggleAll(master) {
    document.querySelectorAll('.notif-check').forEach(function(cb) {
        cb.checked = master.checked;
    });
    updateDeleteBtn();
}

function updateDeleteBtn() {
    var checked = document.querySelectorAll('.notif-check:checked').length;
    var btn = document.getElementById('btn-delete-selected');
    if (btn) btn.style.display = checked > 0 ? 'inline-flex' : 'none';
}

function deleteSelected() {
    var checked = document.querySelectorAll('.notif-check:checked');
    if (checked.length === 0) return;
    if (!confirm('Supprimer ces ' + checked.length + ' notification(s) ?')) return;

    var container = document.getElementById('bulk-ids-container');
    container.innerHTML = '';
    checked.forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });
    document.getElementById('form-bulk-delete').submit();
}
</script>
</body></html>
