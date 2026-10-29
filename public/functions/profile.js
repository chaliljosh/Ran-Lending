(function () {
    const wrap = document.getElementById('profileLoanHistory');
    const modalEl = document.getElementById('ldLoanModal');
    if (!wrap || !modalEl) return;

    const statusClasses = ['bg-warning', 'bg-danger', 'bg-primary', 'bg-info', 'bg-success', 'bg-secondary', 'bg-ongoing'];

    const scheduleStatus = {
        paid: ['plm-pill--paid', 'Paid'],
        overdue: ['plm-pill--overdue', 'Overdue'],
        upcoming: ['plm-pill--upcoming', 'Upcoming'],
        pending: ['plm-pill--upcoming', 'Upcoming']
    };

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text || '—';
    }

    function setStatus(id, cls, label) {
        const el = document.getElementById(id);
        if (!el) return;
        statusClasses.forEach(function (c) {
            el.classList.remove(c);
        });
        if (cls) el.classList.add(cls);
        el.textContent = label || '';
    }

    function renderSchedule(rows) {
        const body = document.getElementById('ldScheduleBody');
        if (!body) return;
        body.innerHTML = '';

        if (!rows.length) {
            const emptyRow = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'plm-empty';
            cell.textContent = 'No repayment schedule yet.';
            emptyRow.appendChild(cell);
            body.appendChild(emptyRow);
            return;
        }

        rows.forEach(function (r) {
            const tr = document.createElement('tr');
            const st = scheduleStatus[r.status] || scheduleStatus.upcoming;

            [r.no, r.due, r.amount, r.principal, r.interest].forEach(function (value) {
                const td = document.createElement('td');
                td.textContent = value;
                tr.appendChild(td);
            });

            const tdStatus = document.createElement('td');
            const pill = document.createElement('span');
            pill.className = 'plm-pill ' + st[0];
            pill.textContent = st[1];
            tdStatus.appendChild(pill);
            tr.appendChild(tdStatus);

            body.appendChild(tr);
        });
    }

    function openLoanModal(row) {
        const d = row.dataset;

        let schedule = [];
        try {
            schedule = JSON.parse(d.schedule || '[]');
        } catch (err) {
            schedule = [];
        }

        const done = parseInt(d.paidCount || '0', 10);
        const total = parseInt(d.installments || '0', 10);
        const pct = total > 0 ? Math.min(Math.round((done / total) * 100), 100) : 0;

        setText('ldLoanId', d.loanId);
        setStatus('ldLoanStatus', d.statusClass, d.statusLabel);
        setStatus('ldOvStatus', d.statusClass, d.statusLabel);

        setText('ldLoanApplied', d.applied);
        setText('ldLoanAmount', d.amount);
        setText('ldLoanTerm', d.term);
        setText('ldLoanMonthly', d.monthly);

        setText('ldOvId', d.loanId);
        setText('ldOvApplied', d.applied);
        setText('ldOvReleased', d.released);
        setText('ldOvPrincipal', d.amount);
        setText('ldOvFee', d.fee);
        setText('ldOvTerm', d.term);

        setText('ldPaid', d.paid);
        setText('ldRemaining', d.remaining);
        setText('ldNextDue', d.nextDue);
        setText('ldProgressText', done + ' of ' + total + ' installments');

        const bar = document.getElementById('ldProgressBar');
        if (bar) bar.style.width = pct + '%';

        renderSchedule(schedule);

        const open = document.getElementById('ldLoanOpen');
        if (open) open.href = d.href || '#';

        const scheduleLink = document.getElementById('ldScheduleLink');
        if (scheduleLink) scheduleLink.href = d.href || '#';

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (d.href) {
            window.location.href = d.href;
        }
    }

    wrap.addEventListener('click', function (e) {
        const row = e.target.closest('tr.bwl-ld-row-link');
        if (!row) return;

        const sel = window.getSelection();
        if (!e.target.closest('[data-ld-view-loan]') && sel && String(sel).length > 0) return;

        openLoanModal(row);
    });
})();