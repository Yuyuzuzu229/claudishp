<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion des classes nécessaires
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Categorie.php';
require_once __DIR__ . '/../classes/FedaPay.php';

// Vérification que l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupération du token de paiement depuis l'URL
$token = $_GET['token'] ?? '';
// Redirection si le token est vide
if (empty($token)) {
    redirect(BASE_URL . '/index.php');
}

// Connexion à la base de données
$pdo = getPdo();
// Requête pour récupérer les informations du paiement, de la commande et de l'utilisateur
$stmt = $pdo->prepare("SELECT p.*, c.montant_total, c.id as commande_id, u.prenom, u.nom FROM paiement p JOIN commande c ON p.commande_id = c.id JOIN utilisateur u ON c.utilisateur_id = u.id WHERE p.token = ? AND c.utilisateur_id = ?");
$stmt->execute([$token, $_SESSION['user_id']]);
$paiement = $stmt->fetch();

// Si le paiement n'est pas trouvé, redirection avec message d'erreur
if (!$paiement) {
    $_SESSION['error'] = 'Transaction introuvable.';
    redirect(BASE_URL . '/index.php');
}

// Instancie FedaPay pour récupérer les infos de simulation
$fedapay = new FedaPay();
$otpSimulation = $fedapay->getOtpSimulation();
$etapeSimulation = $fedapay->getEtapeSimulation();
$modePaiement = $_SESSION['fedapay_mode'] ?? $paiement['mode'];
$telephonePaiement = $_SESSION['fedapay_telephone'] ?? $paiement['telephone_paiement'];

// Définit l'icône et la couleur selon l'opérateur Mobile Money
$estMTN = strpos($modePaiement, 'MTN') !== false;
$operateurIcone = $estMTN ? 'mtn' : 'moov';
$operateurCouleur = $estMTN ? '#ffcc00' : '#00aaff';
$operateurNom = $estMTN ? 'MTN Mobile Money' : 'Moov Money';

// Définition du titre de la page
$pageTitle = 'Paiement Mobile Money';

