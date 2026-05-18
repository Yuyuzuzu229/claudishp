<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$commandeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$commandeId) { redirect(BASE_URL . '/user/mes_commandes.php'); }

$commandeObj = new Commande();
$commande = $commandeObj->getById($commandeId);

if (!$commande || $commande['utilisateur_id'] != $_SESSION['user_id']) {
    redirect(BASE_URL . '/user/mes_commandes.php');
}

$livraisonObj = new Livraison();
$suivi = $livraisonObj->getByCommande($commandeId);
$lignes = $commandeObj->getLignes($commandeId);

$pdo = getPdo();
$stmtPmt = $pdo->prepare("SELECT mode, statut FROM paiement WHERE commande_id = ? ORDER BY id DESC LIMIT 1");
$stmtPmt->execute([$commandeId]);
$paiement = $stmtPmt->fetch();
$modePaiement = $paiement ? $paiement['mode'] : ($commande['mode_paiement'] ?? '');

$pageTitle = 'Détail commande #' . str_pad($commandeId, 6, '0', STR_PAD_LEFT);

// Détecter le mode invité (première visite après achat sans mot de passe)
$showGuestBanner = isset($_SESSION['guest_converted']) && $_SESSION['guest_converted'] && empty($_SESSION['guest_banner_shown']);
if ($showGuestBanner) {
    $_SESSION['guest_banner_shown'] = true;
}

require_once __DIR__ . '/../includes/header.php';
$activePage = 'commandes';

