<?php
$client_page = 'avis';
$client_title = 'Mes avis';
$client_subtitle = 'Retrouvez la liste de vos avis et ajoutez-en de nouveaux.';
include '../includes/client_header.php';

$mes_avis = [
  ['produit'=>'T-shirt Oversize Noir','achat'=>'13/05/2026','note'=>5,'commentaire'=>'Très bonne qualité de tissu, je recommande !','date'=>'13/05/2026'],
  ['produit'=>'Sneakers Blanches','achat'=>'08/05/2026','note'=>4,'commentaire'=>'Confortables et légères, parfaites pour un usage quotidien.','date'=>'08/05/2026'],
  ['produit'=>'Casquette Noire','achat'=>'27/04/2026','note'=>5,'commentaire'=>'Design simple et élégant, j\'adore !','date'=>'27/04/2026'],
  ['produit'=>'Sac à dos Urban','achat'=>'21/04/2026','note'=>4,'commentaire'=>'Sac pratique avec beaucoup d\'espace. Très utile.','date'=>'21/04/2026'],
];

$produits_achetes = [
  ['nom'=>'T-shirt Oversize Noir','date'=>'13/05/2026'],
  ['nom'=>'Sneakers Blanches','date'=>'08/05/2026'],
  ['nom'=>'Casquette Noire','date'=>'27/04/2026'],
  ['nom'=>'Sac à dos Urban','date'=>'21/04/2026'],
];
?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

  <!-- LISTE AVIS -->
  <div class="card card-lg">
    <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:20px;">Mes avis (<?= count($mes_avis) ?>)</h3>

    <?php foreach($mes_avis as $avis): ?>
    <div style="display:flex;align-items:flex-start;gap:16px;padding:18px 0;border-bottom:1px solid #F5F5F5;">
      <!-- Image produit -->
      <div style="width:70px;height:80px;background:#F0F0F0;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#C0C0C0;">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </div>

      <!-- Infos produit -->
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:.9rem;margin-bottom:3px;"><?= htmlspecialchars($avis['produit']) ?></div>
        <div style="font-size:.75rem;color:#9A9A9A;margin-bottom:10px;">Acheté le <?= $avis['achat'] ?></div>
        <div style="font-size:.75rem;color:#5A5A5A;margin-bottom:4px;">Note</div>
        <div style="font-size:1.2rem;color:#C9A03D;letter-spacing:2px;margin-bottom:8px;">
          <?= str_repeat('★', $avis['note']) . str_repeat('☆', 5 - $avis['note']) ?>
        </div>
        <div style="font-size:.75rem;color:#5A5A5A;margin-bottom:4px;">Commentaire</div>
        <div style="font-size:.85rem;color:#2D2D2D;line-height:1.6;"><?= htmlspecialchars($avis['commentaire']) ?></div>
      </div>

      <!-- Date & actions -->
      <div style="flex-shrink:0;text-align:right;">
        <div style="display:flex;align-items:center;gap:6px;font-size:.75rem;color:#9A9A9A;margin-bottom:10px;justify-content:flex-end;">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?= $avis['date'] ?>
        </div>
        <button style="background:none;border:none;cursor:pointer;color:#9A9A9A;font-size:1.1rem;">⋮</button>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- PAGINATION -->
    <div class="pagination" style="margin-top:16px;">
      <button class="page-btn">‹</button>
      <button class="page-btn active">1</button>
      <button class="page-btn">›</button>
    </div>
  </div>

  <!-- FORMULAIRE AJOUTER AVIS -->
  <div class="card card-lg">
    <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:16px;">Ajouter un avis</h3>

    <form onsubmit="return publierAvis(event)">
      <div class="form-group">
        <label class="form-label">Produit acheté</label>
        <select class="form-control form-select" id="produit-select">
          <option value="">Sélectionnez un produit</option>
          <?php foreach($produits_achetes as $p): ?>
          <option value="<?= htmlspecialchars($p['nom']) ?>"><?= htmlspecialchars($p['nom']) ?> – <?= $p['date'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Note</label>
        <div style="display:flex;gap:6px;font-size:1.5rem;" id="star-rating">
          <?php for($i=1;$i<=5;$i++): ?>
          <span class="star-btn" data-val="<?= $i ?>" style="cursor:pointer;color:#E5E5E5;transition:color .15s;" onclick="setNote(<?= $i ?>)">★</span>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="note" id="note-val" value="0">
      </div>

      <div class="form-group">
        <label class="form-label">Commentaire</label>
        <textarea class="form-control" name="commentaire" rows="5" placeholder="Partagez votre expérience sur ce produit…" id="comment-area" maxlength="500"></textarea>
        <div style="text-align:right;font-size:.72rem;color:#9A9A9A;margin-top:4px;"><span id="char-count">0</span> / 500</div>
      </div>

      <button type="submit" style="width:100%;padding:13px;background:#0A0A0A;color:#fff;border:none;border-radius:4px;font-size:.9rem;font-weight:600;cursor:pointer;">Publier l'avis</button>
    </form>
  </div>
</div>

<script>
let noteVal = 0;
function setNote(n) {
  noteVal = n;
  document.getElementById('note-val').value = n;
  document.querySelectorAll('.star-btn').forEach((s, i) => {
    s.style.color = i < n ? '#C9A03D' : '#E5E5E5';
  });
}
document.getElementById('comment-area').addEventListener('input', function(){
  document.getElementById('char-count').textContent = this.value.length;
});
document.querySelectorAll('.star-btn').forEach(s => {
  s.addEventListener('mouseover', function(){
    const n = parseInt(this.dataset.val);
    document.querySelectorAll('.star-btn').forEach((x, i) => {
      x.style.color = i < n ? '#C9A03D' : '#E5E5E5';
    });
  });
  s.addEventListener('mouseout', () => setNote(noteVal));
});
function publierAvis(e){
  e.preventDefault();
  const produit = document.getElementById('produit-select').value;
  const comment = document.getElementById('comment-area').value;
  if(!produit){ alert('Veuillez sélectionner un produit.'); return; }
  if(noteVal === 0){ alert('Veuillez attribuer une note.'); return; }
  if(!comment.trim()){ alert('Veuillez écrire un commentaire.'); return; }
  alert('✅ Votre avis a été soumis et sera publié après modération.');
  document.getElementById('produit-select').value='';
  document.getElementById('comment-area').value='';
  document.getElementById('char-count').textContent='0';
  setNote(0);
}
</script>

<?php include '../includes/client_footer.php'; ?>
