// ── Hide page loader (fire on both DOMContentLoaded and load) ──────
function hideLoader() {
    var loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.add('hidden');
        setTimeout(function () { loader.style.display = 'none'; }, 700);
    }
}
window.addEventListener('load', hideLoader);

document.addEventListener('DOMContentLoaded', function () {

    // ── Hide page loader (early; load event also fires hideLoader for safety) ─
    hideLoader();

    // ── Lazy load images ───────────────────────────────────────────────
    const lazyImgs = document.querySelectorAll('img.lazy');
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const img = e.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    obs.unobserve(img);
                }
            });
        });
        lazyImgs.forEach(img => obs.observe(img));
    } else {
        lazyImgs.forEach(img => { img.src = img.dataset.src; img.classList.add('loaded'); });
    }

    // ── Auto-dismiss alerts ─────────────────────────────────────────────
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ── Product search filter (boutique) ────────────────────────────────
    const searchInput = document.getElementById('searchProd');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }

    // ── Quantity control (inline) ───────────────────────────────────────
    document.querySelectorAll('.qty-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = this.closest('form');
            if (!form) return;
            const input = form.querySelector('.qty-input');
            if (!input) return;
            let val = parseInt(input.value) || 1;
            const max = parseInt(input.max) || 99;
            const min = parseInt(input.min) || 1;
            if (this.dataset.dir === 'up') val = Math.min(val + 1, max);
            else val = Math.max(val - 1, min);
            input.value = val;
        });
    });

    // ── Close modals on overlay click + Escape key ─────────────────────
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay[style*="display: flex"]').forEach(function (m) {
                m.style.display = 'none';
            });
        }
    });

    // ── Size tag selection ──────────────────────────────────────────────
    document.querySelectorAll('.size-tag').forEach(function (tag) {
        tag.addEventListener('click', function () {
            const group = this.closest('.size-tags');
            if (group) group.querySelectorAll('.size-tag').forEach(t => t.classList.remove('active'));
            this.classList.toggle('active');
        });
        tag.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
        });
    });

    // ── Filter tag toggle ───────────────────────────────────────────────
    document.querySelectorAll('.filter-tag[data-filter]').forEach(function (tag) {
        tag.addEventListener('click', function () {
            this.classList.toggle('active');
        });
    });

    // ── Dashboard topbar date (FR) ──────────────────────────────────────
    const dateEl = document.querySelector('.dash-topbar-date');
    if (dateEl && !dateEl.textContent.trim()) {
        const jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        const mois  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        const now   = new Date();
        dateEl.textContent = jours[now.getDay()] + ' ' + now.getDate() + ' ' + mois[now.getMonth()] + ' ' + now.getFullYear();
    }

    // ── Gallery thumb click ─────────────────────────────────────────────
    document.querySelectorAll('.product-gallery-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            document.querySelectorAll('.product-gallery-thumb').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
        thumb.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
        });
    });

    // ── Mobile menu toggle ─────────────────────────────────────────────
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');
    if (menuBtn && mobileNav) {
        menuBtn.addEventListener('click', function () {
            const isHidden = mobileNav.style.display === 'none' || !mobileNav.style.display;
            mobileNav.style.display = isHidden ? 'flex' : 'none';
        });
    }

    // ── Confirm delete ─────────────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Confirmer cette action ?')) {
                e.preventDefault();
            }
        });
    });

    // ── Avatar dropdown toggle ─────────────────────────────────────
    function setupDropdown(triggerId, menuId) {
        var trigger = document.getElementById(triggerId);
        var menu = document.getElementById(menuId);
        if (trigger && menu) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            });
            trigger.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
                if (e.key === 'Escape') { menu.style.display = 'none'; trigger.focus(); }
            });
            document.addEventListener('click', function () {
                if (menu.style.display === 'block') menu.style.display = 'none';
            });
        }
    }
    setupDropdown('avatar-dropdown-trigger', 'avatar-dropdown-menu');
    setupDropdown('nav-avatar-trigger', 'nav-avatar-menu');

    // ── Toast notification system ───────────────────────────────────
    function showToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.style.cssText = 'min-width:280px;padding:14px 18px;background:white;border:1px solid #e5e7eb;display:flex;align-items:center;gap:12px;font-size:0.875rem;animation:slideIn 0.3s ease;border-radius:4px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);';
        toast.style.borderLeft = '4px solid ' + (type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6');
        var icon = document.createElement('i');
        icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        icon.style.cssText = 'font-size:18px;color:' + (type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6');
        icon.setAttribute('aria-hidden', 'true');
        toast.appendChild(icon);
        var msg = document.createElement('span');
        msg.textContent = message;
        toast.appendChild(msg);
        container.appendChild(toast);
        setTimeout(function () {
            toast.style.transition = 'opacity 0.3s';
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }
    window.showToast = showToast;

    // ── Scroll-reveal animation for sections ─────────────────────────
    if ('IntersectionObserver' in window) {
        var animEls = document.querySelectorAll('.section-animate');
        if (animEls.length) {
            var obs = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('section-enter');
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            animEls.forEach(function(el) { obs.observe(el); });
        }
    } else {
        // Fallback: make all visible immediately
        document.querySelectorAll('.section-animate').forEach(function(el) {
            el.classList.add('section-enter');
        });
    }

    // ── FAQ toggle ──────────────────────────────────────────────────
    document.querySelectorAll('.faq-q').forEach(function(q) {
        q.setAttribute('tabindex', '0');
        q.setAttribute('role', 'button');
        q.addEventListener('click', function() {
            var answer = this.nextElementSibling;
            if (answer && answer.classList.contains('faq-a')) {
                var isOpen = answer.classList.contains('open');
                answer.classList.toggle('open');
                this.classList.toggle('open');
            }
        });
        q.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
        });
    });

    // ── Loading state on all form submits ────────────────────────────
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        var form = e.target;
        var btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.dataset.noLoading) {
            btn.classList.add('btn-loading');
            btn.disabled = true;
        }
    });

    // ── AJAX Add to Cart ───────────────────────────────────────────
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute('data-ajax-add')) return;
        e.preventDefault();
        var formData = new FormData(form);
        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.classList.add('btn-loading'); }
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Erreur serveur ' + res.status);
            return res.json();
        })
        .then(function (data) {
            if (btn) { btn.disabled = false; btn.classList.remove('btn-loading'); btn.innerHTML = originalText; }
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.success) {
                var countEl = document.getElementById('cart-count');
                if (countEl) { countEl.textContent = data.count; countEl.setAttribute('data-count', data.count); }
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(function (err) {
            if (btn) { btn.disabled = false; btn.classList.remove('btn-loading'); btn.innerHTML = originalText; }
            showToast(err.message || 'Erreur réseau. Veuillez réessayer.', 'error');
        });
    });

});