if ($showGuestBanner): ?>
<!-- ── LAYOUT INVITÉ (sans sidebar) ── -->
<div class="container" style="padding-top:28px;padding-bottom:60px;max-width:800px;margin:0 auto;">
    <div id="banner-guest" style="background:linear-gradient(135deg,#F0FDF4,#ECFDF5);border:1px solid #86EFAC;border-radius:10px;padding:32px;margin-bottom:24px;">
        <div style="text-align:center;margin-bottom:24px;">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--success);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-gift" style="color:white;font-size:28px;"></i>
            </div>
            <h2 style="font-size:22px;font-weight:800;color:#166534;margin-bottom:8px;">Félicitations pour votre achat !</h2>
            <p style="font-size:14px;color:#374151;line-height:1.7;">
                Un compte client a été créé avec votre adresse email.<br>
                <strong>Définissez un mot de passe</strong> pour sécuriser votre accès et suivre vos commandes.
            </p>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <a id="btn-definir-mdp" href="<?= BASE_URL ?>/user/profil.php?invite=1" class="btn btn-dark btn-block btn-lg">
                <i class="fas fa-lock"></i> Définir mon mot de passe
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-block" style="border:1px solid var(--gray-200);padding:14px;text-align:center;border-radius:4px;text-decoration:none;color:var(--dark);font-weight:600;font-size:14px;">
                <i class="fas fa-store"></i> Continuer mes achats
            </a>
        </div>
    </div>

    <?php $livraisonObj->assignerAutomatique(); ?>

    <!-- ── DÉTAILS DE LA COMMANDE (articles, livreur, suivi) ── -->
    <div class="dash-two-col" style="align-items:start;">
        <!-- ARTICLES -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Articles commandés</span><span id="commande-statut-badge"><?= getStatutBadge($commande['statut']) ?></span></div>
            <table>
                <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Total</th></tr></thead>
                <tbody>
                <?php if (empty($lignes)): ?>
                <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--gray-400);">Aucun article.</td></tr>
                <?php else: foreach ($lignes as $l): ?>
                <tr>
                    <td><div class="flex items-center gap-2"><div class="admin-thumb"><i class="fas fa-tshirt"></i></div><span class="text-sm font-semibold"><?= securiser($l['nom'] ?? 'Produit') ?></span></div></td>
                    <td class="text-sm"><?= formatPrix($l['prix_unitaire']) ?></td>
                    <td class="text-sm"><?= $l['quantite'] ?></td>
                    <td class="text-sm font-semibold"><?= formatPrix($l['prix_unitaire'] * $l['quantite']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div class="flex justify-between items-center" style="padding:14px 16px;border-top:1px solid var(--gray-100);">
                <span class="text-muted text-sm">Total</span>
                <strong style="font-size:18px;"><?= formatPrix($commande['montant_total']) ?></strong>
            </div>
        </div>

        <!-- RECAP INFOS -->
        <div id="recap-infos" style="display:flex;flex-direction:column;gap:16px;">
            <?php if ($suivi && $suivi['livreur_nom']):
                $tel = $suivi['livreur_telephone'] ?? '';
                $telClean = preg_replace('/[^0-9]/', '', $tel);
                $whatsapp = $suivi['livreur_whatsapp'] ?: $tel;
                $whatsappClean = preg_replace('/[^0-9]/', '', $whatsapp);
                $email = $suivi['livreur_email'] ?? '';
                $photo = $suivi['livreur_photo'] ?? '';
                $clientALaPosition = !empty($commande['latitude_client']) && !empty($commande['longitude_client']);
                $waHref = $clientALaPosition ? 'https://wa.me/' . $whatsappClean . '?text=' . rawurlencode("Bonjour {$suivi['livreur_nom']}, voici ma position actuelle.") : 'https://wa.me/' . $whatsappClean;
                $waLabel = $clientALaPosition ? 'Partager ma position' : 'Contacter sur WhatsApp';
                $estLivree = $suivi['statut'] === 'Livrée';
            ?>
            <div class="table-card" style="border-color:<?= $estLivree ? 'var(--success)' : '#25D366' ?>;">
                <div class="table-card-header" style="background:<?= $estLivree ? 'var(--success)' : '#25D366' ?>;color:white;">
                    <span class="table-card-title"><i class="fas fa-motorcycle"></i> Votre livreur</span>
                    <?php if ($estLivree): ?>
                    <span style="font-size:11px;opacity:0.9;"><i class="fas fa-check-circle"></i> Livrée</span>
                    <?php else: ?>
                    <span style="font-size:11px;opacity:0.9;">En route</span>
                    <?php endif; ?>
                </div>
                <div style="padding:16px;">
                    <div class="flex gap-3 items-center" style="margin-bottom:12px;">
                        <?php if ($photo): ?>
                        <img src="<?= UPLOADS_URL . '/' . securiser($photo) ?>" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--gray-200);">
                        <?php else: ?>
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--dark);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;"><?= strtoupper(substr($suivi['livreur_nom'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="text-sm font-semibold" style="font-size:15px;"><?= securiser($suivi['livreur_nom']) ?></div>
                            <div class="text-xs text-muted"><?= $estLivree ? 'A livré votre commande' : 'Livreur ClaudiShop' ?></div>
                        </div>
                    </div>
                    <div class="flex gap-3 items-center" style="margin-bottom:14px;">
                        <i class="fas fa-phone" style="color:var(--gray-400);"></i>
                        <div><div class="text-sm font-semibold"><?= formatTelephone($tel) ?></div></div>
                    </div>
                    <?php if (!$estLivree && $whatsappClean): ?>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px;font-size:13px;">
                        <input type="checkbox" id="share-position-cb" <?= $clientALaPosition ? 'checked' : '' ?> onchange="togglePartagePosition()">
                        <span>Partager ma position avec le livreur</span>
                    </label>
                    <div id="share-instructions" style="<?= $clientALaPosition ? '' : 'display:none;' ?>background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:10px;margin-bottom:12px;font-size:12px;line-height:1.5;color:#166534;">
                        <strong>📱 Comment partager votre position :</strong><br>
                        2. Dans la conversation WhatsApp, appuyez sur <strong style="font-size:18px;">📎</strong> (en bas à droite)<br>
                        3. Sélectionnez <strong>Localisation</strong><br>
                        4. Choisissez <strong>Partager en direct</strong> et la durée
                    </div>
                    <a id="wa-link" href="<?= $waHref ?>" target="_blank" onclick="marquerEnCours()" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;font-size:13px;background:#25D366;color:white;text-decoration:none;border-radius:6px;font-weight:600;margin-bottom:6px;"><i class="fab fa-whatsapp" style="font-size:16px;"></i> <span id="wa-btn-label"><?= $waLabel ?></span></a>
                    <?php endif; ?>
                    <?php if (!$estLivree && $telClean): ?>
                    <a href="tel:<?= $telClean ?>" onclick="marquerEnCours()" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;font-size:13px;border:1px solid var(--gray-200);color:var(--dark);text-decoration:none;border-radius:6px;margin-bottom:6px;"><i class="fas fa-phone"></i> Appeler le livreur</a>
                    <?php endif; ?>
                    <?php if (!$estLivree && $email): ?>
                    <a href="mailto:<?= $email ?>?subject=Commande%20%23CMD-<?= str_pad($commande['id'],6,'0',STR_PAD_LEFT) ?>" onclick="marquerEnCours()" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;font-size:13px;border:1px solid var(--gray-200);color:var(--dark);text-decoration:none;border-radius:6px;"><i class="fas fa-envelope"></i> Email</a>
                    <?php endif; ?>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--gray-100);text-align:center;">
                        <a href="mailto:contact@claudishop.com?subject=Problème%20commande%20%23CMD-<?= str_pad($commande['id'],6,'0',STR_PAD_LEFT) ?>" style="font-size:12px;color:var(--gray-400);"><i class="fas fa-exclamation-triangle"></i> Signaler un problème</a>
                    </div>
                </div>
            </div>
            <script>
            function marquerEnCours() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?= BASE_URL ?>/actions/client_update_statut.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('commande_id=<?= $commandeId ?>&action=en_cours');
            }
            function togglePartagePosition() {
                var cb = document.getElementById('share-position-cb');
                var instr = document.getElementById('share-instructions');
                var link = document.getElementById('wa-link');
                var label = document.getElementById('wa-btn-label');
                if (cb.checked) {
                    instr.style.display = '';
                    link.href = 'https://wa.me/<?= $whatsappClean ?>?text=<?= rawurlencode("Bonjour {$suivi['livreur_nom']}, voici ma position actuelle.") ?>';
                    label.textContent = 'Partager ma position';
                } else {
                    instr.style.display = 'none';
                    link.href = 'https://wa.me/<?= $whatsappClean ?>';
                    label.textContent = 'Contacter sur WhatsApp';
                }
            }
            </script>
            <?php endif; ?>
            <div class="table-card">
                <div class="table-card-header"><span class="table-card-title">Informations</span></div>
                <div style="padding:16px;">
                    <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-muted text-sm">Mode de retrait</span><span class="text-sm font-semibold"><?= securiser($commande['mode_retrait'] ?? '—') ?></span></div>
                    <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-muted text-sm">Mode de paiement</span><span class="text-sm font-semibold"><?= $modePaiement ? securiser($modePaiement) : '—' ?></span></div>
                    <div class="flex justify-between" style="padding:8px 0;"><span class="text-muted text-sm">Statut</span><?= getStatutBadge($commande['statut']) ?></div>
                </div>
            </div>
            <div id="suivi-livraison-card" class="table-card">
                <div class="table-card-header"><span class="table-card-title">Suivi de livraison</span></div>
                <div style="padding:16px;">
                    <?php
                    $s = $suivi ? $suivi['statut'] : $commande['statut'];
                    if ($s === 'En cours') $s = 'En route';
                    $etapes = [
                        'En attente' => ['label' => 'Commande validée', 'icon' => 'fa-check-circle'],
                        'Prêt à expédier' => ['label' => 'Préparation', 'icon' => 'fa-box'],
                        'En route' => ['label' => 'En route', 'icon' => 'fa-truck'],
                        'Livrée' => ['label' => 'Livrée', 'icon' => 'fa-gift'],
                    ];
                    $statutIndex = array_keys($etapes);
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:4px;overflow-x:auto;padding:4px 0;">
                        <?php foreach ($etapes as $key => $e):
                            $done = array_search($key, $statutIndex) <= array_search($s, $statutIndex);
                            $current = $key === $s;
                        ?>
                        <div style="display:flex;flex-direction:column;align-items:center;flex:1;min-width:70px;text-align:center;position:relative;">
                            <?php if ($key !== array_key_first($etapes)): ?>
                            <div style="position:absolute;top:16px;right:50%;width:100%;height:2px;background:<?= $done ? 'var(--success)' : 'var(--gray-200)' ?>;z-index:0;"></div>
                            <?php endif; ?>
                            <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $current ? 'var(--success)' : ($done ? '#f0fdf4' : 'var(--gray-100)') ?>;border:2px solid <?= $current ? 'var(--success)' : ($done ? 'var(--success)' : 'var(--gray-200)') ?>;color:<?= $current ? 'white' : ($done ? 'var(--success)' : 'var(--gray-400)') ?>;font-size:12px;z-index:1;">
                                <i class="fas <?= $e['icon'] ?>"></i>
                            </div>
                            <div style="margin-top:6px;font-size:10px;font-weight:<?= $current ? '700' : '600' ?>;color:<?= $current ? 'var(--success)' : ($done ? 'var(--dark)' : 'var(--gray-400)') ?>;white-space:nowrap;"><?= $e['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php exit; ?>
<?php else: ?>
<!-- ── LAYOUT NORMAL (avec sidebar) ── -->
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label"><a href="<?= BASE_URL ?>/user/mes_commandes.php" style="color:var(--gray-400);"><i class="fas fa-arrow-left"></i> Mes commandes</a></div>
        <h1 class="dash-page-title">Commande #CMD-<?= str_pad($commandeId,6,'0',STR_PAD_LEFT) ?></h1>
        <p class="dash-page-sub">Passée le <?= date('d/m/Y \à H:i', strtotime($commande['date_commande'])) ?></p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="dash-two-col" style="align-items:start;">
        <!-- ARTICLES -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Articles commandés</span><span id="commande-statut-badge"><?= getStatutBadge($commande['statut']) ?></span></div>
            <table>
                <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Total</th></tr></thead>
                <tbody>
                <?php if (empty($lignes)): ?>
                <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--gray-400);">Aucun article.</td></tr>
                <?php else: foreach ($lignes as $l): ?>
                <tr>
                    <td><div class="flex items-center gap-2"><div class="admin-thumb"><i class="fas fa-tshirt"></i></div><span class="text-sm font-semibold"><?= securiser($l['nom'] ?? 'Produit') ?></span></div></td>
                    <td class="text-sm"><?= formatPrix($l['prix_unitaire']) ?></td>
                    <td class="text-sm"><?= $l['quantite'] ?></td>
                    <td class="text-sm font-semibold"><?= formatPrix($l['prix_unitaire'] * $l['quantite']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div class="flex justify-between items-center" style="padding:14px 16px;border-top:1px solid var(--gray-100);">
                <span class="text-muted text-sm">Total</span>
                <strong style="font-size:18px;"><?= formatPrix($commande['montant_total']) ?></strong>
            </div>
        </div>

        <!-- RECAP INFOS -->
        <div id="recap-infos" style="display:flex;flex-direction:column;gap:16px;">
            <?php if ($suivi && $suivi['livreur_nom']):
                $tel = $suivi['livreur_telephone'] ?? '';
                $telClean = preg_replace('/[^0-9]/', '', $tel);
                $whatsapp = $suivi['livreur_whatsapp'] ?: $tel;
                $whatsappClean = preg_replace('/[^0-9]/', '', $whatsapp);
                $email = $suivi['livreur_email'] ?? '';
                $photo = $suivi['livreur_photo'] ?? '';
                $clientALaPosition = !empty($commande['latitude_client']) && !empty($commande['longitude_client']);
                $waHref = $clientALaPosition ? 'https://wa.me/' . $whatsappClean . '?text=' . rawurlencode("Bonjour {$suivi['livreur_nom']}, voici ma position actuelle.") : 'https://wa.me/' . $whatsappClean;
                $waLabel = $clientALaPosition ? 'Partager ma position' : 'Contacter sur WhatsApp';
                $estLivree = $suivi['statut'] === 'Livrée';
            ?>
            <div class="table-card" style="border-color:<?= $estLivree ? 'var(--success)' : '#25D366' ?>;">
                <div class="table-card-header" style="background:<?= $estLivree ? 'var(--success)' : '#25D366' ?>;color:white;">
                    <span class="table-card-title"><i class="fas fa-motorcycle"></i> Votre livreur</span>
                    <?php if ($estLivree): ?>
                    <span style="font-size:11px;opacity:0.9;"><i class="fas fa-check-circle"></i> Livrée</span>
                    <?php else: ?>
                    <span style="font-size:11px;opacity:0.9;">En route</span>
                    <?php endif; ?>
                </div>
                <div style="padding:16px;">
                    <div class="flex gap-3 items-center" style="margin-bottom:12px;">
                        <?php if ($photo): ?>
                        <img src="<?= UPLOADS_URL . '/' . securiser($photo) ?>" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--gray-200);">
                        <?php else: ?>
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--dark);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;"><?= strtoupper(substr($suivi['livreur_nom'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="text-sm font-semibold" style="font-size:15px;"><?= securiser($suivi['livreur_nom']) ?></div>
                            <div class="text-xs text-muted"><?= $estLivree ? 'A livré votre commande' : 'Livreur ClaudiShop' ?></div>
                        </div>
                    </div>
                    <div class="flex gap-3 items-center" style="margin-bottom:14px;">
                        <i class="fas fa-phone" style="color:var(--gray-400);"></i>
                        <div><div class="text-sm font-semibold"><?= formatTelephone($tel) ?></div></div>
                    </div>
                    <?php if (!$estLivree && $whatsappClean): ?>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px;font-size:13px;">
                        <input type="checkbox" id="share-position-cb" <?= $clientALaPosition ? 'checked' : '' ?> onchange="togglePartagePosition()">
                        <span>Partager ma position avec le livreur</span>
                    </label>
                    <div id="share-instructions" style="<?= $clientALaPosition ? '' : 'display:none;' ?>background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:10px;margin-bottom:12px;font-size:12px;line-height:1.5;color:#166534;">
                        <strong>📱 Comment partager votre position :</strong><br>
                        2. Dans la conversation WhatsApp, appuyez sur <strong style="font-size:18px;">📎</strong> (en bas à droite)<br>
                        3. Sélectionnez <strong>Localisation</strong><br>
                        4. Choisissez <strong>Partager en direct</strong> et la durée
                    </div>
                    <a id="wa-link" href="<?= $waHref ?>" target="_blank" onclick="marquerEnCours()" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;font-size:13px;background:#25D366;color:white;text-decoration:none;border-radius:6px;font-weight:600;margin-bottom:6px;"><i class="fab fa-whatsapp" style="font-size:16px;"></i> <span id="wa-btn-label"><?= $waLabel ?></span></a>
                    <?php endif; ?>
                    <?php if (!$estLivree && $telClean): ?>
                    <a href="tel:<?= $telClean ?>" onclick="marquerEnCours()" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;font-size:13px;border:1px solid var(--gray-200);color:var(--dark);text-decoration:none;border-radius:6px;margin-bottom:6px;"><i class="fas fa-phone"></i> Appeler le livreur</a>
                    <?php endif; ?>
                    <?php if (!$estLivree && $email): ?>
                    <a href="mailto:<?= $email ?>?subject=Commande%20%23CMD-<?= str_pad($commande['id'],6,'0',STR_PAD_LEFT) ?>" onclick="marquerEnCours()" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;font-size:13px;border:1px solid var(--gray-200);color:var(--dark);text-decoration:none;border-radius:6px;"><i class="fas fa-envelope"></i> Email</a>
                    <?php endif; ?>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--gray-100);text-align:center;">
                        <a href="mailto:contact@claudishop.com?subject=Problème%20commande%20%23CMD-<?= str_pad($commande['id'],6,'0',STR_PAD_LEFT) ?>" style="font-size:12px;color:var(--gray-400);"><i class="fas fa-exclamation-triangle"></i> Signaler un problème</a>
                    </div>
                </div>
            </div>
            <script>
            function marquerEnCours() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?= BASE_URL ?>/actions/client_update_statut.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('commande_id=<?= $commandeId ?>&action=en_cours');
            }
            function togglePartagePosition() {
                var cb = document.getElementById('share-position-cb');
                var instr = document.getElementById('share-instructions');
                var link = document.getElementById('wa-link');
                var label = document.getElementById('wa-btn-label');
                if (cb.checked) {
                    instr.style.display = '';
                    link.href = 'https://wa.me/<?= $whatsappClean ?>?text=<?= rawurlencode("Bonjour {$suivi['livreur_nom']}, voici ma position actuelle.") ?>';
                    label.textContent = 'Partager ma position';
                } else {
                    instr.style.display = 'none';
                    link.href = 'https://wa.me/<?= $whatsappClean ?>';
                    label.textContent = 'Contacter sur WhatsApp';
                }
            }
            </script>
            <?php endif; ?>
            <div class="table-card">
                <div class="table-card-header"><span class="table-card-title">Informations</span></div>
                <div style="padding:16px;">
                    <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-muted text-sm">Mode de retrait</span><span class="text-sm font-semibold"><?= securiser($commande['mode_retrait'] ?? '—') ?></span></div>
                    <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-muted text-sm">Mode de paiement</span><span class="text-sm font-semibold"><?= $modePaiement ? securiser($modePaiement) : '—' ?></span></div>
                    <div class="flex justify-between" style="padding:8px 0;"><span class="text-muted text-sm">Statut</span><?= getStatutBadge($commande['statut']) ?></div>
                </div>
            </div>
            <div id="suivi-livraison-card" class="table-card">
                <div class="table-card-header"><span class="table-card-title">Suivi de livraison</span></div>
                <div style="padding:16px;">
                    <?php
                    $s = $suivi ? $suivi['statut'] : $commande['statut'];
                    if ($s === 'En cours') $s = 'En route';
                    $etapes = [
                        'En attente' => ['label' => 'Commande validée', 'icon' => 'fa-check-circle'],
                        'Prêt à expédier' => ['label' => 'Préparation', 'icon' => 'fa-box'],
                        'En route' => ['label' => 'En route', 'icon' => 'fa-truck'],
                        'Livrée' => ['label' => 'Livrée', 'icon' => 'fa-gift'],
                    ];
                    $statutIndex = array_keys($etapes);
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:4px;overflow-x:auto;padding:4px 0;">
                        <?php foreach ($etapes as $key => $e):
                            $done = array_search($key, $statutIndex) <= array_search($s, $statutIndex);
                            $current = $key === $s;
                        ?>
                        <div style="display:flex;flex-direction:column;align-items:center;flex:1;min-width:70px;text-align:center;position:relative;">
                            <?php if ($key !== array_key_first($etapes)): ?>
                            <div style="position:absolute;top:16px;right:50%;width:100%;height:2px;background:<?= $done ? 'var(--success)' : 'var(--gray-200)' ?>;z-index:0;"></div>
                            <?php endif; ?>
                            <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $current ? 'var(--success)' : ($done ? '#f0fdf4' : 'var(--gray-100)') ?>;border:2px solid <?= $current ? 'var(--success)' : ($done ? 'var(--success)' : 'var(--gray-200)') ?>;color:<?= $current ? 'white' : ($done ? 'var(--success)' : 'var(--gray-400)') ?>;font-size:12px;z-index:1;">
                                <i class="fas <?= $e['icon'] ?>"></i>
                            </div>
                            <div style="margin-top:6px;font-size:10px;font-weight:<?= $current ? '700' : '600' ?>;color:<?= $current ? 'var(--success)' : ($done ? 'var(--dark)' : 'var(--gray-400)') ?>;white-space:nowrap;"><?= $e['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
<script>
(function(){
    var currentUrl = window.location.href;
    var pollId = null;
    function actualiser() {
        fetch(currentUrl)
            .then(function(r){ return r.text(); })
            .then(function(html){
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newRecap = doc.getElementById('recap-infos');
                var oldRecap = document.getElementById('recap-infos');
                if (newRecap && oldRecap) {
                    oldRecap.style.transition = 'opacity 0.3s ease';
                    oldRecap.style.opacity = '0';
                    setTimeout(function(){
                        oldRecap.innerHTML = newRecap.innerHTML;
                        oldRecap.style.opacity = '1';
                    }, 300);
                }
                pollId = setTimeout(actualiser, 10000);
            })
            .catch(function(){ pollId = setTimeout(actualiser, 10000); });
    }
    pollId = setTimeout(actualiser, 10000);
})();
</script>
</div>
</div>
<?php endif; ?>
</body></html>
