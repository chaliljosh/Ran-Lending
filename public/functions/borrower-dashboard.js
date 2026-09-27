document.addEventListener('DOMContentLoaded', function () {
    const calCard = document.getElementById('bwl-dash-calendar-card');
    if (!calCard) return;

    const calContent = document.getElementById('bwl-cal-content');
    const dataUrl = calCard.dataset.calendarDataUrl;

    let schedule = [];
    let currentMonth = new Date();
    currentMonth.setDate(1);

    const monthNames = ['January','February','March','April','May','June',
        'July','August','September','October','November','December'];

    function renderCalendar() {
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();

        const firstDow = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const today = new Date();
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;
        const todayDate = today.getDate();

        const daysWithPayments = new Set(
            schedule
                .filter(s => {
                    const d = new Date(s.due_date + 'T00:00:00');
                    return d.getFullYear() === year && d.getMonth() === month;
                })
                .map(s => new Date(s.due_date + 'T00:00:00').getDate())
        );

        const prevMonth = new Date(year, month - 1, 1);
        const nextMonth = new Date(year, month + 1, 1);
        const prevKey = `${prevMonth.getFullYear()}-${String(prevMonth.getMonth() + 1).padStart(2, '0')}`;
        const nextKey = `${nextMonth.getFullYear()}-${String(nextMonth.getMonth() + 1).padStart(2, '0')}`;

        let html = `
            <div class="bwl-dash-cal-month">
                <a href="#" class="bwl-dash-cal-nav" data-month="${prevKey}" aria-label="Previous month">‹</a>
                <span>${monthNames[month]} ${year}</span>
                <a href="#" class="bwl-dash-cal-nav" data-month="${nextKey}" aria-label="Next month">›</a>
            </div>
            <div class="bwl-dash-cal-grid">
        `;

        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
            html += `<div class="bwl-dash-cal-dow">${d}</div>`;
        });

        for (let i = 0; i < firstDow; i++) {
            html += `<div class="bwl-dash-cal-day is-empty"></div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = isCurrentMonth && day === todayDate;
            const hasPayment = daysWithPayments.has(day);
            html += `<div class="bwl-dash-cal-day ${isToday ? 'is-today' : ''} ${hasPayment ? 'has-payment' : ''}">${day}</div>`;
        }

        html += `</div>`;

        const nextUnpaid = schedule.find(s => !s.paid_at);
        if (nextUnpaid) {
            const due = new Date(nextUnpaid.due_date + 'T00:00:00');
            const label = due.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const amount = nextUnpaid.amount_due.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            html += `
                <div class="bwl-dash-cal-next">
                    <span class="bwl-dash-cal-dot"></span>
                    <div>
                        <div class="bwl-dash-cal-next-label">${label}</div>
                        <div class="bwl-dash-cal-next-sub">Next payment due</div>
                    </div>
                    <strong>₱${amount}</strong>
                </div>
            `;
        }

        calContent.innerHTML = html;
    }

    calCard.addEventListener('click', function (e) {
        const link = e.target.closest('.bwl-dash-cal-nav');
        if (!link) return;
        e.preventDefault();

        const [year, month] = link.dataset.month.split('-').map(Number);
        currentMonth = new Date(year, month - 1, 1);
        renderCalendar();
    });

    fetch(dataUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => {
            if (!res.ok) throw new Error('Calendar data request failed: ' + res.status);
            return res.json();
        })
        .then(data => {
            schedule = data.schedule;
            renderCalendar();
        })
        .catch(() => {
            calContent.innerHTML = '<p class="text-muted small">Unable to load calendar.</p>';
        });
});

document.addEventListener('DOMContentLoaded', function () {
    // Ripple spotlight hover effect para sa "Update my details" button
    // sa on-hold / rejected verification card (.bwl-hold-btn)
    document.querySelectorAll('.bwl-hold-btn').forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            btn.style.setProperty('--mx', `${x}px`);
            btn.style.setProperty('--my', `${y}px`);
        });
    });
});