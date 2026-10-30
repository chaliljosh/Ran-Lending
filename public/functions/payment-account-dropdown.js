// public/functions/payment-account-dropdown.js
// Used by: resources/views/admin/partials/assigned-payment-method.blade.php
// (loaded from admin/ongoing-loan-detail.blade.php)
//
// 1) Keeps the bank logo in the "assigned" box in sync when assigned-payment-method.js
//    changes the provider text (no page reload).
// 2) Custom dropdown with bank logos. It mirrors the hidden #pmAssignSelect and fires its
//    "change" event. The assign / confirm / success flow is in assigned-payment-method.js.

(function () {
    const panel = document.getElementById('pmAssignPanel');
    if (!panel) return;

    // ---------- 1) Assigned box logo ----------
    (function assignedLogo() {
        const nameEl = document.getElementById('pmdProvider');
        const box = document.getElementById('pmdLogo');
        if (!nameEl || !box) return;

        let map = {};
        try { map = JSON.parse(panel.dataset.logoMap || '{}') || {}; } catch (e) { map = {}; }
        const base = panel.dataset.logoBase;

        function renderLogo() {
            const name = (nameEl.textContent || '').trim();
            box.innerHTML = '';
            if (!name) return;

            const initials = document.createElement('span');
            initials.className = 'pm-logo-fallback';
            initials.textContent = name.slice(0, 2).toUpperCase();
            box.appendChild(initials);

            const file = map[name.toLowerCase()];
            if (file) {
                const img = new Image();
                img.alt = name;
                img.width = 30;
                img.height = 30;
                img.onload = function () { box.innerHTML = ''; box.appendChild(img); };
                img.src = base + '/' + file;
            }
        }

        new MutationObserver(renderLogo).observe(nameEl, { childList: true, characterData: true, subtree: true });
    })();

    // ---------- 2) Custom dropdown ----------
    (function logoDropdown() {
        const sel = document.getElementById('pmAssignSelect');
        const toggle = document.getElementById('pmDdToggle');
        const menu = document.getElementById('pmDdMenu');
        const current = document.getElementById('pmDdCurrent');
        const dd = document.getElementById('pmAssignDd');
        if (!sel || !toggle || !menu || !current || !dd) return;

        const items = Array.prototype.slice.call(menu.querySelectorAll('.pm-dd-option'));

        function render() {
            const v = sel.value;
            let active = null;
            items.forEach(function (li) {
                const on = li.dataset.value === v;
                li.classList.toggle('is-selected', on && v !== '');
                li.setAttribute('aria-selected', on ? 'true' : 'false');
                if (on) active = li;
            });

            current.innerHTML = '';
            if (!active || v === '') {
                current.innerHTML = '<span class="pm-dd-placeholder">Select an account</span>';
                return;
            }
            current.appendChild(active.querySelector('.pm-logo').cloneNode(true));
            const label = document.createElement('span');
            label.className = 'pm-dd-label';
            label.textContent = active.dataset.label;
            current.appendChild(label);
        }

        function open() {
            if (toggle.disabled) return;
            render();
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        }

        function close() {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        function choose(li) {
            sel.value = li.dataset.value;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            render();
            close();
            toggle.focus();
        }

        toggle.addEventListener('click', function () { menu.hidden ? open() : close(); });

        menu.addEventListener('click', function (e) {
            const li = e.target.closest('.pm-dd-option');
            if (li) choose(li);
        });

        document.addEventListener('click', function (e) {
            if (!dd.contains(e.target)) close();
        });

        dd.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); toggle.focus(); return; }
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') return;

            const idx = items.indexOf(document.activeElement);
            if (e.key === 'Enter' && idx > -1) { e.preventDefault(); choose(items[idx]); return; }
            if (e.key === 'Enter') return;

            e.preventDefault();
            if (menu.hidden) open();
            let next = e.key === 'ArrowDown' ? idx + 1 : idx - 1;
            if (idx === -1) next = 0;
            next = Math.max(0, Math.min(items.length - 1, next));
            items[next].focus();
        });

        // keep in sync when assigned-payment-method.js disables / changes the real select
        new MutationObserver(function () { toggle.disabled = sel.disabled; })
            .observe(sel, { attributes: true, attributeFilter: ['disabled'] });
        sel.addEventListener('change', render);

        render();

        // Custom dropdown is ready: hide the native select and show the custom one
        sel.classList.add('pm-native-hidden');
        dd.hidden = false;
    })();
})();