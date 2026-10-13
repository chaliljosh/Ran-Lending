@extends('admin.layout')

@section('title', 'Application Detail')

@section('page-styles')
   <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    @php
        $cameFrom = request()->query('from', 'loan-applications');
        $borrowerPhotoUrl = $borrowerProfilePicture ?? null;

        $statusClass = match ($app->status) {
            'approved' => 'badge-approved',
            'active' => 'badge-approved',
            'rejected' => 'badge-rejected',
            'on_hold' => 'badge-onhold',
            default => 'badge-pending',
        };
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Application Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                @if ($cameFrom === 'active-borrowers')
                    <li class="breadcrumb-item"><a href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                @elseif($cameFrom === 'ongoing-loans')
                    <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                @elseif($cameFrom === 'overdue-payments')
                    <li class="breadcrumb-item"><a href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                @else
                    <li class="breadcrumb-item"><a href="{{ route('admin.loan-applications') }}">Loan Applications</a></li>
                @endif
           <li class="breadcrumb-item">#{{ strtoupper(substr($app->uuid, 0, 8)) }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            @if ($cameFrom === 'active-borrowers')
                <a href="{{ route('admin.active-borrowers') }}" class="btn btn-light">Back</a>
            @elseif($cameFrom === 'ongoing-loans')
                <a href="{{ route('admin.ongoing-loans') }}" class="btn btn-light">Back</a>
            @elseif($cameFrom === 'overdue-payments')
                <a href="{{ route('admin.overdue-payments') }}" class="btn btn-light">Back</a>
            @else
                <a href="{{ route('admin.loan-applications', ['status' => $viewStatus]) }}" class="btn btn-light">Back</a>
            @endif
        </div>
    </div>

    <div class="main-content">

        {{-- Decision saved → toast instead of a modal.
             NOTE: the controller currently redirects to
             admin.loan-applications after a decision (not back
             here), so this block only fires if some other action
             flashes 'success' while staying on this page. The
             loan-applications list page needs the matching toast
             script to actually show the message after a decision. --}}
        @if (session('success'))
            @php
                $decisionStatus = session('decisionStatus');
                $toastDesc = match ($decisionStatus) {
                    'approved' => 'Loan application approved.',
                    'rejected' => 'Loan application rejected.',
                    'on_hold' => 'Loan application placed on hold.',
                    default => 'Decision saved successfully.',
                };
            @endphp
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    window.showAdminToast('success', 'Decision Saved', @json($toastDesc));
                });
            </script>
        @endif

        <!-- ============================================================
                                     HEADER CARD — borrower profile (left) + loan stats (right)
                                ============================================================ -->
        <div class="ld-header-card">
            <div class="ld-header-left">
                <div class="borrower-avatar {{ empty($borrowerPhotoUrl) ? 'js-borrower-avatar' : '' }}">
                    @if (!empty($borrowerPhotoUrl))
                        <img src="{{ $borrowerPhotoUrl }}" alt="{{ $app->first_name }} {{ $app->last_name }}">
                    @else
                        {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                    @endif
                </div>
                <div class="ld-header-info">
                    <h6 class="fw-semibold mb-1">
                        {{ $app->first_name }} {{ $app->last_name }}
                    </h6>
                    <p class="ld-header-meta mb-2">
                      <span>Application ID: <strong>#{{ strtoupper(substr($app->uuid, 0, 8)) }}</strong></span>
                        <span class="ld-header-meta-sep">&bull;</span>
                        <span>Email: <a href="mailto:{{ $app->email }}">{{ $app->email }}</a></span>
                    </p>
                    <span class="badge {{ $statusClass }} px-3 py-2">
                        {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                    </span>
                </div>
            </div>

            <div class="ld-header-stats">
                <div class="ld-stat">
                    <div class="ld-stat-value">₱{{ number_format($app->loan_amount, 2) }}</div>
                    <div class="ld-stat-label">Loan Amount</div>
                </div>
                <div class="ld-stat">
                    <div class="ld-stat-value">{{ $app->loan_term }}</div>
                    <div class="ld-stat-label">Term (Months)</div>
                </div>
                <div class="ld-stat">
                    <div class="ld-stat-value">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</div>
                    <div class="ld-stat-label">Monthly Payment</div>
                </div>
                <div class="ld-stat">
                    <div class="ld-stat-value">₱{{ number_format($app->total_interest ?? 0, 2) }}</div>
                    <div class="ld-stat-label">Total Interest</div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- LEFT COLUMN — Loan Summary -->
            <div class="col-lg-4 ld-left-col">
                <div class="ld-sticky-col">

                    <!-- Loan Summary -->
                    <div class="section-card mb-3">
                        <p class="section-title">Loan Summary</p>
                        <div class="row g-3">
                            <div class="col-6">
                               <div class="ld-field"><span class="info-label">Application ID</span><span
        class="info-value">#{{ strtoupper(substr($app->uuid, 0, 8)) }}</span></div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Loan Amount</span><span
                                        class="info-value">₱{{ number_format($app->loan_amount, 2) }}</span></div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Term</span><span
                                        class="info-value">{{ $app->loan_term }} months</span></div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Interest Rate</span><span
                                        class="info-value">5% / month</span></div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Total Interest</span><span
                                        class="info-value">₱{{ number_format($app->total_interest ?? 0, 2) }}</span></div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Total to Pay</span><span
                                        class="info-value">₱{{ number_format($app->total_amount ?? 0, 2) }}</span></div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Monthly Payment</span><span
                                        class="info-value">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Purpose</span><span
                                        class="info-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Referral</span><span
                                        class="info-value">{{ ucfirst(str_replace('_', ' ', $app->referral_source ?? '—')) }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Applied</span><span
                                        class="info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN — Admin Review, Personal Info, Application Details -->
            <div class="col-lg-8">

                <!-- Admin Review Status -->
                <div class="section-card mb-3">
                    <p class="section-title">Admin Review Status
                        ({{ $approvedCount ?? 0 }}/3 approved)</p>
                    <div class="table-responsive">
                        <table class="table table-sm ld-review-table mb-0">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Decision</th>
                                    <th>Reviewed On</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($i = 0; $i < 3; $i++)
                                    @php $review = $allReviews[$i] ?? null; @endphp
                                    <tr>
                                        <td>{{ $review->admin_name ?? 'Admin ' . ($i + 1) }}</td>
                                        <td>
                                            @if ($review)
                                                <span
                                                    class="badge {{ $review->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-2 py-1">
                                                    {{ ucfirst($review->decision) }}
                                                </span>
                                            @else
                                                <span class="badge badge-pending px-2 py-1">Awaiting</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $review ? \Carbon\Carbon::parse($review->reviewed_at)->format('M d, Y g:i A') : '—' }}
                                        </td>
                                        <td>
                                            {{ $review && $review->remarks ? $review->remarks : '—' }}
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="section-card mb-3">
                    <p class="section-title">Personal Information</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Full Name</span><span
                                    class="info-value">{{ $app->first_name }} {{ $app->middle_name }}
                                    {{ $app->last_name }}{{ $app->suffix ?? null ? ', ' . $app->suffix : '' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Birthday</span><span
                                    class="info-value">{{ $app->birthday ?? null ? \Carbon\Carbon::parse($app->birthday)->format('F d, Y') : '—' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Gender</span><span
                                    class="info-value">{{ ucfirst($app->gender ?? '—') }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Civil Status</span><span
                                    class="info-value">{{ ucfirst($app->civil_status ?? '—') }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Citizenship</span><span
                                    class="info-value">{{ $app->citizenship ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Cellphone</span><span
                                    class="info-value">{{ $app->cellphone_num ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Email</span><span
                                    class="info-value">{{ $app->email }}</span></div>
                        </div>
                                                <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Monthly Income</span><span
                                    class="info-value text-success">{{ $app->monthly_income ?? null ? (is_numeric($app->monthly_income) ? '₱' . number_format($app->monthly_income, 2) : $app->monthly_income) : '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Present Address -->
                <div class="section-card mb-3">
                    <p class="section-title">Present Address</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Region</span><span
                                    class="info-value">{{ $address->region ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Province</span><span
                                    class="info-value">{{ $address->province ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">City / Municipality</span><span
                                    class="info-value">{{ $address->city ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Barangay</span><span
                                    class="info-value">{{ $address->barangay ?? '—' }}</span></div>
                        </div>
                        <div class="col-12">
                            <div class="ld-field compact"><span class="info-label">Home Address</span><span
                                    class="info-value">{{ $address->home_address ?? '—' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Work Information -->
                <div class="section-card mb-3">
                    <p class="section-title">Work Information</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Employment Status</span><span
                                    class="info-value">{{ ucfirst(str_replace('_', ' ', $work->employment_status ?? '—')) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Company / School</span><span
                                    class="info-value">{{ $work->company_name ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Position</span><span
                                    class="info-value">{{ $work->position ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="ld-field compact"><span class="info-label">Years in Company</span><span
                                    class="info-value">{{ $work->years_in_company ?? '—' }} years</span></div>
                        </div>
                    </div>
                </div>

               <!-- Valid ID -->
@if ($idFrontUrl || $idBackUrl)
    <div class="section-card mb-3">
        <p class="section-title">Submitted Valid ID</p>
        <div class="row g-3 mb-1">
            <div class="col-md-6">
                <div class="ld-field"><span class="info-label">ID Type</span><span
                        class="info-value">{{ ucfirst(str_replace('_', ' ', $app->id_type ?? '—')) }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ld-field"><span class="info-label">ID Number</span><span
                        class="info-value">{{ $app->id_number ?? '—' }}</span></div>
            </div>
        </div>

        <div class="mt-1 d-flex flex-wrap gap-4">
            @if ($idFrontUrl)
                <div>
                    <label class="form-label fw-semibold"
                        style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Front
                        ID</label><br>
                    @php $extFront = strtolower(pathinfo($app->id_file_path, PATHINFO_EXTENSION)); @endphp
                    @if (in_array($extFront, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <span class="id-thumb-wrap" onclick="openIdViewer('{{ $idFrontUrl }}')">
                            <img src="{{ $idFrontUrl }}" alt="Valid ID — Front">
                        </span>
                    @else
                        <a href="{{ $idFrontUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="feather-file me-1"></i> View Front ID
                        </a>
                    @endif
                </div>
            @endif

            @if ($idBackUrl)
                <div>
                    <label class="form-label fw-semibold"
                        style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Back
                        ID</label><br>
                    @php $extBack = strtolower(pathinfo($app->id_file_path_back, PATHINFO_EXTENSION)); @endphp
                    @if (in_array($extBack, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <span class="id-thumb-wrap" onclick="openIdViewer('{{ $idBackUrl }}')">
                            <img src="{{ $idBackUrl }}" alt="Valid ID — Back">
                        </span>
                    @else
                        <a href="{{ $idBackUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="feather-file me-1"></i> View Back ID
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif

                <!-- Payment Schedule -->
                @if ($paymentSchedule->isNotEmpty())
                    <div class="section-card mb-3">
                        <p class="section-title">Payment Schedule</p>
                        <div class="table-responsive">
                            <table class="table table-sm ld-schedule-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th>Amount Due</th>
                                        <th>Amount Paid</th>
                                        <th>Paid On</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($paymentSchedule as $s)
                                        @php
                                            $isOverdue =
                                                is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
                                            $isPaid = !is_null($s->paid_at);
                                            $scheduleBadge = $isPaid
                                                ? 'ld-schedule-paid'
                                                : ($isOverdue
                                                    ? 'ld-schedule-late'
                                                    : 'ld-schedule-pending');
                                            $scheduleLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Late' : 'Pending');
                                        @endphp
                                        <tr>
                                            <td>{{ $s->installment_no }}</td>
                                            <td>{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                            <td>₱{{ number_format($s->amount_due, 2) }}</td>
                                            <td>₱{{ number_format($s->amount_paid, 2) }}</td>
                                            <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}
                                            </td>
                                            <td><span
                                                    class="ld-schedule-badge {{ $scheduleBadge }}">{{ $scheduleLabel }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Discussion -->
                <div class="section-card">
                    <p class="section-title">Discussion</p>

                    <div class="ld-discussion">
                        @forelse($comments as $c)
                            <div class="ld-comment">
                                <div class="ld-comment-avatar">{{ strtoupper(substr($c->admin_name ?? '?', 0, 1)) }}</div>
                                <div class="ld-comment-body">
                                    <div class="ld-comment-head">
                                        <span class="ld-comment-name">{{ $c->admin_name }}</span>
                                        @isset($c->created_at)
                                            <span
                                                class="ld-comment-time">{{ \Carbon\Carbon::parse($c->created_at)->diffForHumans() }}</span>
                                        @endisset
                                    </div>
                                    <p class="ld-comment-text">{{ $c->comment }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="ld-discussion-empty">No comments yet. Be the first to add one.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('admin.loan-application-detail', $applicationId) }}"
                        class="ld-comment-form">
                        @csrf
                        <textarea name="comment" class="form-control" placeholder="Add a comment..."></textarea>
                        <button type="submit" class="btn-post-comment">Post Comment</button>
                    </form>
                </div>

                <!-- Application Status / Decision — nasa pinaka-baba, right side -->
             <div class="d-flex justify-content-end mt-4 mb-5">
                    @if ($app->status === 'rejected')
                        <div class="alert alert-danger mb-0" style="font-size:0.85rem;">
                            <i class="feather-x-circle me-2"></i>
                            Fully <strong>rejected</strong> by all 3 admins. No further action.
                        </div>
                    @elseif($app->status === 'approved' || $app->status === 'active')
                        <div class="alert alert-success mb-0" style="font-size:0.85rem;">
                            <i class="feather-check-circle me-2"></i>
                            Fully <strong>approved</strong>. No further action needed.
                        </div>
                    @elseif($myReview)
                        <div class="alert alert-info mb-0" style="font-size:0.82rem;">
                            <i class="feather-check-circle me-1"></i> You have already reviewed this application.
                        </div>
                    @else
                       <button type="button" class="btn btn-decision-review" data-bs-toggle="modal"
    data-bs-target="#decisionModal">
    <i class="feather-clipboard"></i>
    Review Application
</button>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ============================================
         VALID ID FULLSCREEN VIEWER (zoom + rotate)
         Same markup/behavior as verification-detail.blade.php.
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

{{-- ============================================================
     MODALS — pinush sa dulo ng <body> gamit ang @stack('modals')
     sa layout, para hindi sila ma-trap sa loob ng .nxl-container.
     Kung ilalagay sila sa loob ng @section('content'), mag-a-appear
     bilang "gumagana" ang button pero hindi lalabas ang modal kasi
     naka-clip sa loob ng parent container.
============================================================ --}}
@push('modals')

    @if ($app->status !== 'rejected' && $app->status !== 'approved' && $app->status !== 'active' && !$myReview)
        <div class="modal fade modal-modern" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.loan-application-detail', $applicationId) }}"
                        id="decisionForm">
                        @csrf
                        <input type="hidden" name="action" id="decisionActionInput" value="">

                        <div class="modal-header-clean">
                            <div class="header-left">
                                <h6 id="decisionModalLabel">Review Loan Application</h6>
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
                                    placeholder="Provide additional comments or reason for your decision..."></textarea>
                                <div class="decision-char-counter"><span id="decisionRemarksCount">0</span>/500</div>
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
                                        <span class="modal-detail-label">Applicant</span>
                                        <span class="modal-detail-value">{{ $app->first_name }}
                                            {{ $app->last_name }}</span>
                                    </div>
                                    <div class="modal-detail-row">
                                        <span class="modal-detail-label">Loan amount</span>
                                        <span class="modal-detail-value">₱{{ number_format($app->loan_amount, 2) }}</span>
                                    </div>
                                    <div class="modal-detail-row">
                                        <span class="modal-detail-label">Current status</span>
                                        <span
                                            class="modal-detail-value">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span>
                                    </div>
                                </div>

                            </div>

                        </div>

                       <div class="modal-footer" id="decisionFooterStep1">
    <button type="button" class="btn-decision btn-decision-cancel"
        data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn-decision btn-decision-reject"
        onclick="decisionShowConfirm('rejected')">Reject</button>
    <button type="button" class="btn-decision btn-decision-approve"
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

@endpush

@section('page-scripts')
    <script src="{{ asset('functions/loan-application-detail.js') }}"></script>
    <script src="{{ asset('functions/remove-blur.js') }}"></script>
@endsection