// Inclusion de l'en-tête HTML et de la barre de navigation
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<style>
  body { animation: none !important; }
  .panier-recap { position: static !important; }
  .mm-modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
  }
  .mm-modal {
    background: #fff; border-radius: 16px; padding: 32px 28px 24px;
    width: 360px; max-width: 92vw; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: mmFadeIn 0.3s ease;
  }
  @keyframes mmFadeIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
  .mm-spinner {
    width: 48px; height: 48px; border: 4px solid #e5e7eb;
    border-top-color: #000; border-radius: 50%;
    animation: mmSpin 0.8s linear infinite; margin: 0 auto 16px;
  }
  @keyframes mmSpin { to { transform: rotate(360deg); } }
  .mm-operator-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;
    margin-bottom: 16px;
  }
  .mm-success-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: #10b981; display: flex; align-items: center;
    justify-content: center; margin: 0 auto 16px;
  }
  .mm-otp-input {
    width: 100%; text-align: center; font-size: 32px; font-weight: 700;
    letter-spacing: 12px; padding: 12px; border: 2px solid #d1d5db;
    border-radius: 12px; outline: none; transition: border-color 0.2s;
  }
  .mm-otp-input:focus { border-color: #000; }
</style>

<div class="container" style="padding-top:32px;padding-bottom:48px;">
    <div style="max-width:480px;margin:0 auto;">

        <!-- Affichage des messages stockés en session -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Récapitulatif de la commande -->
        <div class="panier-recap" style="margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;">Récapitulatif de la commande</h3>
            <div class="recap-row">
                <span class="text-muted">Commande</span>
                <strong>#<?= $paiement['commande_id'] ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Client</span>
                <strong><?= securiser($paiement['prenom']) ?> <?= securiser($paiement['nom']) ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Montant</span>
                <strong class="recap-total" style="color:var(--color-primary);"><?= formatPrix($paiement['montant_total']) ?></strong>
            </div>
            <div class="recap-row">
                <span class="text-muted">Paiement</span>
                <strong><?= securiser($modePaiement) ?></strong>
            </div>
            <?php if (!empty($telephonePaiement)): ?>
            <div class="recap-row">
                <span class="text-muted">Téléphone</span>
                <strong><?= securiser($telephonePaiement) ?></strong>
            </div>
            <?php endif; ?>
            <div class="recap-row">
                <span class="text-muted">Statut</span>
                <span class="badge badge-warning">En attente</span>
            </div>
        </div>

        <!-- Interface de paiement Mobile Money -->
        <div class="panier-recap" style="margin-bottom:16px;">
            <div class="text-center" style="margin-bottom:20px;">
                <!-- Badge opérateur Mobile Money -->
                <div class="mm-operator-badge" style="background:<?= $operateurCouleur ?>20;color:<?= $operateurCouleur ?>;border:1px solid <?= $operateurCouleur ?>40;">
                    <i class="fas fa-mobile-alt"></i>
                    <?= $operateurNom ?>
                </div>

                <?php if ($etapeSimulation === 'termine'): ?>
                    <h3 style="font-size:18px;font-weight:700;margin-bottom:6px;">Paiement effectué</h3>
                    <p class="text-muted" style="font-size:13px;">
                        Votre paiement a été traité avec succès.
                    </p>
                <?php else: ?>
                    <h3 style="font-size:18px;font-weight:700;margin-bottom:6px;">Confirmation de paiement</h3>
                    <p class="text-muted" style="font-size:13px;">
                        Vous allez recevoir une demande de paiement sur votre téléphone
                        <strong><?= securiser($telephonePaiement) ?></strong> via <?= $operateurNom ?>.
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($etapeSimulation === 'termine'): ?>
                <!-- État final : paiement réussi -->
                <div class="text-center">
                    <div class="mm-success-icon">
                        <i class="fas fa-check" style="font-size:28px;color:#fff;"></i>
                    </div>
                    <p style="font-size:14px;color:var(--success);font-weight:600;margin-bottom:20px;">
                        Paiement de <?= formatPrix($paiement['montant_total']) ?> confirmé !
                    </p>
                    <a href="<?= BASE_URL ?>/user/detail_commande.php?id=<?= $paiement['commande_id'] ?>" class="btn btn-dark btn-block">
                        <i class="fas fa-eye"></i> Voir le détail de ma commande
                    </a>
                </div>
            <?php else: ?>
                <!-- Bouton de confirmation du paiement Mobile Money -->
                <form method="POST" action="<?= BASE_URL ?>/actions/confirmer_paiement.php" id="form-paiement">
                    <input type="hidden" name="token" value="<?= securiser($token) ?>">
                    <input type="hidden" name="commande_id" value="<?= $paiement['commande_id'] ?>">
                    <input type="hidden" name="paiement_id" value="<?= $paiement['id'] ?>">

                    <button type="submit" class="btn btn-dark btn-block btn-lg" style="padding:14px;font-size:16px;" id="btn-payer">
                        <i class="fas fa-lock"></i> Payer <?= formatPrix($paiement['montant_total']) ?> via <?= $operateurNom ?>
                    </button>
                </form>

                <!-- Badge de sécurité -->
                <div class="secure-badge" style="margin-top:14px;padding:10px 12px;">
                    <i class="fas fa-shield-alt" style="color:var(--success);font-size:18px;"></i>
                    <div>
                        <strong style="font-size:12px;">Paiement 100% sécurisé</strong>
                        <p class="text-muted" style="font-size:11px;margin:2px 0 0;">
                            Crypté et protégé par FedaPay
                        </p>
                    </div>
                </div>

                <!-- Lien d'annulation -->
                <div class="text-center" style="margin-top:12px;">
                    <a href="<?= BASE_URL ?>/actions/annuler_paiement.php?token=<?= securiser($token) ?>&commande_id=<?= $paiement['commande_id'] ?>" class="btn btn-outline-dark btn-sm" style="color:var(--gray-500);border-color:var(--gray-300);">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informations sur les opérateurs acceptés -->
        <div style="border:1px solid var(--gray-200);padding:16px;background:var(--gray-50);">
            <h3 style="font-size:13px;font-weight:700;margin-bottom:10px;">Moyens de paiement acceptés</h3>
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <span style="padding:6px 14px;background:#ffcc00;color:#000;border-radius:6px;font-size:12px;font-weight:600;">
                    <i class="fas fa-mobile-alt"></i> MTN MoMo
                </span>
                <span style="padding:6px 14px;background:#00aaff;color:#fff;border-radius:6px;font-size:12px;font-weight:600;">
                    <i class="fas fa-mobile-alt"></i> Moov Money
                </span>
                <span style="font-size:11px;color:var(--gray-400);margin-left:auto;">
                    <i class="fas fa-lock"></i> Transaction sécurisée
                </span>
            </div>
        </div>

    </div>
</div>

<?php if ($otpSimulation && $etapeSimulation !== 'termine'): ?>
<!-- Modal OTP pour la simulation Mobile Money -->
<div class="mm-modal-overlay" id="modal-otp" style="display:none;">
    <div class="mm-modal">
        <div class="mm-operator-badge" style="background:<?= $operateurCouleur ?>20;color:<?= $operateurCouleur ?>;border:1px solid <?= $operateurCouleur ?>40;">
            <i class="fas fa-mobile-alt"></i>
            <?= $operateurNom ?>
        </div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:4px;">Code de confirmation</h3>
        <p class="text-muted" style="font-size:13px;margin-bottom:20px;">
            Un code a été envoyé au <strong><?= securiser($telephonePaiement) ?></strong>
        </p>

        <form method="POST" action="<?= BASE_URL ?>/actions/confirmer_paiement.php" id="form-otp">
            <input type="hidden" name="token" value="<?= securiser($token) ?>">
            <input type="hidden" name="commande_id" value="<?= $paiement['commande_id'] ?>">
            <input type="hidden" name="paiement_id" value="<?= $paiement['id'] ?>">
            <input type="hidden" name="avec_otp" value="1">

            <input type="text" name="code_otp" class="mm-otp-input"
                   maxlength="4" placeholder="* * * *"
                   inputmode="numeric" pattern="[0-9]{4}"
                   autocomplete="one-time-code"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <button type="submit" class="btn btn-dark btn-block" style="margin-top:20px;padding:12px;">
                <i class="fas fa-check-circle"></i> Confirmer le paiement
            </button>
        </form>

        <p class="text-xs text-muted" style="margin-top:12px;">
            Code de test : <strong style="font-size:16px;letter-spacing:4px;"><?= $otpSimulation ?></strong>
        </p>
        <button type="button" class="btn btn-outline-dark btn-sm" style="margin-top:4px;font-size:11px;" onclick="document.getElementById('modal-otp').style.display='none'">
            <i class="fas fa-times"></i> Annuler
        </button>
    </div>
</div>

<script>
// Affiche la modale OTP après soumission du formulaire principal
document.getElementById('btn-payer').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('modal-otp').style.display = 'flex';
});
</script>
<?php endif; ?>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../includes/footer.php'; ?>
