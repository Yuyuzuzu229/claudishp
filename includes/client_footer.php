  </main>

  <!-- Features bar -->
  <div class="features-bar" style="border-top:1px solid #E5E5E5;display:grid;grid-template-columns:repeat(4,1fr);">
    <?php
    $features = [
      ['icon'=>'📦','title'=>'Besoin d\'aide ?','sub'=>'Consultez notre FAQ ou contactez notre support'],
      ['icon'=>'🔄','title'=>'Retours faciles','sub'=>'Retournez vos articles sous 7 jours'],
      ['icon'=>'🔒','title'=>'Paiement sécurisé','sub'=>'Vos paiements sont protégés à 100%'],
      ['icon'=>'🚚','title'=>'Livraison rapide','sub'=>'Partout au Bénin'],
    ];
    foreach($features as $f):
    ?>
    <div style="padding:20px;display:flex;align-items:center;gap:12px;border-right:1px solid #E5E5E5;">
      <span style="font-size:1.4rem;"><?= $f['icon'] ?></span>
      <div>
        <div style="font-size:.85rem;font-weight:600;"><?= $f['title'] ?></div>
        <div style="font-size:.75rem;color:#9A9A9A;"><?= $f['sub'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <footer class="client-footer">
    © 2026 ClaudiShop – Tous droits réservés – Paiement MTN MoMo &amp; Moov Money
    <span style="float:right;">v1.0.0</span>
  </footer>
</div><!-- /client-main -->
</div><!-- /client-layout -->

<div class="toast-container" id="toastContainer"></div>
<script>
function showToast(msg, type='success'){
  const t=document.createElement('div');
  t.className=`toast toast-${type}`;
  t.innerHTML=`<span>${type==='success'?'✓':'✕'}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(()=>t.remove(),3500);
}
</script>
</body>
</html>
