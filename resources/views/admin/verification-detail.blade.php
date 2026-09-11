@extends('admin.layout')

@section('title', 'Borrower Details')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/verification-detail.css') }}" />
@endsection

@section('content')
    @php
        $cameFrom = request()->query('from', 'borrowers');
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Borrower Details</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                @if ($cameFrom === 'verification')
                    <li class="breadcrumb-item">
                        <a
                            href="{{ route('admin.borrower-verification', ($viewStatus ?? 'pending') !== 'pending' ? ['status' => $viewStatus] : []) }}">Borrower
                            Verification</a>
                    </li>
                @else
                    <li class="breadcrumb-item"><a href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                @endif
                <li class="breadcrumb-item">Details #{{ $detailId }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto d-flex align-items-center gap-2">
            @if ($cameFrom === 'verification')
                <a href="{{ route('admin.borrower-verification', ($viewStatus ?? 'pending') !== 'pending' ? ['status' => $viewStatus] : []) }}"
                    class="btn btn-light">
                    Back
                </a>
            @else
                <a href="{{ route('admin.borrowers') }}" class="btn btn-light">
                    Back
                </a>
            @endif
        </div>
    </div>

    <div class="main-content">
        {{-- Decision saved → toast instead of a modal --}}
        @if (session('success'))
            @php
                $decisionStatus = session('decisionStatus');
                $toastDesc = match ($decisionStatus) {
                    'approved' => 'Borrower verification approved.',
                    'rejected' => 'Borrower verification rejected.',
                    'on_hold' => 'Borrower verification placed on hold.',
                    default => 'Decision saved successfully.',
                };
            @endphp
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    window.showAdminToast('success', 'Decision Saved', @json($toastDesc));
                });
            </script>
        @endif

        {{-- ============================================
             COMPACT BORROWER HEADER
             (Profile + Overview combined into one card)
             Layout: left = avatar/name/id/email/badges (stacked)
                     right = View Active Loan button (top) + stats row (bottom)
        ============================================ --}}
        @php
            $initials = strtoupper(substr($d->first_name ?? 'B', 0, 1) . substr($d->last_name ?? 'R', 0, 1));
            // Optional loan-overview stats — populated by the controller via getBorrowerLoanStats().
            $loanStats = $loanStats ?? [];
            $borrowerPhotoUrl = $borrowerProfilePicture ?? null;
        @endphp
        <div class="borrower-summary-card">
            <div class="bsc-left">
                <div class="borrower-avatar {{ empty($borrowerPhotoUrl) ? 'js-borrower-avatar' : '' }}">
                    @if (!empty($borrowerPhotoUrl))
                        <img src="{{ $borrowerPhotoUrl }}" alt="{{ $d->first_name }} {{ $d->last_name }}">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div class="borrower-summary-info">
                    <h5>{{ $d->first_name }} {{ $d->last_name }}</h5>
                    <div class="borrower-summary-meta">
                        <span><strong>Borrower ID:</strong> #{{ $detailId }}</span>
                        <span><strong>Email:</strong> {{ $d->reg_email }}</span>
                    </div>
                    <div class="bsc-badges">
                        @if ($d->status === 'approved')
                            <span class="badge badge-approved">Approved</span>
                        @elseif($d->status === 'rejected')
                            <span class="badge badge-rejected">Rejected</span>
                        @elseif($d->status === 'on_hold')
                            <span class="badge badge-onhold">On Hold</span>
                        @else
                            <span class="badge badge-pending">Pending</span>
                        @endif

                        @if (!empty($standingLabel))
                            <span class="abd-standing is-{{ $standing ?? 'good' }}">{{ $standingLabel }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="bsc-right">
                @if (($loanStats['active'] ?? 0) > 0 && !empty($loanStats['active_application_id']))
                    <a href="{{ route('admin.active-borrower-detail', $loanStats['active_application_id']) }}"
                        class="btn-view-active-loan">
                        View Active Loan <i class="feather-arrow-right"></i>
                    </a>
                @endif
                <div class="bsc-stats">
                    <div class="bsc-stat">
                        <span class="bsc-stat-value">{{ $loanStats['total'] ?? '—' }}</span>
                        <span class="bsc-stat-label">Total Loans</span>
                    </div>
                    <div class="bsc-stat">
                        <span class="bsc-stat-value">{{ $loanStats['active'] ?? '—' }}</span>
                        <span class="bsc-stat-label">Active</span>
                    </div>
                    <div class="bsc-stat">
                        <span class="bsc-stat-value">{{ $loanStats['completed'] ?? '—' }}</span>
                        <span class="bsc-stat-label">Completed</span>
                    </div>
                    <div class="bsc-stat">
                        <span class="bsc-stat-value">{{ $loanStats['overdue'] ?? '—' }}</span>
                        <span class="bsc-stat-label">Overdue</span>
                    </div>
                </div>

                {{-- Opens the Review Borrower Verification modal. Compact,
                     outline-style trigger — matches the muted button
                     style used elsewhere in the admin (e.g. btn-la-outline
                     on Loan Applications) rather than a large filled CTA. --}}
                @if ($d->status !== 'rejected' && $d->status !== 'approved')
                    <button type="button" class="btn-review-decision" data-bs-toggle="modal"
                        data-bs-target="#decisionModal">
                        Make Decision
                    </button>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">

                <!-- Personal Information -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="section-title">Personal Information</div>
                        <div class="info-row">
                            <div class="info-item"><label>Last Name</label><span>{{ $d->last_name }}</span></div>
                            <div class="info-item"><label>First Name</label><span>{{ $d->first_name }}</span></div>
                            <div class="info-item"><label>Middle Name</label><span>{{ $d->middle_name }}</span></div>
                            <div class="info-item"><label>Suffix</label><span>{{ $d->suffix ?? '—' }}</span></div>
                            <div class="info-item">
                                <label>Birthday</label><span>{{ $d->birthday ? \Carbon\Carbon::parse($d->birthday)->format('F d, Y') : '—' }}</span>
                            </div>
                            <div class="info-item">
                                <label>Age</label><span>{{ $d->birthday ? \Carbon\Carbon::parse($d->birthday)->age . ' yrs old' : '—' }}</span>
                            </div>
                            <div class="info-item"><label>Gender</label><span>{{ ucfirst($d->gender ?? '—') }}</span></div>
                            <div class="info-item"><label>Civil
                                    Status</label><span>{{ ucfirst($d->civil_status ?? '—') }}</span></div>
                            <div class="info-item"><label>Citizenship</label><span>{{ $d->citizenship ?? '—' }}</span>
                            </div>
                            <div class="info-item"><label>Cellphone</label><span>{{ $d->cellphone_num ?? '—' }}</span>
                            </div>
                            <div class="info-item"><label>Email</label><span>{{ $d->reg_email }}</span></div>
                            <div class="info-item highlight"><label>Monthly
                                    Income</label><span>₱{{ number_format($d->monthly_income ?? 0, 2) }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Birth Place -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="section-title">Birth Place</div>
                        <div class="info-row">
                            <div class="info-item"><label>Country</label><span>{{ $d->birth_country ?? '—' }}</span></div>
                            <div class="info-item"><label>Province</label><span>{{ $d->birth_province ?? '—' }}</span>
                            </div>
                            <div class="info-item"><label>City</label><span>{{ $d->birth_city ?? '—' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Present Address -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="section-title">Present Address</div>
                        <div class="info-row">
                            <div class="info-item"><label>Region</label><span>{{ $d->region ?? '—' }}</span></div>
                            <div class="info-item"><label>Province</label><span>{{ $d->province ?? '—' }}</span></div>
                            <div class="info-item"><label>City</label><span>{{ $d->city ?? '—' }}</span></div>
                            <div class="info-item"><label>Barangay</label><span>{{ $d->barangay ?? '—' }}</span></div>
                            <div class="info-item"><label>Zipcode</label><span>{{ $d->zipcode ?? '—' }}</span></div>
                            <div class="info-item"><label>Home Address</label><span>{{ $d->home_address ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Information -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="section-title">Work Information</div>
                        <div class="info-row">
                            <div class="info-item"><label>Employment
                                    Status</label><span>{{ ucfirst(str_replace('_', ' ', $d->employment_status ?? '—')) }}</span>
                            </div>
                            <div class="info-item">
                                <label>Company/Business</label><span>{{ $d->company_name ?? '—' }}</span>
                            </div>
                            <div class="info-item"><label>Position</label><span>{{ $d->position ?? '—' }}</span></div>
                            <div class="info-item"><label>Years in
                                    Company</label><span>{{ $d->years_in_company ?? '—' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Co-Maker Information -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="section-title">Co-Maker Information</div>
                        <div class="info-row">
                            <div class="info-item"><label>Last Name</label><span>{{ $d->co_last ?? '—' }}</span></div>
                            <div class="info-item"><label>First Name</label><span>{{ $d->co_first ?? '—' }}</span></div>
                            <div class="info-item"><label>Middle Name</label><span>{{ $d->co_middle ?? '—' }}</span></div>
                            <div class="info-item">
                                <label>Relationship</label><span>{{ ucfirst($d->relationship ?? '—') }}</span>
                            </div>
                            <div class="info-item"><label>Contact Number</label><span>{{ $d->co_contact ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Valid ID / Attached ID -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="section-title">Valid ID</div>
                        <div class="info-row">
                            <div class="info-item"><label>ID Type</label><span>{{ $d->id_type ?? '—' }}</span></div>
                            <div class="info-item"><label>ID Number</label><span>{{ $d->id_number ?? '—' }}</span></div>
                        </div>

                        @if (!empty($d->id_file_path) || !empty($d->id_file_path_back))
                            <div class="mt-1 d-flex flex-wrap gap-4">
                                @if (!empty($d->id_file_path))
                                    <div>
                                        <label class="form-label fw-semibold"
                                            style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Front
                                            ID</label><br>
                                        @php $extFront = strtolower(pathinfo($d->id_file_path, PATHINFO_EXTENSION)); @endphp
                                        @if (in_array($extFront, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <span class="id-thumb-wrap"
                                                onclick="openIdViewer('{{ asset('storage/' . $d->id_file_path) }}')">
                                                <img src="{{ asset('storage/' . $d->id_file_path) }}"
                                                    alt="Valid ID — Front">
                                            </span>
                                        @else
                                            <a href="{{ asset('storage/' . $d->id_file_path) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="feather-file me-1"></i> View Front ID
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                @if (!empty($d->id_file_path_back))
                                    <div>
                                        <label class="form-label fw-semibold"
                                            style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Back
                                            ID</label><br>
                                        @php $extBack = strtolower(pathinfo($d->id_file_path_back, PATHINFO_EXTENSION)); @endphp
                                        @if (in_array($extBack, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <span class="id-thumb-wrap"
                                                onclick="openIdViewer('{{ asset('storage/' . $d->id_file_path_back) }}')">
                                                <img src="{{ asset('storage/' . $d->id_file_path_back) }}"
                                                    alt="Valid ID — Back">
                                            </span>
                                        @else
                                            <a href="{{ asset('storage/' . $d->id_file_path_back) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="feather-file me-1"></i> View Back ID
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Admin Review History (was: Admin Discussion) -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Admin Review History</h6>
                    </div>
                    <div class="card-body">

                        @if ($allReviews->count() > 0)
                            <div class="mb-4">
                                <p class="fw-semibold mb-3"
                                    style="font-size:0.75rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">
                                    Decisions</p>
                                @foreach ($allReviews as $r)
                                    @php $rInitials = strtoupper(substr($r->admin_name, 0, 2)); @endphp
                                    <div class="discussion-item">
                                        <div class="discussion-avatar">{{ $rInitials }}</div>
                                        <div class="discussion-body">
                                            <div
                                                class="discussion-card {{ $r->decision === 'approved' ? 'decision-approved' : 'decision-rejected' }}">
                                                <div class="discussion-header">
                                                    <span class="name">{{ $r->admin_name }}</span>
                                                    <span
                                                        class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-2 py-1">
                                                        {{ ucfirst($r->decision) }}
                                                    </span>
                                                </div>
                                                @if ($r->remarks)
                                                    <p class="mb-0 mt-2 text-muted" style="font-size:0.82rem;">
                                                        {{ $r->remarks }}</p>
                                                @endif
                                                <p class="mb-0 mt-1 time">
                                                    {{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y h:i A') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($comments->count() > 0)
                            <div class="mb-3">
                                <p class="fw-semibold mb-3"
                                    style="font-size:0.75rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">
                                    Comments</p>
                                @foreach ($comments as $c)
                                    @php $cInitials = strtoupper(substr($c->admin_name, 0, 2)); @endphp
                                    <div class="discussion-item">
                                        <div class="discussion-avatar {{ $c->admin_id == $adminId ? 'mine' : '' }}">
                                            {{ $cInitials }}</div>
                                        <div class="discussion-body">
                                            <div class="discussion-card comment">
                                                <div class="discussion-header">
                                                    <span class="name">{{ $c->admin_name }}
                                                        {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                                    <span
                                                        class="time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y h:i A') }}</span>
                                                </div>
                                                <p class="mb-0 mt-2">{{ $c->comment }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Comment box — hidden if fully rejected -->
                        @if ($d->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.verification-detail', $detailId) }}">
                                @csrf
                                <div class="d-flex gap-2">
                                    <input type="text" name="comment" class="form-control"
                                        placeholder="Add a comment for other admins..." required>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="feather-send"></i>
                                    </button>
                                </div>
                            </form>
                        @endif

                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- ============================================================
         MAKE DECISION MODAL — 2-step (remarks → confirmation),
         same design as loan-application-detail.blade.php's
         #decisionModal. Uses the shared modals-modern.css classes
         (.modal-header-clean, .modal-icon-circle, .modal-detail-card)
         plus the .btn-decision-* / .modal-subtext-text /
         .decision-char-counter overrides scoped to #decisionModal
         in verification-detail.css.
    ============================================================ --}}
    @if ($d->status !== 'rejected' && $d->status !== 'approved')
        <div class="modal fade modal-modern" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.verification-detail', $detailId) }}" id="decisionForm">
                        @csrf
                        <input type="hidden" name="action" id="decisionActionInput" value="">

                        <div class="modal-header-clean">
                            <div class="header-left">
                                <h6 id="decisionModalLabel">Review Borrower Verification</h6>
                            </div>
                            <button type="button" class="btn-close-clean" data-bs-dismiss="modal"
                                aria-label="Close">&times;</button>
                        </div>

                        <div class="modal-body">

                            <!-- Step 1: remarks -->
                            <div id="decisionStep1">
                                <label class="form-label fw-semibold" style="font-size:0.85rem;">Review Remarks <span
                                        class="text-muted fw-normal">(optional)</span></label>
                                <textarea name="remarks" id="decisionRemarks" class="form-control" rows="3" maxlength="500"
                                    placeholder="Provide additional comments or reason for your decision...">{{ $myReview->remarks ?? '' }}</textarea>
                                <div class="decision-char-counter">
                                    <span id="decisionRemarksCount">{{ strlen($myReview->remarks ?? '') }}</span>/500
                                </div>
                            </div>

                            <!-- Step 2: confirmation -->
                            <div id="decisionStep2" style="display:none;">
                                <div class="modal-icon-circle icon-success" id="decisionConfirmIconWrap">
                                    <i id="decisionConfirmIcon" class="feather-check"></i>
                                </div>
                                <p id="decisionConfirmText" class="modal-title-text"></p>
                                <p id="decisionConfirmSubtext" class="modal-subtext-text"></p>

                                <div class="modal-detail-card">
                                    <div class="modal-detail-row">
                                        <span class="modal-detail-label">Borrower</span>
                                        <span class="modal-detail-value">{{ $d->first_name }} {{ $d->last_name }}</span>
                                    </div>
                                    <div class="modal-detail-row">
                                        <span class="modal-detail-label">Borrower ID</span>
                                        <span class="modal-detail-value">#{{ $detailId }}</span>
                                    </div>
                                    <div class="modal-detail-row">
                                        <span class="modal-detail-label">Current status</span>
                                        <span
                                            class="modal-detail-value">{{ ucfirst(str_replace('_', ' ', $d->status)) }}</span>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="modal-footer" id="decisionFooterStep1">
                            <button type="button" class="btn-decision btn-decision-cancel"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn-decision btn-decision-reject"
                                onclick="decisionShowConfirm('rejected')">Reject</button>
                            <button type="button" class="btn-decision btn-decision-black"
                                onclick="decisionShowConfirm('approved')">Approve</button>
                        </div>
                        <div class="modal-footer" id="decisionFooterStep2" style="display:none;">
                            <button type="button" class="btn-decision btn-decision-cancel"
                                onclick="decisionBackToStep1()">Cancel</button>
                            <button type="submit" class="btn-decision btn-decision-black"
                                id="decisionConfirmBtn"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================
         VALID ID FULLSCREEN VIEWER (zoom + rotate)
    ============================================ --}}
    <div class="id-viewer-overlay" id="idViewerOverlay">
        <button class="id-viewer-close" onclick="closeIdViewer()"><i class="feather-x"></i></button>
        <img src="" alt="Valid ID full view" id="idViewerImage">
        <div class="id-viewer-toolbar">
            <button onclick="idViewerZoom(-0.2)" title="Zoom out"><i class="feather-zoom-out"></i></button>
            <button onclick="idViewerZoom(0.2)" title="Zoom in"><i class="feather-zoom-in"></i></button>
            <button onclick="idViewerRotate()" title="Rotate"><i class="feather-rotate-cw"></i></button>
            <button onclick="idViewerReset()" title="Reset"><i class="feather-refresh-ccw"></i></button>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/verification-detail.js') }}"></script>

    <script>
        function fixVerificationDetailSidebar() {
            const cameFrom = @json($cameFrom);


            const menusToReset = document.querySelectorAll(
                '.nxl-navbar > .nxl-item.nxl-hasmenu'
            );

            menusToReset.forEach(function(parentLi) {
                const label = parentLi.querySelector('.nxl-mtext');
                if (!label) return;

                const text = label.textContent.trim();
                if (text === 'Borrower Verification' || text === 'Borrowers') {
                    parentLi.classList.remove('nxl-trigger', 'active');
                    parentLi.querySelectorAll('.nxl-submenu .nxl-item').forEach(function(li) {
                        li.classList.remove('active');
                        li.setAttribute('data-current', 'false');
                    });
                }
            });


            menusToReset.forEach(function(parentLi) {
                const label = parentLi.querySelector('.nxl-mtext');
                if (!label) return;

                const text = label.textContent.trim();

                if (cameFrom === 'verification' && text === 'Borrower Verification') {
                    parentLi.classList.add('nxl-trigger', 'active');
                } else if (cameFrom !== 'verification' && text === 'Borrowers') {
                    parentLi.classList.add('nxl-trigger', 'active');

                    const firstSub = parentLi.querySelector('.nxl-submenu .nxl-item');
                    if (firstSub) {
                        firstSub.classList.add('active');
                        firstSub.setAttribute('data-current', 'true');
                    }
                }
            });
        }

        fixVerificationDetailSidebar();
        window.addEventListener('load', fixVerificationDetailSidebar);
        setTimeout(fixVerificationDetailSidebar, 300);
        setTimeout(fixVerificationDetailSidebar, 1000);
    </script>
@endsection
