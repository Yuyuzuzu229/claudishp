// ── Cacher le loader de la page (déclenché sur DOMContentLoaded et load) ──────
// Fonction qui masque l'écran de chargement
function hideLoader() {
    // Récupération de l'élément loader
    var loader = document.getElementById('page-loader');
    // Vérification si le loader existe
    if (loader) {
        // Ajout de la classe 'hidden' qui déclenche la transition CSS
        loader.classList.add('hidden');
        // Suppression complète du loader du DOM après 700ms
        setTimeout(function () { loader.style.display = 'none'; }, 700);
    }
}
// Écouteur d'événement load pour cacher le loader
window.addEventListener('load', hideLoader);

// Initialisation au chargement complet du DOM
document.addEventListener('DOMContentLoaded', function () {

    // ── Cacher le loader (appel précoce, le load event est une sécurité) ─
    hideLoader();

    // ── Chargement paresseux (lazy loading) des images ────────────────
    // Sélection de toutes les images avec la classe 'lazy'
    const lazyImgs = document.querySelectorAll('img.lazy');
    // Vérification de la disponibilité de l'API IntersectionObserver
    if ('IntersectionObserver' in window) {
        // Création d'un observateur d'intersection
        const obs = new IntersectionObserver(entries => {
            // Parcours des entrées observées
            entries.forEach(e => {
                // Si l'image devient visible dans le viewport
                if (e.isIntersecting) {
                    const img = e.target;
                    // Remplacement de l'attribut src par l'URL réelle (data-src)
                    img.src = img.dataset.src;
                    // Ajout de la classe 'loaded' pour l'affichage
                    img.classList.add('loaded');
                    // Arrêt de l'observation de cette image
                    obs.unobserve(img);
                }
            });
        });
        // Observation de chaque image paresseuse
        lazyImgs.forEach(img => obs.observe(img));
    } else {
        // Fallback pour les navigateurs sans IntersectionObserver : chargement immédiat
        lazyImgs.forEach(img => { img.src = img.dataset.src; img.classList.add('loaded'); });
    }

    // ── Fermeture automatique des alertes après 4 secondes ─────────────
    // Sélection de tous les éléments d'alerte
    document.querySelectorAll('.alert').forEach(function (alert) {
        // Temporisation de 4 secondes avant la disparition
        setTimeout(function () {
            // Transition de l'opacité vers 0
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            // Suppression de l'élément après la transition
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ── Filtre de recherche de produits (page boutique) ────────────────
    // Récupération du champ de recherche
    const searchInput = document.getElementById('searchProd');
    // Vérification si le champ existe
    if (searchInput) {
        // Écouteur d'événement à chaque saisie
        searchInput.addEventListener('input', function () {
            // Récupération de la requête en minuscules
            const q = this.value.toLowerCase();
            // Parcours de toutes les lignes du tableau
            document.querySelectorAll('tbody tr').forEach(function (row) {
                // Récupération du texte de la ligne en minuscules
                const text = row.textContent.toLowerCase();
                // Affichage ou masquage selon la correspondance
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }

    // ── Contrôle de quantité (inline) ─────────────────────────────────
    // Sélection de tous les boutons de contrôle de quantité
    document.querySelectorAll('.qty-btn').forEach(function (btn) {
        // Écouteur de clic sur chaque bouton
        btn.addEventListener('click', function () {
            // Récupération du formulaire parent
            const form = this.closest('form');
            // Si pas de formulaire, on arrête
            if (!form) return;
            // Récupération de l'input de quantité
            const input = form.querySelector('.qty-input');
            if (!input) return;
            // Lecture de la valeur actuelle
            let val = parseInt(input.value) || 1;
            // Récupération des bornes max et min
            const max = parseInt(input.max) || 99;
            const min = parseInt(input.min) || 1;
            // Incrémentation ou décrémentation selon la direction
            if (this.dataset.dir === 'up') val = Math.min(val + 1, max);
            else val = Math.max(val - 1, min);
            // Mise à jour de la valeur dans l'input
            input.value = val;
        });
    });

    // ── Fermeture des modales au clic sur l'overlay + touche Échap ──
    // Parcours de tous les overlays de modale
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        // Écouteur de clic sur l'overlay
        overlay.addEventListener('click', function (e) {
            // Si le clic est directement sur l'overlay, on ferme
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });
    // Écouteur global de la touche Échap
    document.addEventListener('keydown', function (e) {
        // Si la touche Échap est pressée
        if (e.key === 'Escape') {
            // Fermeture de toutes les modales ouvertes
            document.querySelectorAll('.modal-overlay[style*="display: flex"]').forEach(function (m) {
                m.style.display = 'none';
            });
        }
    });

    // ── Sélection des tags de taille ──────────────────────────────────
    // Parcours de tous les tags de taille
    document.querySelectorAll('.size-tag').forEach(function (tag) {
        // Écouteur de clic sur un tag
        tag.addEventListener('click', function () {
            // Récupération du groupe parent
            const group = this.closest('.size-tags');
            // Retrait de la classe 'active' de tous les tags du groupe
            if (group) group.querySelectorAll('.size-tag').forEach(t => t.classList.remove('active'));
            // Bascule de la classe 'active' sur le tag cliqué
            this.classList.toggle('active');
        });
        // Écouteur de touche pour l'accessibilité (Entrée ou Espace)
        tag.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
        });
    });

    // ── Bascule des tags de filtre ────────────────────────────────────
    // Parcours de tous les tags de filtre avec attribut data-filter
    document.querySelectorAll('.filter-tag[data-filter]').forEach(function (tag) {
        // Écouteur de clic pour basculer la classe active
        tag.addEventListener('click', function () {
            this.classList.toggle('active');
        });
    });

    // ── Date de la barre supérieure du tableau de bord (format FR) ────
    // Récupération de l'élément d'affichage de la date
    const dateEl = document.querySelector('.dash-topbar-date');
    // Vérification si l'élément existe et est vide
    if (dateEl && !dateEl.textContent.trim()) {
        // Tableaux des jours et mois en français
        const jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        const mois  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        // Date courante
        const now   = new Date();
        // Construction et insertion de la date formatée
        dateEl.textContent = jours[now.getDay()] + ' ' + now.getDate() + ' ' + mois[now.getMonth()] + ' ' + now.getFullYear();
    }

    // ── Clic sur les vignettes de la galerie ──────────────────────────
    // Parcours de toutes les vignettes de la galerie produit
    document.querySelectorAll('.product-gallery-thumb').forEach(function (thumb) {
        // Écouteur de clic sur une vignette
        thumb.addEventListener('click', function () {
            // Retrait de la classe 'active' de toutes les vignettes
            document.querySelectorAll('.product-gallery-thumb').forEach(t => t.classList.remove('active'));
            // Ajout de la classe 'active' sur la vignette cliquée
            this.classList.add('active');
        });
        // Écouteur de touche pour l'accessibilité
        thumb.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
        });
    });

    // ── Bascule du menu mobile ───────────────────────────────────────
    // Récupération des éléments du DOM pour le menu mobile
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');
    const mobileNavOverlay = document.getElementById('mobileNavOverlay');
    const mobileNavClose = document.getElementById('mobileNavClose');
    // Fonction d'ouverture du menu mobile
    function openMobileNav() {
        if (mobileNav) mobileNav.classList.add('open');
        if (mobileNavOverlay) mobileNavOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    // Fonction de fermeture du menu mobile
    function closeMobileNav() {
        if (mobileNav) mobileNav.classList.remove('open');
        if (mobileNavOverlay) mobileNavOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    // Attachement des événements si les éléments existent
    if (menuBtn && mobileNav) {
        menuBtn.addEventListener('click', openMobileNav);
    }
    if (mobileNavClose) {
        mobileNavClose.addEventListener('click', closeMobileNav);
    }
    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', closeMobileNav);
    }
    // Fermeture du menu mobile avec la touche Échap
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mobileNav && mobileNav.classList.contains('open')) {
            closeMobileNav();
        }
    });

    // ── Bascule de la sidebar du tableau de bord (mobile) ─────────────
    // Récupération des éléments du DOM
    const dashToggle = document.getElementById('dashSidebarToggle');
    const dashSidebar = document.getElementById('dashSidebar');
    const dashOverlay = document.getElementById('dashSidebarOverlay');
    // Fonction d'ouverture de la sidebar
    function openDashSidebar() {
        if (dashSidebar) dashSidebar.classList.add('open');
        if (dashOverlay) dashOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    // Fonction de fermeture de la sidebar
    function closeDashSidebar() {
        if (dashSidebar) dashSidebar.classList.remove('open');
        if (dashOverlay) dashOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    // Attachement des événements
    if (dashToggle && dashSidebar) {
        dashToggle.addEventListener('click', openDashSidebar);
    }
    if (dashOverlay) {
        dashOverlay.addEventListener('click', closeDashSidebar);
    }
    // Fermeture de la sidebar avec la touche Échap
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && dashSidebar && dashSidebar.classList.contains('open')) {
            closeDashSidebar();
        }
    });

    // ── Bascule de la sidebar client (mobile) ─────────────────────────
    // Récupération des éléments du DOM
    const sidebarToggle = document.getElementById('mobileSidebarToggle');
    const clientSidebar = document.getElementById('clientSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    // Fonction d'ouverture de la sidebar client
    function openSidebar() {
        if (clientSidebar) clientSidebar.classList.add('open');
        if (sidebarOverlay) sidebarOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    // Fonction de fermeture de la sidebar client
    function closeSidebar() {
        if (clientSidebar) clientSidebar.classList.remove('open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    // Attachement des événements
    if (sidebarToggle && clientSidebar) {
        sidebarToggle.addEventListener('click', openSidebar);
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    // Fermeture de la sidebar client avec la touche Échap
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && clientSidebar && clientSidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // ── Confirmation avant suppression ────────────────────────────────
    // Parcours de tous les éléments avec l'attribut data-confirm
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        // Écouteur de clic pour chaque élément
        el.addEventListener('click', function (e) {
            // Si l'utilisateur annule la confirmation, on empêche l'action par défaut
            if (!confirm(this.dataset.confirm || 'Confirmer cette action ?')) {
                e.preventDefault();
            }
        });
    });

    // ── Bascule du menu déroulant de l'avatar ─────────────────────────
    // Fonction générique de configuration d'un menu déroulant
    function setupDropdown(triggerId, menuId) {
        // Récupération des éléments déclencheur et menu
        var trigger = document.getElementById(triggerId);
        var menu = document.getElementById(menuId);
        // Vérification que les deux éléments existent
        if (trigger && menu) {
            // Écouteur de clic sur le déclencheur
            trigger.addEventListener('click', function (e) {
                // Arrêt de la propagation pour éviter la fermeture immédiate
                e.stopPropagation();
                // Bascule de l'affichage du menu (block/none)
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            });
            // Écouteur de touche pour l'accessibilité
            trigger.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
                if (e.key === 'Escape') { menu.style.display = 'none'; trigger.focus(); }
            });
            // Fermeture du menu au clic ailleurs sur la page
            document.addEventListener('click', function () {
                if (menu.style.display === 'block') menu.style.display = 'none';
            });
        }
    }
    // Initialisation des menus déroulants des avatars
    setupDropdown('avatar-dropdown-trigger', 'avatar-dropdown-menu');
    setupDropdown('nav-avatar-trigger', 'nav-avatar-menu');

    // ── Système de notification toast ──────────────────────────────────
    // Fonction d'affichage d'une notification toast
    function showToast(message, type) {
        // Définition du type par défaut
        type = type || 'success';
        // Recherche du conteneur de toasts existant
        var container = document.getElementById('toast-container');
        // Si le conteneur n'existe pas, on le crée
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
            document.body.appendChild(container);
        }
        // Création de l'élément toast
        var toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.style.cssText = 'min-width:280px;padding:14px 18px;background:white;border:1px solid #e5e7eb;display:flex;align-items:center;gap:12px;font-size:0.875rem;animation:slideIn 0.3s ease;border-radius:4px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);';
        // Couleur de la bordure gauche selon le type
        toast.style.borderLeft = '4px solid ' + (type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6');
        // Création de l'icône Font Awesome
        var icon = document.createElement('i');
        icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        icon.style.cssText = 'font-size:18px;color:' + (type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6');
        icon.setAttribute('aria-hidden', 'true');
        toast.appendChild(icon);
        // Création du texte du message
        var msg = document.createElement('span');
        msg.textContent = message;
        toast.appendChild(msg);
        // Ajout du toast au conteneur
        container.appendChild(toast);
        // Suppression automatique après 3 secondes
        setTimeout(function () {
            toast.style.transition = 'opacity 0.3s';
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }
    // Exposition de la fonction showToast dans le scope global
    window.showToast = showToast;

    // ── Animation de défilement pour les sections ──────────────────────
    // Vérification de la disponibilité de l'API IntersectionObserver
    if ('IntersectionObserver' in window) {
        // Sélection des éléments à animer
        var animEls = document.querySelectorAll('.section-animate');
        // Vérification s'il y a des éléments à observer
        if (animEls.length) {
            // Création de l'observateur d'intersection
            var obs = new IntersectionObserver(function(entries) {
                // Parcours des entrées
                entries.forEach(function(e) {
                    // Si l'élément devient visible
                    if (e.isIntersecting) {
                        // Ajout de la classe d'animation
                        e.target.classList.add('section-enter');
                        // Arrêt de l'observation de cet élément
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            // Observation de chaque élément
            animEls.forEach(function(el) { obs.observe(el); });
        }
    } else {
        // Fallback : rendre tous les éléments visibles immédiatement
        document.querySelectorAll('.section-animate').forEach(function(el) {
            el.classList.add('section-enter');
        });
    }

    // ── Bascule des questions FAQ ──────────────────────────────────────
    // Parcours de toutes les questions de FAQ
    document.querySelectorAll('.faq-q').forEach(function(q) {
        // Accessibilité : ajout des attributs tabindex et role
        q.setAttribute('tabindex', '0');
        q.setAttribute('role', 'button');
        // Écouteur de clic pour basculer la réponse
        q.addEventListener('click', function() {
            // Récupération de l'élément réponse suivant
            var answer = this.nextElementSibling;
            // Vérification qu'il s'agit bien d'une réponse FAQ
            if (answer && answer.classList.contains('faq-a')) {
                // Bascule de l'affichage
                var isOpen = answer.classList.contains('open');
                answer.classList.toggle('open');
                this.classList.toggle('open');
            }
        });
        // Écouteur de touche pour l'accessibilité
        q.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
        });
    });

    // ── État de chargement sur tous les envois de formulaire ──────────
    // Écouteur global d'événement submit
    document.addEventListener('submit', function (e) {
        // Si l'événement est déjà annulé, on ne fait rien
        if (e.defaultPrevented) return;
        // Récupération du formulaire
        var form = e.target;
        // Récupération du bouton de soumission
        var btn = form.querySelector('button[type="submit"]');
        // Si le bouton existe et n'a pas l'attribut noLoading
        if (btn && !btn.dataset.noLoading) {
            // Ajout de la classe de chargement et désactivation
            btn.classList.add('btn-loading');
            btn.disabled = true;
        }
    });

    // ── Ajout au panier via AJAX ──────────────────────────────────────
    // Écouteur global d'événement submit pour les formulaires AJAX
    document.addEventListener('submit', function (e) {
        // Récupération du formulaire
        var form = e.target;
        // Vérification de la présence de l'attribut data-ajax-add
        if (!form.hasAttribute('data-ajax-add')) return;
        // Empêchement de la soumission normale du formulaire
        e.preventDefault();
        // Récupération des données du formulaire
        var formData = new FormData(form);
        // Récupération du bouton de soumission
        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn ? btn.innerHTML : '';
        // Désactivation du bouton et ajout de l'état de chargement
        if (btn) { btn.disabled = true; btn.classList.add('btn-loading'); }
        // Envoi de la requête AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        // Traitement de la réponse
        .then(function (res) {
            // Vérification du statut de la réponse
            if (!res.ok) throw new Error('Erreur serveur ' + res.status);
            // Parsing de la réponse JSON
            return res.json();
        })
        // Traitement des données reçues
        .then(function (data) {
            // Réactivation du bouton
            if (btn) { btn.disabled = false; btn.classList.remove('btn-loading'); btn.innerHTML = originalText; }
            // Redirection si spécifiée
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            // Affichage du résultat
            if (data.success) {
                // Mise à jour du compteur du panier
                var countEl = document.getElementById('cart-count');
                if (countEl) { countEl.textContent = data.count; countEl.setAttribute('data-count', data.count); }
                // Toast de succès
                showToast(data.message, 'success');
            } else {
                // Toast d'erreur
                showToast(data.message, 'error');
            }
        })
        // Gestion des erreurs
        .catch(function (err) {
            // Réactivation du bouton
            if (btn) { btn.disabled = false; btn.classList.remove('btn-loading'); btn.innerHTML = originalText; }
            // Toast d'erreur réseau
            showToast(err.message || 'Erreur réseau. Veuillez réessayer.', 'error');
        });
    });

});
