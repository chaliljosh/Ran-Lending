// public/functions/audit-logs.js
// Used by: resources/views/admin/audit-logs.blade.php
//
// - Filters, Reset and pagination load in the background (no full page reload).
//   Only the entry count, the table and the pagination are swapped.
// - From / To use the same calendar as "Record Payment" (ongoing-loan-detail.js).

(function () {
    const page = document.getElementById('alPage');
    if (!page) return;

    const $ = (id) => document.getElementById(id);
    const form = $('alFilters');

    // ---------- Load results without reloading the page ----------
    let controller = null;

    function setLoading(on) {
        const wrap = page.querySelector('.al-table-wrap');
        if (!wrap) return;
        wrap.style.transition = 'opacity .15s ease';
        wrap.style.opacity = on ? '.5' : '';
        wrap.style.pointerEvents = on ? 'none' : '';
    }

    // Current form values -> URL (empty fields are left out)
    function buildUrl() {
        const url = new URL(form.getAttribute('action'), window.location.href);
        new FormData(form).forEach(function (value, key) {
            if (String(value).trim() !== '') url.searchParams.set(key, value);
        });
        return url.toString();
    }

    // Used by the browser Back / Forward buttons
    function syncFormFromUrl() {
        const params = new URLSearchParams(window.location.search);
        Array.from(form.elements).forEach(function (el) {
            if (el.name) el.value = params.get(el.name) || '';
        });
        refreshCalendars();
    }

    async function load(url, push) {
        if (controller) controller.abort();
        controller = new AbortController();
        setLoading(true);

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            // Session expired / redirected somewhere else -> do a normal page visit
            if (!res.ok || res.url.indexOf('/admin/audit-logs') === -1) {
                window.location.href = url;
                return;
            }

            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const newPill = doc.querySelector('.al-count-pill');
            const newWrap = doc.querySelector('.al-table-wrap');
            const curPill = page.querySelector('.al-count-pill');
            const curWrap = page.querySelector('.al-table-wrap');

            if (!newWrap || !curWrap) {
                window.location.href = url;
                return;
            }

            if (newPill && curPill) curPill.replaceWith(newPill);
            curWrap.replaceWith(newWrap);

            // Pagination only exists when there is more than one page
            const curPager = page.querySelector('.al-pagination');
            const newPager = doc.querySelector('.al-pagination');
            if (curPager && newPager) curPager.replaceWith(newPager);
            else if (curPager) curPager.remove();
            else if (newPager) page.querySelector('.al-table-wrap').after(newPager);

            if (push) window.history.pushState({ al: 1 }, '', url);
        } catch (err) {
            if (err.name === 'AbortError') return; // a newer request replaced this one
            window.location.href = url;            // anything else: normal page visit
        } finally {
            if (controller && !controller.signal.aborted) setLoading(false);
        }
    }

    // ---------- Calendar (same as the Record Payment calendar) ----------
    function todayLocal() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
    }

    function createDatePicker(displayEl, hiddenEl, onPick) {
        const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const WEEKDAYS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        const pad = n => String(n).padStart(2, '0');
        const toYmd = (y, m, d) => y + '-' + pad(m + 1) + '-' + pad(d);
        const parseYmd = str => {
            const parts = str.split('-').map(Number);
            return { y: parts[0], m: parts[1] - 1, d: parts[2] };
        };

        let minYmd = '';
        let maxYmd = '';
        let view = 'days';
        let viewYear = 0;
        let viewMonth = 0;
        let isOpen = false;

        const pop = document.createElement('div');
        pop.className = 'al-dp';
        pop.style.display = 'none';
        pop.setAttribute('role', 'dialog');
        document.body.appendChild(pop);

        function setValue(ymd) {
            hiddenEl.value = ymd || '';
            if (ymd) {
                const p = parseYmd(ymd);
                displayEl.value = pad(p.m + 1) + '/' + pad(p.d) + '/' + p.y;
            } else {
                displayEl.value = '';
            }
        }

        function setRange(min, max) {
            minYmd = min || '';
            maxYmd = max || '';
        }

        function isDisabled(ymd) {
            return (minYmd && ymd < minYmd) || (maxYmd && ymd > maxYmd);
        }

        function monthIndex(ymd) {
            const p = parseYmd(ymd);
            return p.y * 12 + p.m;
        }

        function headerHtml(showNav) {
            const prevOff = minYmd && (viewYear * 12 + viewMonth) <= monthIndex(minYmd);
            const nextOff = maxYmd && (viewYear * 12 + viewMonth) >= monthIndex(maxYmd);
            const caret = view === 'years' ? 'feather-chevron-up' : 'feather-chevron-down';
            let html = '<div class="al-dp-head">' +
                '<button type="button" class="al-dp-title" data-act="toggle-view">' +
                MONTHS[viewMonth] + ' ' + viewYear + ' <i class="' + caret + '"></i></button>';
            if (showNav) {
                html += '<div class="al-dp-nav">' +
                    '<button type="button" class="al-dp-navbtn" data-act="prev" aria-label="Previous month"' + (prevOff ? ' disabled' : '') + '><i class="feather-chevron-left"></i></button>' +
                    '<button type="button" class="al-dp-navbtn" data-act="next" aria-label="Next month"' + (nextOff ? ' disabled' : '') + '><i class="feather-chevron-right"></i></button>' +
                    '</div>';
            }
            return html + '</div>';
        }

        function render() {
            if (view === 'years') {
                renderYears();
            } else {
                renderDays();
            }
            if (isOpen) position();
        }

        function renderDays() {
            const firstWeekday = new Date(viewYear, viewMonth, 1).getDay();
            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            const selected = hiddenEl.value;
            const today = todayLocal();

            let html = headerHtml(true);
            html += '<div class="al-dp-weekdays">' + WEEKDAYS.map(w => '<span>' + w + '</span>').join('') + '</div>';
            html += '<div class="al-dp-grid">';
            for (let i = 0; i < firstWeekday; i++) html += '<span></span>';
            for (let d = 1; d <= daysInMonth; d++) {
                const ymd = toYmd(viewYear, viewMonth, d);
                let cls = 'al-dp-day';
                if (ymd === today) cls += ' is-today';
                if (ymd === selected) cls += ' is-selected';
                html += '<button type="button" class="' + cls + '" data-ymd="' + ymd + '"' +
                    (isDisabled(ymd) ? ' disabled' : '') + '>' + d + '</button>';
            }
            html += '</div>';
            // A filter must be removable, so the calendar has a Clear button
            html += '<div class="al-dp-foot"><button type="button" class="al-dp-clear" data-act="clear"' +
                (selected ? '' : ' disabled') + '>Clear</button></div>';
            pop.innerHTML = html;
        }

        function renderYears() {
            const thisYear = new Date().getFullYear();
            const minY = minYmd ? parseYmd(minYmd).y : thisYear - 10;
            const maxY = maxYmd ? parseYmd(maxYmd).y : thisYear;

            let html = headerHtml(false) + '<div class="al-dp-years">';
            for (let y = minY; y <= maxY; y++) {
                html += '<button type="button" class="al-dp-year' + (y === viewYear ? ' is-selected' : '') +
                    '" data-year="' + y + '">' + y + '</button>';
            }
            html += '</div>';
            pop.innerHTML = html;

            const grid = pop.querySelector('.al-dp-years');
            const current = pop.querySelector('.al-dp-year.is-selected');
            if (grid && current) {
                grid.scrollTop = current.offsetTop - (grid.clientHeight / 2) + (current.clientHeight / 2);
            }
        }

        // The calendar is fixed to the screen: just below the box, or above it when there is no room
        function position() {
            const r = displayEl.getBoundingClientRect();
            const w = pop.offsetWidth;
            const h = pop.offsetHeight;

            let top = r.bottom + 6;
            if (top + h > window.innerHeight - 8) {
                top = Math.max(8, r.top - h - 6);
            }
            const left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));

            pop.style.top = top + 'px';
            pop.style.left = left + 'px';
        }

        function open() {
            if (isOpen) return;
            const base = hiddenEl.value || todayLocal();
            let p = parseYmd(base);
            if (isDisabled(base)) {
                p = parseYmd(maxYmd && base > maxYmd ? maxYmd : minYmd);
            }
            viewYear = p.y;
            viewMonth = p.m;
            view = 'days';
            isOpen = true;
            pop.style.display = 'block';
            render();
        }

        function close() {
            if (!isOpen) return;
            isOpen = false;
            pop.style.display = 'none';
        }

        function shiftMonth(delta) {
            const idx = viewYear * 12 + viewMonth + delta;
            viewYear = Math.floor(idx / 12);
            viewMonth = idx % 12;
            render();
        }

        function pickYear(year) {
            viewYear = year;
            if (minYmd && (viewYear * 12 + viewMonth) < monthIndex(minYmd)) viewMonth = parseYmd(minYmd).m;
            if (maxYmd && (viewYear * 12 + viewMonth) > monthIndex(maxYmd)) viewMonth = parseYmd(maxYmd).m;
            view = 'days';
            render();
        }

        pop.addEventListener('click', function (e) {
            const actBtn = e.target.closest('[data-act]');
            if (actBtn) {
                if (actBtn.disabled) return;
                const act = actBtn.dataset.act;
                if (act === 'toggle-view') { view = view === 'days' ? 'years' : 'days'; render(); }
                if (act === 'prev') shiftMonth(-1);
                if (act === 'next') shiftMonth(1);
                if (act === 'clear') {
                    setValue('');
                    close();
                    displayEl.focus();
                    if (onPick) onPick();
                }
                return;
            }
            const dayBtn = e.target.closest('.al-dp-day');
            if (dayBtn && !dayBtn.disabled) {
                const changed = hiddenEl.value !== dayBtn.dataset.ymd;
                setValue(dayBtn.dataset.ymd);
                close();
                displayEl.focus();
                if (changed && onPick) onPick();
                return;
            }
            const yearBtn = e.target.closest('.al-dp-year');
            if (yearBtn) pickYear(Number(yearBtn.dataset.year));
        });

        displayEl.addEventListener('click', function () { isOpen ? close() : open(); });
        displayEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                open();
            }
        });

        document.addEventListener('mousedown', function (e) {
            if (!isOpen) return;
            if (pop.contains(e.target) || displayEl.parentElement.contains(e.target)) return;
            close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) {
                e.preventDefault();
                close();
            }
        });

        // The calendar is fixed on screen, so close it when the page moves (scrolling inside the calendar is fine)
        window.addEventListener('scroll', function (e) {
            if (isOpen && !pop.contains(e.target)) close();
        }, true);
        window.addEventListener('resize', close);

        return {
            setValue: setValue,
            setRange: setRange,
            close: close,
            get: function () { return hiddenEl.value; },
        };
    }

    // One calendar per date field: pickers.from and pickers.to
    const pickers = {};

    // From can't be after To, and neither can be in the future
    function updateRanges() {
        const today = todayLocal();
        const fromVal = pickers.from ? pickers.from.get() : '';
        const toVal = pickers.to ? pickers.to.get() : '';
        if (pickers.from) pickers.from.setRange('', toVal && toVal < today ? toVal : today);
        if (pickers.to) pickers.to.setRange(fromVal, today);
    }

    // After the values were changed from code (Reset, Back / Forward), show them in the date boxes
    function refreshCalendars() {
        Object.keys(pickers).forEach(function (key) {
            pickers[key].close();
            pickers[key].setValue(pickers[key].get());
        });
        updateRanges();
    }

    if (form) {
        form.querySelectorAll('[data-al-date]').forEach(function (wrap) {
            const hidden = wrap.querySelector('input[type="hidden"]');
            const display = wrap.querySelector('.al-date-input');
            if (!hidden || !display) return;

            const picker = createDatePicker(display, hidden, function () {
                updateRanges();
                load(buildUrl(), true);
            });
            picker.setValue(hidden.value);
            pickers[hidden.name] = picker;
        });
        updateRanges();

        // Apply button / Enter key
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            load(buildUrl(), true);
        });

        // Dropdowns apply as soon as they change
        form.querySelectorAll('.js-al-auto').forEach(function (el) {
            el.addEventListener('change', function () { load(buildUrl(), true); });
        });

        // Reset button: clear every field, then reload the list
        form.addEventListener('click', function (e) {
            const reset = e.target.closest('.al-filter-actions a');
            if (!reset) return;
            e.preventDefault();
            Array.from(form.elements).forEach(function (el) {
                if (el.name) el.value = '';
            });
            refreshCalendars();
            load(buildUrl(), true);
        });

        // Back / Forward buttons
        window.addEventListener('popstate', function () {
            syncFormFromUrl();
            load(window.location.href, false);
        });
    }

    // Previous / Next
    page.addEventListener('click', function (e) {
        const link = e.target.closest('.al-pagination a');
        if (!link) return;
        e.preventDefault();
        load(link.href, true);
    });

    // ---------- Detail modal ----------
    const modalEl = $('alDetailModal');

    const isObj = (v) => v !== null && typeof v === 'object' && !Array.isArray(v);

    // Everything is written with textContent, so log data can never inject HTML.
    function fmt(v) {
        if (v === null || v === undefined || v === '') return '—';
        if (typeof v === 'object') return JSON.stringify(v);
        return String(v);
    }

    function cell(text, cls) {
        const td = document.createElement('td');
        td.textContent = text;
        if (cls) td.className = cls;
        return td;
    }

    function fillChanges(oldV, newV) {
        const body = $('alChangesBody');
        body.innerHTML = '';

        const o = isObj(oldV) ? oldV : {};
        const n = isObj(newV) ? newV : {};
        const keys = Array.from(new Set(Object.keys(o).concat(Object.keys(n))));

        keys.forEach(function (key) {
            if (JSON.stringify(o[key]) === JSON.stringify(n[key])) return; // only fields that changed
            const tr = document.createElement('tr');
            tr.appendChild(cell(key.replace(/_/g, ' ')));
            tr.appendChild(cell(fmt(o[key]), key in o ? 'al-before' : ''));
            tr.appendChild(cell(fmt(n[key]), key in n ? 'al-after' : ''));
            body.appendChild(tr);
        });

        $('alChangesSection').hidden = body.children.length === 0;
    }

    function fillContext(ctx) {
        const body = $('alContextBody');
        body.innerHTML = '';

        if (isObj(ctx)) {
            Object.keys(ctx).forEach(function (key) {
                if (ctx[key] === null || ctx[key] === undefined || ctx[key] === '') return;
                const tr = document.createElement('tr');
                tr.appendChild(cell(key.replace(/_/g, ' ')));
                tr.appendChild(cell(Array.isArray(ctx[key]) ? ctx[key].join(', ') : fmt(ctx[key])));
                body.appendChild(tr);
            });
        }

        $('alContextSection').hidden = body.children.length === 0;
    }

    function open(log) {
        $('alDetailAction').textContent = log.action || '';
        $('alDetailDesc').textContent = log.description || 'No description.';
        $('alDetailWhen').textContent = log.when || '—';
        $('alDetailActor').textContent = (log.actor || '—') + (log.role ? ' (' + log.role + ')' : '');
        $('alDetailTarget').textContent = log.target || '—';
        $('alDetailIp').textContent = log.ip || '—';
        $('alDetailUa').textContent = log.ua || '—';

        fillChanges(log.old, log.new);
        fillContext(log.context);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // Listens on the page (not the table), because the table is replaced on every filter change
    if (modalEl && typeof bootstrap !== 'undefined') {
        page.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-al-view');
            if (!btn) return;
            try {
                open(JSON.parse(btn.dataset.log || '{}'));
            } catch (err) {
                console.error('Could not read this log entry', err);
            }
        });
    }
})();