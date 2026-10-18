document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('rptFilterForm');
    if (!form) return;

    const monthSelect = form.querySelector('select[name="month"]');
    const yearSelect = form.querySelector('select[name="year"]');
    const exportBtn = form.querySelector('.rpt-export-btn');
    const page = document.querySelector('.rpt-page');

    // Para ma-ignore ang lumang request kapag mabilis magpalit ng month/year
    let requestCount = 0;

    // Nakaimbak dito ang mga na-load na report (key: "year-month")
    // Kapag binalikan ang same month, instant na, walang request
    const cache = {};
    const CACHE_SECONDS = 300; // 5 minuto

    function buildUrl() {
        return form.getAttribute('action')
            + '?month=' + encodeURIComponent(monthSelect.value)
            + '&year=' + encodeURIComponent(yearSelect.value);
    }

    // Update ang Export PDF link para sa napiling month/year
    function updateExportLink() {
        if (!exportBtn) return;
        const base = exportBtn.getAttribute('href').split('?')[0];
        exportBtn.href = base
            + '?year=' + encodeURIComponent(yearSelect.value)
            + '&month=' + encodeURIComponent(monthSelect.value);
    }

    function showReport(html, url) {
        page.innerHTML = html;
        page.style.opacity = '1';
        updateExportLink();
        history.replaceState(null, '', url);
    }

    function loadReport() {
        const url = buildUrl();
        const key = yearSelect.value + '-' + monthSelect.value;
        const thisRequest = ++requestCount;

        // Kung nasa cache pa at hindi pa lumipas ang 5 minuto, gamitin na agad
        const saved = cache[key];
        if (saved && (Date.now() - saved.time) < CACHE_SECONDS * 1000) {
            showReport(saved.html, url);
            return;
        }

        // Kaunting fade habang naglo-load
        page.style.transition = 'opacity 0.15s';
        page.style.opacity = '0.5';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Request failed');
                return res.text();
            })
            .then(function (html) {
                // Kung may mas bago nang request, huwag na itong i-apply
                // (pero i-save pa rin sa cache para mabilis next time)
                cache[key] = { html: html, time: Date.now() };
                if (thisRequest !== requestCount) return;

                // Ang response ay laman na lang ng report (cards, chart, tables)
                showReport(html, url);
            })
            .catch(function (err) {
                console.error('[admin-reports.js] load error:', err);
                // Kung pumalya, bumalik sa normal na page refresh
                window.location.href = url;
            });
    }

    monthSelect.addEventListener('change', loadReport);
    yearSelect.addEventListener('change', loadReport);

    // Iwas full page submit (hal. Enter key)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        loadReport();
    });

    // Search sa Approved Applications table
    // Nasa 'page' nakalagay ang listener, kaya gumagana pa rin
    // kahit napalitan na ang laman ng page ng bagong month
    page.addEventListener('input', function (e) {
        if (!e.target.classList.contains('rpt-search-input')) return;

        const query = e.target.value.trim().toLowerCase();
        const rows = page.querySelectorAll('.rpt-approved-row');
        let shown = 0;

        rows.forEach(function (row) {
            const match = row.textContent.toLowerCase().includes(query);
            row.style.display = match ? '' : 'none';
            if (match) shown++;
        });

        // Ipakita ang "No results" kung may rows pero walang tumugma
        const noMatch = page.querySelector('.rpt-no-match');
        if (noMatch) {
            noMatch.style.display = (rows.length > 0 && shown === 0) ? '' : 'none';
        }
    });
});