<?php
$client_page = 'profil';
$client_title = 'Mon profil';
$client_subtitle = 'Gérez vos informations personnelles.';
include '../includes/client_header.php';
?>

<!-- PROFIL CARD -->
<div class="card card-lg" style="margin-bottom:20px;display:flex;align-items:center;gap:24px;">
  <div style="width:80px;height:80px;border-radius:50%;background:#F0F0F0;border:2px solid #E5E5E5;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#9A9A9A;flex-shrink:0;">A</div>
  <div>
    <h2 style="font-family:'DM Sans',sans-serif;font-size:1.2rem;font-weight:600;margin-bottom:6px;">Adjoua K.</h2>
    <span style="font-size:.75rem;background:#F5F5F5;color:#5A5A5A;padding:3px 10px;border-radius:20px;border:1px solid #E5E5E5;">Membre depuis mai 2024</span>
    <div style="display:flex;align-items:center;gap:8px;margin-top:10px;font-size:.82rem;color:#5A5A5A;">
      <span>✉</span> adjoua.k@email.com
    </div>
    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;font-size:.82rem;color:#5A5A5A;">
      <span>📞</span> +229 90 12 34 56
    </div>
  </div>
</div>

<!-- FORMULAIRE INFOS -->
<div class="card card-lg">
  <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:20px;">Informations personnelles</h3>
  <form onsubmit="return sauvegarder(event)">
    <?php
    $fields = [
      ['icon'=>'👤','label'=>'Nom','type'=>'text','value'=>'K.','name'=>'nom'],
      ['icon'=>'👤','label'=>'Prénom','type'=>'text','value'=>'Adjoua','name'=>'prenom'],
      ['icon'=>'✉','label'=>'Email','type'=>'email','value'=>'adjoua.k@email.com','name'=>'email'],
      ['icon'=>'🔒','label'=>'Mot de passe','type'=>'password','value'=>'password123','name'=>'mdp'],
    ];
    foreach($fields as $f):
    ?>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
      <div style="width:40px;height:40px;background:#F5F5F5;border:1px solid #E5E5E5;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;"><?= $f['icon'] ?></div>
      <div style="flex:1;">
        <label style="font-size:.75rem;color:#9A9A9A;display:block;margin-bottom:4px;"><?= $f['label'] ?></label>
        <div style="position:relative;">
          <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" value="<?= $f['value'] ?>" class="form-control">
          <?php if($f['type']==='password'): ?>
          <button type="button" onclick="togglePwd(this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9A9A9A;font-size:.85rem;">👁</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- TÉLÉPHONE -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
      <div style="width:40px;height:40px;background:#F5F5F5;border:1px solid #E5E5E5;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;">📞</div>
      <div style="flex:1;">
        <label style="font-size:.75rem;color:#9A9A9A;display:block;margin-bottom:4px;">Téléphone</label>
        <div style="display:flex;gap:8px;">
          <select class="form-control form-select" style="width:100px;">
            <option>+229</option><option>+228</option><option>+225</option><option>+33</option>
          </select>
          <input type="tel" class="form-control" value="90 12 34 56" style="flex:1;">
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;">
      <button type="submit" style="padding:12px 28px;background:#0A0A0A;color:#fff;border:none;border-radius:4px;font-size:.88rem;font-weight:600;cursor:pointer;">Enregistrer les modifications</button>
    </div>
  </form>
</div>

<script>
function sauvegarder(e){
  e.preventDefault();
  alert('✅ Profil mis à jour avec succès !');
}
function togglePwd(btn){
  const input = btn.previousElementSibling;
  input.type = input.type==='password'?'text':'password';
}
</script>

<?php include '../includes/client_footer.php'; ?>
