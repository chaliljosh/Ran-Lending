// Payment Calendar — prev/next month navigation (AJAX partial swap)
// Lives on: borrower.dashboard (only rendered when #bwl-dash-calendar-card exists)
document.addEventListener('DOMContentLoaded', function () {
    const calCard = document.getElementById('bwl-dash-calendar-card');
    if (!calCard) return;

    const calContent = document.getElementById('bwl-cal-content');
    const baseUrl = calCard.dataset.calendarUrl;

    calCard.addEventListener('click', function (e) {
        const link = e.target.closest('.bwl-dash-cal-nav');
        if (!link) return;
        e.preventDefault();

        const month = link.dataset.month;

        fetch(`${baseUrl}?cal_month=${month}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Calendar request failed: ' + res.status);
            return res.text();
        })
        .then(html => {
            calContent.innerHTML = html;
            const url = new URL(window.location.href);
            url.searchParams.set('cal_month', month);
            window.history.replaceState({}, '', url);
        })
        .catch(() => {
            window.location.href = `${window.location.pathname}?cal_month=${month}`;
        });
    });
});