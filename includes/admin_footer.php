<!-- Fermeture de la section admin-content -->
  </main><!-- /admin-content -->

  <!-- Pied de page de l'administration -->
  <footer class="admin-footer">
    <!-- Affichage du copyright et des moyens de paiement acceptés -->
    <span>© 2026 ClaudiShop · Tous droits réservés · Paiement MTN Momo &amp; Moov Money</span>
    <!-- Numéro de version de l'application -->
    <span>v1.0.0</span>
  </footer>

</div><!-- /admin-main -->
</div><!-- /admin-layout -->

<!-- Conteneur des notifications toast -->
<div class="toast-container" id="toastContainer"></div>

<script>
// Fonction utilitaire pour afficher une notification toast temporaire
function showToast(message, type='success') {
    // Tableau associatif des icônes selon le type de notification
    const icons = {success:'&#10003;', error:'&#10005;', info:'&#8505;'};
    // Création de l'élément div pour le toast
    const t = document.createElement('div');
    // Attribution de la classe CSS selon le type
    t.className = `toast toast-${type}`;
    // Définition du contenu HTML : icône + message
    t.innerHTML = `<span style="font-size:1.1rem;">${icons[type]||'&#8505;'}</span><span>${message}</span>`;
    // Ajout du toast au conteneur dans le DOM
    document.getElementById('toastContainer').appendChild(t);
    // Suppression automatique du toast après 3,5 secondes
    setTimeout(()=>t.remove(), 3500);
}

// Confirmation avant suppression pour chaque bouton .btn-delete
document.querySelectorAll('.btn-delete').forEach(btn => {
    // Écouteur d'événement au clic sur chaque bouton de suppression
    btn.addEventListener('click', function(e) {
        // Si l'utilisateur n'annule pas la confirmation, on empêche l'action par défaut
        if (!confirm('Confirmer la suppression ?')) e.preventDefault();
    });
});

// Dashboard sidebar toggle (mobile)
(function() {
    // Récupération des éléments du DOM pour le toggle de la barre latérale
    var toggle = document.getElementById('dashSidebarToggle');
    var sidebar = document.getElementById('dashSidebar');
    var overlay = document.getElementById('dashSidebarOverlay');
    // Fonction d'ouverture de la barre latérale
    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    // Fonction de fermeture de la barre latérale
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    // Si le bouton toggle et la sidebar existent, on attache l'événement d'ouverture
    if (toggle && sidebar) {
        toggle.addEventListener('click', openSidebar);
    }
    // Si l'overlay existe, on attache l'événement de fermeture
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    // Fermeture de la sidebar avec la touche Échap
    document.addEventListener('keydown', function(e) {
        // Vérification si la touche Échap est pressée et si la sidebar est ouverte
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>
</body>
</html>
