  </main>

  <!-- Barre des fonctionnalités (features bar) affichée au-dessus du pied de page -->
  <div class="features-bar" style="border-top:1px solid #E5E5E5;display:grid;grid-template-columns:repeat(4,1fr);">
    <?php
    // Définition du tableau des fonctionnalités à afficher
    $features = [
      ['icon'=>'&#128230;','title'=>'Besoin d\'aide ?','sub'=>'Consultez notre FAQ ou contactez notre support'],
      ['icon'=>'&#128260;','title'=>'Retours faciles','sub'=>'Retournez vos articles sous 7 jours'],
      ['icon'=>'&#128274;','title'=>'Paiement sécurisé','sub'=>'Vos paiements sont protégés à 100%'],
      ['icon'=>'&#128666;','title'=>'Livraison rapide','sub'=>'Partout au Bénin'],
    ];
    // Boucle d'affichage de chaque fonctionnalité
    foreach($features as $f):
    ?>
    <!-- Bloc d'une fonctionnalité individuelle -->
    <div style="padding:20px;display:flex;align-items:center;gap:12px;border-right:1px solid #E5E5E5;">
      <span style="font-size:1.4rem;"><?= $f['icon'] ?></span>
      <div>
        <div style="font-size:.85rem;font-weight:600;"><?= $f['title'] ?></div>
        <div style="font-size:.75rem;color:#9A9A9A;"><?= $f['sub'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pied de page (footer) du client -->
  <footer class="client-footer">
    © 2026 ClaudiShop – Tous droits réservés – Paiement MTN MoMo &amp; Moov Money
    <span style="float:right;">v1.0.0</span>
  </footer>
</div><!-- /client-main -->
</div><!-- /client-layout -->

<!-- Conteneur des notifications toast -->
<div class="toast-container" id="toastContainer"></div>
<script>
// Fonction utilitaire d'affichage d'une notification toast
function showToast(msg, type='success'){
  // Création de l'élément div pour le toast
  const t=document.createElement('div');
  // Attribution de la classe CSS selon le type
  t.className=`toast toast-${type}`;
  // Définition du contenu HTML : icône + message
  t.innerHTML=`<span>${type==='success'?'&#10003;':'&#10005;'}</span><span>${msg}</span>`;
  // Ajout du toast au conteneur dans le DOM
  document.getElementById('toastContainer').appendChild(t);
  // Suppression automatique du toast après 3,5 secondes
  setTimeout(()=>t.remove(),3500);
}
</script>
</body>
</html>
