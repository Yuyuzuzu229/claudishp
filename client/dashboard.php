<?php
// Définition de la page active pour le menu client
$client_page = 'dashboard';
// Titre de la page
$client_title = 'Dashboard';
// Sous-titre affiché dans l'en-tête client
$client_subtitle = 'Bienvenue dans votre espace client.';
// Inclusion de l'en-tête client
include '../includes/client_header.php';
// Inclusion de la configuration
include '../includes/config.php';
?>

<!-- KPI rapides : indicateurs clés de performance en 4 colonnes -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
  <!-- Définition des indicateurs KPI -->
  <?php
  $kpis=[
    ['🛒','Commandes','6','Totales'],
    ['🚚','En cours','1','Livraison active'],
    ['💳','Dépensé','560 000','FCFA au total'],
    ['⭐','Avis donnés','4','Sur 6 achats'],
  ];
  // Boucle d'affichage des KPI
  foreach($kpis as $k):
  ?>
  <div style="background:#fff;border:1px solid #E5E5E5;border-radius:8px;padding:18px;">
    <div style="font-size:1.3rem;margin-bottom:8px;"><?= $k[0] ?></div>
    <div style="font-size:1.5rem;font-weight:700;line-height:1;"><?= $k[2] ?></div>
    <!-- Affichage conditionnel de l'étiquette "FCFA au total" -->
    <?php if($k[3]==='FCFA au total'): ?>
    <div style="font-size:.65rem;color:#9A9A9A;margin-top:2px;"><?= $k[3] ?></div>
    <?php endif; ?>
    <div style="font-size:.75rem;color:#9A9A9A;margin-top:4px;"><?= $k[1] ?> <?= $k[3]!=='FCFA au total'?'· '.$k[3]:'' ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Grille à deux colonnes pour le contenu principal -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">

  <!-- Commandes récentes -->
  <div class="card card-lg">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="font-family:var(--font-body);font-size:1rem;font-weight:600;">Commandes récentes</h3>
      <!-- Lien vers la page des commandes -->
      <a href="commandes.php" style="font-size:.8rem;color:#5A5A5A;text-decoration:none;">Voir tout →</a>
    </div>
    <!-- Définition des 4 dernières commandes -->
    <?php
    $dernières=[
      ['#CMD-000125','13/05/2026','Livrée','145 000','badge-green'],
      ['#CMD-000118','08/05/2026','Livrée','89 000','badge-green'],
      ['#CMD-000109','02/05/2026','En route','62 500','badge-blue'],
      ['#CMD-000102','27/04/2026','En préparation','215 000','badge-orange'],
    ];
    // Boucle d'affichage de chaque commande récente
    foreach($dernières as $c):
    ?>
    <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #F5F5F5;">
      <div style="width:36px;height:36px;border-radius:8px;background:#F5F5F5;display:flex;align-items:center;justify-content:center;font-size:1rem;">📦</div>
      <div style="flex:1;">
        <div style="font-weight:600;font-size:.88rem;"><?= $c[0] ?></div>
        <div style="font-size:.75rem;color:#9A9A9A;"><?= $c[1] ?></div>
      </div>
      <div style="text-align:right;">
        <div style="font-weight:600;font-size:.88rem;"><?= $c[3] ?> FCFA</div>
        <!-- Badge de statut avec couleur -->
        <span class="badge <?= $c[4] ?>" style="font-size:.68rem;"><?= $c[2] ?></span>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <!-- Panneau droit -->
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- Profil rapide -->
    <div class="card card-lg" style="text-align:center;">
      <!-- Avatar circulaire avec initiale -->
      <div style="width:60px;height:60px;border-radius:50%;background:#0A0A0A;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:600;margin:0 auto 12px;">A</div>
      <div style="font-weight:600;font-size:1rem;margin-bottom:2px;">Adjoua K.</div>
      <div style="font-size:.75rem;color:#9A9A9A;margin-bottom:12px;">adjoua.k@email.com</div>
      <!-- Badge "Membre depuis" -->
      <span style="display:inline-block;font-size:.72rem;background:#F5F5F5;color:#5A5A5A;padding:3px 10px;border-radius:20px;border:1px solid #E5E5E5;">Membre depuis mai 2024</span>
      <!-- Lien vers la modification du profil -->
      <a href="profil.php" style="display:block;margin-top:14px;padding:9px;border:1.5px solid #E5E5E5;border-radius:4px;font-size:.82rem;color:#0A0A0A;text-decoration:none;font-weight:500;">✏️ Modifier le profil</a>
    </div>

    <!-- Accès rapides -->
    <div class="card card-lg">
      <h3 style="font-family:var(--font-body);font-size:.9rem;font-weight:600;margin-bottom:12px;">Accès rapide</h3>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <!-- Définition des liens d'accès rapide -->
        <?php
        $links=[
          ['🛒','Mon panier','panier.php','4 articles'],
          ['📦','Mes commandes','commandes.php','6 commandes'],
          ['⭐','Mes avis','avis.php','4 avis'],
          ['🔔','Notifications','notifications.php','3 non lues'],
        ];
        // Boucle d'affichage des liens
        foreach($links as $l):
        ?>
        <a href="<?= $l[2] ?>" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #E5E5E5;border-radius:6px;text-decoration:none;color:#0A0A0A;transition:background .15s;" onmouseover="this.style.background='#F5F5F5'" onmouseout="this.style.background=''">
          <span style="font-size:1rem;"><?= $l[0] ?></span>
          <span style="flex:1;font-size:.85rem;font-weight:500;"><?= $l[1] ?></span>
          <span style="font-size:.72rem;color:#9A9A9A;"><?= $l[3] ?></span>
          <span style="color:#C0C0C0;">›</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/client_footer.php'; ?>
