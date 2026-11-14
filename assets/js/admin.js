/* ==================================================================
   Heelora — admin panel behaviour
   ================================================================== */
(function () {
    'use strict';

    var ready = function (fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    };

    ready(function () {
        /* ---------- Sidebar (mobile) ---------- */
        var sidebar = document.querySelector('[data-admin-sidebar]');
        var scrim   = document.querySelector('[data-admin-scrim]');

        function closeSidebar() {
            if (sidebar) { sidebar.classList.remove('is-open'); }
            if (scrim) { scrim.classList.remove('is-open'); }
        }

        document.querySelectorAll('[data-admin-menu]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!sidebar) { return; }
                var open = sidebar.classList.toggle('is-open');
                if (scrim) { scrim.classList.toggle('is-open', open); }
            });
        });
        if (scrim) { scrim.addEventListener('click', closeSidebar); }
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeSidebar(); } });

        /* ---------- Confirm forms ---------- */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
            });
        });

        /* ---------- Auto-submit ---------- */
        document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
            el.addEventListener('change', function () {
                var form = el.closest('form');
                if (!form && el.getAttribute('form')) {
                    form = document.getElementById(el.getAttribute('form'));
                }
                if (form) { form.submit(); }
            });
        });

        /* ---------- Dismiss alerts ---------- */
        document.querySelectorAll('.alert__close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alert = btn.closest('.alert');
                if (alert) { alert.remove(); }
            });
        });

        /* ---------- Live image preview ---------- */
        document.querySelectorAll('[data-image-input]').forEach(function (input) {
            var preview = document.querySelector(input.getAttribute('data-image-input'));
            if (!preview) { return; }
            input.addEventListener('input', function () {
                if (input.value.trim() !== '') { preview.src = input.value.trim(); }
            });
        });

        /* ---------- Slug suggestion from the name ---------- */
        document.querySelectorAll('[data-slug-source]').forEach(function (source) {
            var slug = document.querySelector(source.getAttribute('data-slug-source'));
            if (!slug) { return; }
            source.addEventListener('blur', function () {
                if (slug.value.trim() !== '') { return; }
                slug.value = source.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            });
        });
    });
}());
