<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="x-ua-compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Overdue Payment Detail - Admin</title>
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/admin/images/favicon.ico') }}" />
        <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/admin/vendors/css/vendors.min.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/admin/css/theme.min.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/admin/css/logout-modal.css') }}">
        {{-- Borrower header now reuses the shared section-card / borrower-avatar
             components from loan-details.css (same ones Completed Loan Detail
             uses), so that stylesheet needs to load here too. --}}
        <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
        {{-- Standalone stylesheet for this page only — not shared with loan-details.css --}}
        <link rel="stylesheet" href="{{ asset('assets/admin/css/overdue-payment-detail.css') }}">
    </head>
    <body>
        <nav class="nxl-navigation">
            <div class="navbar-wrapper">
                <div class="m-header">
                    <a href="{{ route('admin.dashboard') }}" class="b-brand"><h3 class="fst-italic">Ran Serenity</h3></a>
                </div>
                <div class="navbar-content">
                    <ul class="nxl-navbar">
                        <li class="nxl-item">
                            <a href="{{ route('admin.dashboard') }}" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-airplay"></i></span>
                                <span class="nxl-mtext">Dashboard</span>
                            </a>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-user-check"></i></span>
                                <span class="nxl-mtext">Borrower Verification</span>
                                @if($pendingCount > 0)
                                    <span class="badge bg-danger ms-auto">{{ $pendingCount }}</span>
                                @endif
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification') }}">Pending</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'approved']) }}">Approved</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'rejected']) }}">Rejected</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrower-verification', ['status' => 'on_hold']) }}">On Hold</a></li>
                            </ul>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Loan Applications</span>
                                @if(($pendingLoanCount ?? 0) > 0)
                                    <span class="badge bg-danger ms-auto">{{ $pendingLoanCount }}</span>
                                @endif
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications') }}">Pending Applications</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'approved']) }}">Approved</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'rejected']) }}">Rejected</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.loan-applications', ['status' => 'on_hold']) }}">On Hold</a></li>
                            </ul>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-users"></i></span>
                                <span class="nxl-mtext">Borrowers</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.penalty-tracking') }}">Penalty Tracking</a></li>
                            </ul>
                        </li>
                        <li class="nxl-item nxl-hasmenu nxl-trigger active">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                                <span class="nxl-mtext">Active Loans</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                                <li class="nxl-item" data-current="true"><a class="nxl-link" href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.completed-loans') }}">Completed Loans</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="#">Defaulted</a></li>
                            </ul>
                        </li>
                        <li class="nxl-item">
                            <a href="javascript:void(0);" onclick="confirmLogout()" class="nxl-link text-danger">
                                <span class="nxl-micon"><i class="feather-log-out"></i></span>
                                <span class="nxl-mtext">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <header class="nxl-header">
            <div class="header-wrapper">
                <div class="header-left d-flex align-items-center gap-4">
                    <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                        <div class="hamburger hamburger--arrowturn"><div class="hamburger-box"><div class="hamburger-inner"></div></div></div>
                    </a>
                    <div class="nxl-navigation-toggle">
                        <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                        <a href="javascript:void(0);" id="menu-expend-button" style="display:none"><i class="feather-arrow-right"></i></a>
                    </div>
                </div>
                <div class="header-right ms-auto">
                    <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none"><i class="feather-sun"></i></a>
                    </div>
                </div>
            </div>
        </header>

        <main class="nxl-container">
            <div class="nxl-content">
                <div class="page-header">
                    <div class="page-header-left d-flex align-items-center">
                        <div class="page-header-title"><h5 class="m-b-10">Overdue Payment Detail</h5></div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                            <li class="breadcrumb-item">#{{ $applicationId }}</li>
                        </ul>
                    </div>
                    <div class="page-header-right ms-auto">
                        <a href="{{ route('admin.overdue-payments') }}" class="btn btn-light">Back</a>
                    </div>
                </div>

                <div class="main-content">
                    @if(session('success'))
                        <div class="alert alert-success mb-3">{{ session('success') }}</div>
                    @endif

                    @php
                        $fullName = trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? ''));
                    @endphp

                    <div class="opd-content">

                        {{-- ============ 1. BORROWER HEADER ============
                             Same shell as Completed Loan Detail's header —
                             section-card + borrower-avatar, full email shown
                             (not truncated), badge + "N days overdue" meta
                             instead of the "Fully paid on ..." meta. --}}
                        <div class="section-card opd-borrower-header mb-3">
                            <div class="borrower-avatar">
                                {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                            </div>
                            <div class="opd-borrower-right">
                                <h6 class="fw-bold mb-1">{{ $fullName !== '' ? $fullName : '—' }}</h6>
                                <p class="text-muted mb-0" style="font-size:0.83rem;">{{ $app->email }}</p>
                                <div class="opd-borrower-meta">
                                    <span class="badge badge-rejected px-3 py-2">Overdue</span>
                                    <span class="opd-days-overdue-text">{{ $daysLate }} day{{ $daysLate == 1 ? '' : 's' }} overdue</span>
                                </div>
                            </div>
                        </div>

                        {{-- ============ 2 & 3. OVERDUE PAYMENT SUMMARY + ACTIONS ============ --}}
                        <div class="opd-card opd-overdue-card">
                            <div class="opd-card-title"><i class="feather-alert-triangle"></i> Overdue Payment</div>

                            @if($oldestOverdue)
                                <div class="opd-overdue-grid">
                                    <div class="opd-field">
                                        <div class="opd-field-label">Installment Number</div>
                                        <div class="opd-field-value">#{{ $oldestOverdue->installment_no }}</div>
                                    </div>
                                    <div class="opd-field">
                                        <div class="opd-field-label">Due Date</div>
                                        <div class="opd-field-value">{{ \Carbon\Carbon::parse($oldestOverdue->due_date)->format('M d, Y') }}</div>
                                    </div>
                                    <div class="opd-field">
                                        <div class="opd-field-label">Days Overdue</div>
                                        <div class="opd-field-value is-danger">{{ $daysLate }} days</div>
                                    </div>
                                    <div class="opd-field">
                                        <div class="opd-field-label">Penalty</div>
                                        <div class="opd-field-value is-orange">₱{{ number_format($currentPenalty, 2) }}</div>
                                    </div>
                                    <div class="opd-field is-full">
                                        <div class="opd-field-label">Amount Due</div>
                                        <div class="opd-field-value">₱{{ number_format($amountDue, 2) }}</div>
                                    </div>
                                </div>

                                <div class="opd-outstanding-row">
                                    <div class="opd-outstanding-label">Total Outstanding</div>
                                    <div class="opd-outstanding-value">₱{{ number_format($totalOutstanding, 2) }}</div>
                                </div>

                                <div class="opd-actions">
                                    <button type="button" class="opd-btn opd-btn-mark-paid" data-bs-toggle="modal" data-bs-target="#opdConfirmPaidModal">
                                        <i class="feather-check"></i> Mark as Paid
                                    </button>
                                    <button type="button" class="opd-btn opd-btn-waive" data-bs-toggle="modal" data-bs-target="#opdWaiveModal" {{ !$penaltyId ? 'disabled' : '' }}>
                                        <i class="feather-slash"></i> Waive Penalty
                                    </button>
                                </div>
                            @else
                                <p class="text-muted mb-0" style="font-size:0.85rem;">No overdue installment found for this loan.</p>
                            @endif
                        </div>

                        {{-- ============ 4. PAYMENT SCHEDULE ============ --}}
                        <div class="opd-card">
                            <div class="opd-card-title"><i class="feather-calendar"></i> Payment Schedule</div>
                            <div class="opd-table-scroll">
                                <table class="opd-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Due Date</th>
                                            <th>Amount Due</th>
                                            <th>Paid On</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($paymentSchedule as $s)
                                            @php
                                                $isPaid = !is_null($s->paid_at);
                                                $isOverdue = !$isPaid && \Carbon\Carbon::parse($s->due_date)->isPast();
                                                $badgeClass = $isPaid ? 'opd-badge-paid' : ($isOverdue ? 'opd-badge-overdue' : 'opd-badge-upcoming');
                                                $badgeLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : 'Upcoming');
                                                $isCurrentRow = $oldestOverdue && $s->id === $oldestOverdue->id;
                                            @endphp
                                            <tr class="{{ $isCurrentRow ? 'is-current-overdue' : '' }}">
                                                <td data-label="#">{{ $s->installment_no }}</td>
                                                <td data-label="Due Date">{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                                <td data-label="Amount Due">₱{{ number_format($s->amount_due, 2) }}</td>
                                                <td data-label="Paid On">{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                                <td data-label="Status"><span class="opd-badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ============ 5. LOAN SUMMARY (compact, secondary) ============ --}}
                        <div class="opd-card opd-summary-card">
                            <div class="opd-card-title"><i class="feather-file-text"></i> Loan Summary</div>
                            <div class="opd-summary-grid">
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Application ID</span>
                                    <span class="opd-summary-value">#{{ $app->application_id }}</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Loan Amount</span>
                                    <span class="opd-summary-value">₱{{ number_format($app->loan_amount, 2) }}</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Term</span>
                                    <span class="opd-summary-value">{{ $app->loan_term }} months</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Interest Rate</span>
                                    <span class="opd-summary-value">5% / month</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Total Interest</span>
                                    <span class="opd-summary-value">₱{{ number_format($app->total_interest ?? 0, 2) }}</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Total to Pay</span>
                                    <span class="opd-summary-value">₱{{ number_format($app->total_amount ?? 0, 2) }}</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Monthly Payment</span>
                                    <span class="opd-summary-value">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Purpose</span>
                                    <span class="opd-summary-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</span>
                                </div>
                                <div class="opd-summary-row">
                                    <span class="opd-summary-label">Applied</span>
                                    <span class="opd-summary-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <footer class="footer">
                <p class="text-center">&copy; {{ date('Y') }} Ran Serenity Lending System</p>
            </footer>
        </main>

        {{-- ============ CONFIRM: Mark as Paid ============ --}}
        <div class="modal fade opd-modal" id="opdConfirmPaidModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="opd-modal-icon is-confirm"><i class="feather-check-circle"></i></div>
                        <div class="opd-modal-title">Mark Payment as Paid?</div>
                        <div class="opd-modal-desc">Are you sure you want to mark this overdue payment as paid? This action cannot be undone.</div>
                    </div>
                    <div class="modal-footer">
                          <button type="button" class="opd-btn opd-btn-mark-paid" id="opdConfirmPaidBtn">
                            <i class="feather-check"></i> Yes, Mark as Paid
                        </button>
                        <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Cancel</button>
                      
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ WAIVE PENALTY ============ --}}
        <div class="modal fade opd-modal" id="opdWaiveModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="opd-modal-icon is-confirm"><i class="feather-slash"></i></div>
                        <div class="opd-modal-title">Waive Penalty</div>
                        <div class="opd-modal-desc">Please provide a reason for waiving this penalty.</div>
                        <textarea id="opdWaiveReason" class="opd-modal-textarea" rows="3" placeholder="Reason for waiving..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="opd-btn opd-btn-mark-paid" id="opdConfirmWaiveBtn">
                            <i class="feather-check"></i> Confirm Waive
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ SUCCESS (shared by both actions) ============ --}}
        <div class="modal fade opd-modal" id="opdSuccessModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="opd-modal-icon is-success"><i class="feather-check-circle"></i></div>
                        <div class="opd-modal-title" id="opdSuccessTitle">Success</div>
                        <div class="opd-modal-desc" id="opdSuccessDesc"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="opd-btn opd-btn-mark-paid" id="opdSuccessOkBtn">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('assets/admin/vendors/js/vendors.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/common-init.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/theme-customizer-init.min.js') }}"></script>
        <script src="{{ asset('functions/logout-modal.js') }}"></script>

        <script>
            (function () {
                const csrfToken = '{{ csrf_token() }}';
                @if($oldestOverdue)
                const markPaidUrl = '{{ route('admin.installment-mark-paid', $oldestOverdue->id) }}';
                @else
                const markPaidUrl = null;
                @endif
                @if($penaltyId)
                const waivePenaltyUrl = '{{ route('admin.penalty-waive', $penaltyId) }}';
                @else
                const waivePenaltyUrl = null;
                @endif

                const confirmPaidModalEl = document.getElementById('opdConfirmPaidModal');
                const waiveModalEl = document.getElementById('opdWaiveModal');
                const successModalEl = document.getElementById('opdSuccessModal');
                const successTitleEl = document.getElementById('opdSuccessTitle');
                const successDescEl = document.getElementById('opdSuccessDesc');

                function showSuccess(title, desc) {
                    successTitleEl.textContent = title;
                    successDescEl.textContent = desc;
                    bootstrap.Modal.getOrCreateInstance(successModalEl).show();
                }

                document.getElementById('opdConfirmPaidBtn').addEventListener('click', function () {
                    if (!markPaidUrl) return;

                    fetch(markPaidUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            bootstrap.Modal.getInstance(confirmPaidModalEl).hide();
                            if (data.success) {
                                showSuccess('Payment Marked as Paid', 'The overdue payment has been successfully recorded as paid.');
                            }
                        })
                        .catch(function () {
                            bootstrap.Modal.getInstance(confirmPaidModalEl).hide();
                        });
                });

                document.getElementById('opdConfirmWaiveBtn').addEventListener('click', function () {
                    if (!waivePenaltyUrl) return;

                    const reason = document.getElementById('opdWaiveReason').value.trim();
                    if (!reason) {
                        document.getElementById('opdWaiveReason').focus();
                        return;
                    }

                    fetch(waivePenaltyUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ waived_reason: reason }),
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            bootstrap.Modal.getInstance(waiveModalEl).hide();
                            if (data.success) {
                                showSuccess('Penalty Waived', 'The penalty has been successfully waived.');
                            }
                        })
                        .catch(function () {
                            bootstrap.Modal.getInstance(waiveModalEl).hide();
                        });
                });

                // Reload once the success modal is dismissed, so the page
                // reflects the updated payment/penalty status.
                successModalEl.addEventListener('hidden.bs.modal', function () {
                    window.location.reload();
                });
                document.getElementById('opdSuccessOkBtn').addEventListener('click', function () {
                    bootstrap.Modal.getInstance(successModalEl).hide();
                });
            })();
        </script>
    </body>
    </html>