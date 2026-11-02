// Admin notifications page: filters, pagination, at loader

document.addEventListener('DOMContentLoaded', function () {
    var card = document.getElementById('anfCard');
    if (!card) return;

    var list = document.getElementById('anfList');
    var items = Array.from(list.querySelectorAll('.anf-item'));
    var loader = document.getElementById('anfLoader');
    var emptyBox = document.getElementById('anfEmpty');
    var pagination = document.getElementById('anfPagination');
    var readTabs = document.getElementById('anfReadTabs');
    var typeChips = document.getElementById('anfTypeChips');
    var navLoader = document.getElementById('anfNavLoader');

    var pageSize = parseInt(card.dataset.pageSize, 10) || 10;

    var readFilter = 'all';
    var typeFilter = 'all';
    var currentPage = 1;

    // ---------- Loader ----------
    function showLoader() {
        loader.classList.add('is-visible');
    }

    function hideLoader() {
        loader.classList.remove('is-visible');
    }

    // Ipakita ang loader sandali, tapos i-render ulit ang list
    function reloadList() {
        showLoader();
        setTimeout(function () {
            render();
            hideLoader();
        }, 250);
    }

    // ---------- Filter ----------
    function getFilteredItems() {
        return items.filter(function (item) {
            var matchRead = readFilter === 'all' || item.dataset.read === '0';
            var matchType = typeFilter === 'all' || item.dataset.category === typeFilter;
            return matchRead && matchType;
        });
    }

    // ---------- Render ----------
    function render() {
        var filtered = getFilteredItems();
        var totalPages = Math.ceil(filtered.length / pageSize);

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        var start = (currentPage - 1) * pageSize;
        var end = start + pageSize;

        // Itago muna lahat, tapos ipakita lang yung nasa page na ito
        items.forEach(function (item) {
            item.style.display = 'none';
        });

        filtered.slice(start, end).forEach(function (item) {
            item.style.display = 'flex';
        });

        emptyBox.style.display = filtered.length === 0 ? '' : 'none';

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        pagination.innerHTML = '';

        // Isang page lang, hindi na kailangan ng pagination
        if (totalPages <= 1) return;

        addPageButton('‹', currentPage - 1, currentPage === 1);

        for (var p = 1; p <= totalPages; p++) {
            var nearCurrent = Math.abs(p - currentPage) <= 1;

            if (p === 1 || p === totalPages || nearCurrent) {
                addPageButton(String(p), p, false, p === currentPage);
            } else if (p === currentPage - 2 || p === currentPage + 2) {
                var dots = document.createElement('span');
                dots.className = 'anf-page-dots';
                dots.textContent = '…';
                pagination.appendChild(dots);
            }
        }

        addPageButton('›', currentPage + 1, currentPage === totalPages);
    }

    function addPageButton(label, page, disabled, active) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'anf-page-btn' + (active ? ' is-active' : '');
        btn.textContent = label;
        btn.dataset.page = page;
        btn.disabled = disabled;
        pagination.appendChild(btn);
    }

    // ---------- Events ----------
    readTabs.addEventListener('click', function (e) {
        var tab = e.target.closest('.anf-tab');
        if (!tab) return;

        readTabs.querySelectorAll('.anf-tab').forEach(function (t) {
            t.classList.remove('is-active');
        });
        tab.classList.add('is-active');

        readFilter = tab.dataset.read;
        currentPage = 1;
        reloadList();
    });

    typeChips.addEventListener('click', function (e) {
        var chip = e.target.closest('.anf-chip');
        if (!chip) return;

        typeChips.querySelectorAll('.anf-chip').forEach(function (c) {
            c.classList.remove('is-active');
        });
        chip.classList.add('is-active');

        typeFilter = chip.dataset.type;
        currentPage = 1;
        reloadList();
    });

    pagination.addEventListener('click', function (e) {
        var btn = e.target.closest('.anf-page-btn');
        if (!btn || btn.disabled) return;

        currentPage = parseInt(btn.dataset.page, 10);
        reloadList();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Pag nag-click ng notification, ipakita ang loader habang nagre-redirect
    list.addEventListener('click', function (e) {
        var link = e.target.closest('.anf-item');
        if (!link) return;

        navLoader.classList.add('is-visible');
    });

    // Kapag bumalik gamit ang Back button, itago ulit ang loader
    window.addEventListener('pageshow', function () {
        navLoader.classList.remove('is-visible');
    });

    // ---------- Simula ----------
    render();
});