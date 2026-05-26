<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Avis Clients';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'avis';

// Inclusion de la classe Avis
require_once __DIR__ . '/../classes/Avis.php';
// Instanciation de l'objet Avis
$avisObj = new Avis();

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action à effectuer
    $action = $_POST['action'] ?? '';
    // Récupération et conversion de l'identifiant de l'avis
    $id = intval($_POST['id'] ?? 0);
    // Si un identifiant valide est fourni
    if ($id) {
        // Selon l'action, approbation, refus ou suppression de l'avis
        if ($action === 'approuver') { $avisObj->updateStatut($id, 'Publié'); $_SESSION['success'] = 'Avis approuvé.'; }
        elseif ($action === 'refuser') { $avisObj->updateStatut($id, 'Refusé'); $_SESSION['success'] = 'Avis refusé.'; }
        elseif ($action === 'supprimer') { $avisObj->supprimer($id); $_SESSION['success'] = 'Avis supprimé.'; }
    }
    // Redirection vers la page de gestion des avis après le traitement
    redirect(BASE_URL . '/admin/avis.php');
}

// Récupération des filtres
$note = isset($_GET['note']) && $_GET['note'] !== '' ? intval($_GET['note']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
if ($note !== null && ($note < 1 || $note > 5)) $note = null;
if ($search === '') $search = null;

// Récupération de la liste filtrée des avis
$avis = $avisObj->getFiltered($note, $search);
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Finance &amp; Communication</div>
        <h1 class="dash-page-title">Avis clients</h1>
        <p class="dash-page-sub">Modérez les avis clients</p>
    </div>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Tous les avis (<?= count($avis) ?>)</span>
            <form method="GET" class="flex gap-2" style="display:flex;gap:8px;">
                <select name="note" class="sort-select" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="5"<?= $note === 5 ? ' selected' : '' ?>>★★★★★</option>
                    <option value="4"<?= $note === 4 ? ' selected' : '' ?>>★★★★</option>
                    <option value="3"<?= $note === 3 ? ' selected' : '' ?>>★★★</option>
                    <option value="2"<?= $note === 2 ? ' selected' : '' ?>>★★</option>
                    <option value="1"<?= $note === 1 ? ' selected' : '' ?>>★</option>
                </select>
                <input type="text" name="search" placeholder="Rechercher…" value="<?= securiser($search ?? '') ?>" class="form-input" style="padding:6px 12px;border:1px solid var(--gray-200);border-radius:6px;">
                <button type="submit" class="btn btn-sm btn-primary" style="padding:6px 14px;"><i class="fas fa-search"></i></button>
                <?php if ($note !== null || $search !== null): ?>
                    <a href="<?= BASE_URL ?>/admin/avis.php" class="btn btn-sm btn-secondary" style="padding:6px 14px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($avis)): ?>
            <!-- Si aucun avis n'est trouvé, affichage d'un message par défaut -->
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun avis.</td></tr>
            <?php else: foreach ($avis as $av): ?>
            <!-- Boucle d'affichage de chaque avis dans une ligne du tableau -->
            <tr>
                <td class="text-xs text-muted">#<?= $av['id'] ?></td>
                <td class="text-sm"><?= securiser(($av['prenom']??'').' '.($av['nom']??'')) ?></td>
                <td class="text-sm font-semibold"><?= securiser($av['produit_nom']??'—') ?></td>
                <td><span style="color:var(--warning);"><?= str_repeat('★',$av['note']??5) ?></span></td>
                <td class="text-xs text-muted"><?= securiser(substr($av['commentaire']??'',0,50)) ?>...</td>
                <td><?= getStatutBadge($av['statut']??'Publié') ?></td>
                <td class="text-xs text-muted"><?= date('d/m/Y',strtotime($av['date_creation']??'now')) ?></td>
                <td><div class="flex gap-1">
    <!-- Formulaire d'approbation de l'avis -->
    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="approuver"><input type="hidden" name="id" value="<?= $av['id'] ?>"><button type="submit" class="action-btn" title="Approuver"><i class="fas fa-check"></i></button></form>
    <!-- Formulaire de refus de l'avis -->
    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="refuser"><input type="hidden" name="id" value="<?= $av['id'] ?>"><button type="submit" class="action-btn" title="Refuser"><i class="fas fa-times"></i></button></form>
    <!-- Formulaire de suppression de l'avis avec confirmation -->
    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet avis ?');"><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $av['id'] ?>"><button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button></form>
</div></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
