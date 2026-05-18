  </main><!-- /admin-content -->

  <footer class="admin-footer">
    <span>© 2026 ClaudiShop · Tous droits réservés · Paiement MTN Momo &amp; Moov Money</span>
    <span>v1.0.0</span>
  </footer>

</div><!-- /admin-main -->
</div><!-- /admin-layout -->

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
function showToast(message, type='success') {
    const icons = {success:'✓', error:'✕', info:'ℹ'};
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span style="font-size:1.1rem;">${icons[type]||'ℹ'}</span><span>${message}</span>`;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(()=>t.remove(), 3500);
}

// Confirmation suppression
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Confirmer la suppression ?')) e.preventDefault();
    });
});
</script>
</body>
</html>
