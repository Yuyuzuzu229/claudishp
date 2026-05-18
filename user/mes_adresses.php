<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Adresse.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Categorie.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/pages/connexion.php');
}

$pageTitle = 'Mes adresses';
$adresseObj = new Adresse();
$adresses = $adresseObj->getByUtilisateur($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <div class="list-group list-group-flush rounded-4 shadow-sm mb-4">
                <a href="<?= BASE_URL ?>/user/profil.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Mon profil</a>
                <a href="<?= BASE_URL ?>/user/mes_commandes.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Mes commandes</a>
                <a href="<?= BASE_URL ?>/user/mes_avis.php" class="list-group-item list-group-item-action"><i class="bi bi-star me-2"></i>Mes avis</a>
                <a href="<?= BASE_URL ?>/user/historique_paiement.php" class="list-group-item list-group-item-action"><i class="bi bi-credit-card me-2"></i>Paiements</a>

                <a href="<?= BASE_URL ?>/user/mes_adresses.php" class="list-group-item list-group-item-action active"><i class="bi bi-geo-alt me-2"></i>Mes adresses</a>
                <a href="<?= BASE_URL ?>/user/notifications.php" class="list-group-item list-group-item-action"><i class="bi bi-bell me-2"></i>Notifications</a>
                <a href="<?= BASE_URL ?>/actions/deconnexion.php" class="list-group-item list-group-item-action text-danger"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white fw-bold fs-5 rounded-top-4 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-geo-alt"></i> Mes adresses</span>
                    <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="bi bi-plus"></i> Ajouter
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($adresses)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-pin-map display-4 text-muted"></i>
                        <p class="text-muted mt-2">Aucune adresse enregistrée.</p>
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($adresses as $addr): ?>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="fw-bold"><i class="bi bi-pin-fill text-primary"></i> <?= securiser($addr['quartier']) ?></h6>
                                    <p class="mb-1"><strong>Ville:</strong> <?= securiser($addr['ville']) ?></p>
                                    <?php if ($addr['point_repere']): ?>
                                    <p class="mb-0"><strong>Point de repère:</strong> <?= securiser($addr['point_repere']) ?></p>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <a href="<?= BASE_URL ?>/actions/supprimer_adresse.php?id=<?= $addr['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Supprimer cette adresse ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="fw-bold"><i class="bi bi-plus-circle"></i> Nouvelle adresse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_adresse.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ville</label>
                        <input type="text" name="ville" class="form-control" required placeholder="Ex: Dakar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quartier</label>
                        <input type="text" name="quartier" class="form-control" required placeholder="Ex: Mermoz">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Point de repère</label>
                        <input type="text" name="point_repere" class="form-control" placeholder="Ex: Près de l'école">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
