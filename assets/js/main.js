/* ==================================================================
   Heelora — storefront behaviour
   ================================================================== */
(function () {
    'use strict';

    var ready = function (fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    };

    ready(function () {
        var body = document.body;

        /* ---------- Overlay scrim ---------- */
        var scrim = document.querySelector('[data-scrim]');

        function closeAll() {
            document.querySelectorAll('[data-mobile-nav], [data-search-overlay]').forEach(function (el) {
                el.classList.remove('is-open');
                el.setAttribute('aria-hidden', 'true');
            });
            if (scrim) { scrim.classList.remove('is-open'); }
            body.classList.remove('no-scroll');
        }

        function openLayer(el, button) {
            if (!el) { return; }
            closeAll();
            el.classList.add('is-open');
            el.setAttribute('aria-hidden', 'false');
            if (scrim) { scrim.classList.add('is-open'); }
            body.classList.add('no-scroll');
            if (button) { button.setAttribute('aria-expanded', 'true'); }
            var focusable = el.querySelector('input, button, a');
            if (focusable) { setTimeout(function () { focusable.focus(); }, 60); }
        }

        /* ---------- Mobile nav ---------- */
        var mobileNav = document.querySelector('[data-mobile-nav]');
        document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () { openLayer(mobileNav, btn); });
        });
        document.querySelectorAll('[data-nav-close]').forEach(function (btn) {
            btn.addEventListener('click', closeAll);
        });

        /* ---------- Search overlay ---------- */
        var searchOverlay = document.querySelector('[data-search-overlay]');
        document.querySelectorAll('[data-search-open]').forEach(function (btn) {
            btn.addEventListener('click', function () { openLayer(searchOverlay, btn); });
        });
        document.querySelectorAll('[data-search-close]').forEach(function (btn) {
            btn.addEventListener('click', closeAll);
        });

        if (scrim) { scrim.addEventListener('click', closeAll); }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeAll(); }
        });

        /* ---------- Filters toggle (mobile) ---------- */
        document.querySelectorAll('[data-filters-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var panel = document.querySelector('[data-filters]');
                if (panel) { panel.classList.toggle('is-open'); }
            });
        });

        /* ---------- Accordions ---------- */
        document.querySelectorAll('.accordion__item').forEach(function (item) {
            var trigger = item.querySelector('.accordion__trigger');
            if (!trigger) { return; }
            trigger.addEventListener('click', function () {
                var open = item.classList.contains('is-open');
                var group = item.closest('.accordion');
                if (group) {
                    group.querySelectorAll('.accordion__item.is-open').forEach(function (other) {
                        other.classList.remove('is-open');
                    });
                }
                if (!open) { item.classList.add('is-open'); }
            });
        });

        /* ---------- Quantity steppers ---------- */
        document.querySelectorAll('[data-qty]').forEach(function (wrap) {
            var input = wrap.querySelector('input');
            if (!input) { return; }
            wrap.querySelectorAll('[data-qty-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var step = parseInt(btn.getAttribute('data-qty-step'), 10) || 0;
                    var min = parseInt(input.getAttribute('min'), 10) || 1;
                    var max = parseInt(input.getAttribute('max'), 10) || 10;
                    var next = (parseInt(input.value, 10) || min) + step;
                    next = Math.max(min, Math.min(max, next));
                    input.value = next;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });

        /* ---------- Product gallery ---------- */
        var galleryMain = document.querySelector('[data-gallery-main]');
        if (galleryMain) {
            document.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    var full = thumb.getAttribute('data-gallery-thumb');
                    if (full) { galleryMain.src = full; }
                    document.querySelectorAll('[data-gallery-thumb]').forEach(function (t) { t.classList.remove('is-active'); });
                    thumb.classList.add('is-active');
                });
            });
        }

        /* ---------- Auto-submit selects (sort, order status) ---------- */
        document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
            el.addEventListener('change', function () {
                var form = el.closest('form');
                if (!form && el.getAttribute('form')) {
                    form = document.getElementById(el.getAttribute('form'));
                }
                if (form) { form.submit(); }
            });
        });

        /* ---------- Confirm forms ---------- */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
            });
        });

        /* ---------- Dismiss alerts ---------- */
        document.querySelectorAll('.alert__close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alert = btn.closest('.alert');
                if (alert) { alert.remove(); }
            });
        });

        /* ---------- Reveal on scroll ---------- */
        var revealables = document.querySelectorAll('[data-reveal]');
        if (revealables.length) {
            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                revealables.forEach(function (el) { io.observe(el); });
            } else {
                revealables.forEach(function (el) { el.classList.add('is-visible'); });
            }
        }

        /* ---------- Back to top ---------- */
        var toTop = document.querySelector('[data-to-top]');
        if (toTop) {
            var onScroll = function () {
                toTop.classList.toggle('is-visible', window.scrollY > 600);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
            toTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        /* ---------- Payment option notes (checkout) ---------- */
        var payInputs = document.querySelectorAll('[data-pay]');
        if (payInputs.length) {
            var syncPay = function () {
                var selected = document.querySelector('[data-pay]:checked');
                var value = selected ? selected.value : '';
                document.querySelectorAll('[data-pay-note]').forEach(function (note) {
                    note.hidden = note.getAttribute('data-pay-note') !== value;
                });
            };
            payInputs.forEach(function (input) { input.addEventListener('change', syncPay); });
            syncPay();
        }

        /* ---------- Wishlist (local mirror for the header count) ---------- */
        document.querySelectorAll('.product-card__wish').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('.wish-btn');
                if (btn) { btn.classList.toggle('is-active'); }
            });
        });
    });
}());
