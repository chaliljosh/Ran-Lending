@extends('admin.layout')

@section('title', 'Application Detail')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/loan-details.css') }}">
@endsection

@section('content')

    @php
        $cameFrom = request()->query('from', 'loan-applications');
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Application Detail</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                @if($cameFrom === 'active-borrowers')
                    <li class="breadcrumb-item"><a href="{{ route('admin.active-borrowers') }}">Active Borrowers</a></li>
                @elseif($cameFrom === 'ongoing-loans')
                    <li class="breadcrumb-item"><a href="{{ route('admin.ongoing-loans') }}">Ongoing Loans</a></li>
                @elseif($cameFrom === 'overdue-payments')
                    <li class="breadcrumb-item"><a href="{{ route('admin.overdue-payments') }}">Overdue Payments</a></li>
                @else
                    <li class="breadcrumb-item"><a href="{{ route('admin.loan-applications') }}">Loan Applications</a></li>
                @endif
                <li class="breadcrumb-item">#{{ $applicationId }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            @if($cameFrom === 'active-borrowers')
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

        <div class="row">

            <!-- LEFT COLUMN -->
            <div class="col-lg-4 ld-left-col">
                <div class="ld-sticky-col">
                    <div class="section-card text-center mb-3">
                        <div class="borrower-avatar">
                            {{ strtoupper(substr($app->first_name ?? '', 0, 1) . substr($app->last_name ?? '', 0, 1)) }}
                        </div>
                        <h6 class="fw-bold mb-1">
                            {{ $app->last_name }}, {{ $app->first_name }} {{ $app->middle_name }}{{ ($app->suffix ?? null) ? ', ' . $app->suffix : '' }}
                        </h6>
                        <p class="text-muted mb-3" style="font-size:0.83rem;">{{ $app->email }}</p>

                        @php
                        $statusClass = match($app->status) {
                            'approved' => 'badge-approved',
                            'active'   => 'badge-approved',
                            'rejected' => 'badge-rejected',
                            'on_hold'  => 'badge-onhold',
                            default    => 'badge-pending',
                        };
                        @endphp
                        <span class="badge {{ $statusClass }} px-3 py-2 mb-3">
                            {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                        </span>

                        @if($app->status === 'rejected')
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
                        <div class="alert alert-info mb-0 mt-2" style="font-size:0.82rem;">
                            <i class="feather-check-circle me-1"></i> You have already reviewed this application.
                        </div>

                        @else
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#decisionModal">
                            Make a Decision
                        </button>
                        @endif
                    </div>

                    <!-- Loan Summary -->
                    <div class="section-card">
                        <p class="section-title"><i class="feather-file-text me-2"></i>Loan Summary</p>
                        <table class="table table-sm table-borderless table-loan-summary mb-0">
                            <tr><td class="info-label">Application ID</td><td class="text-end info-value">#{{ $app->application_id }}</td></tr>
                            <tr><td class="info-label">Loan Amount</td><td class="text-end"><span class="ld-value-pill ld-pill-primary">₱{{ number_format($app->loan_amount, 2) }}</span></td></tr>
                            <tr><td class="info-label">Term</td><td class="text-end info-value">{{ $app->loan_term }} months</td></tr>
                            <tr><td class="info-label">Interest Rate</td><td class="text-end info-value">5% / month</td></tr>
                            <tr><td class="info-label">Total Interest</td><td class="text-end"><span class="ld-value-pill ld-pill-warning">₱{{ number_format($app->total_interest ?? 0, 2) }}</span></td></tr>
                            <tr><td class="info-label">Total to Pay</td><td class="text-end"><span class="ld-value-pill ld-pill-danger">₱{{ number_format($app->total_amount ?? 0, 2) }}</span></td></tr>
                            <tr><td class="info-label">Monthly Payment</td><td class="text-end"><span class="ld-value-pill ld-pill-success">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span></td></tr>
                            <tr><td class="info-label">Purpose</td><td class="text-end info-value">{{ ucfirst(str_replace('_', ' ', $app->loan_purpose)) }}</td></tr>
                            <tr><td class="info-label">Referral</td><td class="text-end info-value">{{ ucfirst(str_replace('_', ' ', $app->referral_source ?? '—')) }}</td></tr>
                            <tr><td class="info-label">Applied</td><td class="text-end info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-8">

                <!-- Admin Review Status -->
                <div class="section-card mb-3">
                    <p class="section-title"><i class="feather-users me-2"></i>Admin Review Status ({{ $approvedCount ?? 0 }}/3 approved)</p>
                    <div class="row g-3">
                        @for($i = 0; $i < 3; $i++)
                            @php $review = $allReviews[$i] ?? null; @endphp
                            <div class="col-md-4">
                                @php
                                $boxClass  = $review ? $review->decision : 'awaiting';
                                $iconName  = $review ? ($review->decision === 'approved' ? 'check' : 'x') : 'clock';
                                $iconColor = $review ? ($review->decision === 'approved' ? '#16a34a' : '#dc2626') : '#94a3b8';
                                $iconBg    = $review ? ($review->decision === 'approved' ? '#dcfce7' : '#fee2e2') : '#f1f5f9';
                                @endphp
                                <div class="review-box {{ $boxClass }}">
                                    <div class="review-icon" style="background:{{ $iconBg }};">
                                        <i class="feather-{{ $iconName }}" style="color:{{ $iconColor }};"></i>
                                    </div>
                                    @if($review)
                                        <p class="fw-bold mb-1" style="font-size:0.85rem;">{{ $review->admin_name }}</p>
                                        <span class="badge {{ $review->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }} px-2 py-1">
                                            {{ ucfirst($review->decision) }}
                                        </span>
                                        <p class="ld-review-time mb-0">{{ \Carbon\Carbon::parse($review->reviewed_at)->format('M d, Y g:i A') }}</p>
                                        @if($review->remarks)
                                        <p class="ld-review-remark mb-0">&ldquo;{{ $review->remarks }}&rdquo;</p>
                                        @endif
                                    @else
                                        <p class="fw-bold mb-1" style="font-size:0.85rem;">Admin {{ $i + 1 }}</p>
                                        <span class="badge badge-pending px-2 py-1">Awaiting</span>
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Payment Schedule -->
                @if($paymentSchedule->isNotEmpty())
                <div class="section-card mb-3">
                    <p class="section-title"><i class="feather-calendar me-2"></i>Payment Schedule</p>
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
                                @foreach($paymentSchedule as $s)
                                @php
                                    $isOverdue = is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
                                    $isPaid = !is_null($s->paid_at);
                                    $scheduleBadge = $isPaid ? 'ld-schedule-paid' : ($isOverdue ? 'ld-schedule-late' : 'ld-schedule-pending');
                                    $scheduleLabel = $isPaid ? 'Paid' : ($isOverdue ? 'Late' : 'Pending');
                                @endphp
                                <tr>
                                    <td>{{ $s->installment_no }}</td>
                                    <td>{{ \Carbon\Carbon::parse($s->due_date)->format('M d, Y') }}</td>
                                    <td>₱{{ number_format($s->amount_due, 2) }}</td>
                                    <td>₱{{ number_format($s->amount_paid, 2) }}</td>
                                    <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                    <td><span class="ld-schedule-badge {{ $scheduleBadge }}">{{ $scheduleLabel }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Personal Information -->
                <div class="section-card mb-3">
                    <p class="section-title"><i class="feather-user me-2"></i>Personal Information</p>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Full Name</span><span class="info-value">{{ $app->first_name }} {{ $app->middle_name }} {{ $app->last_name }}{{ ($app->suffix ?? null) ? ', ' . $app->suffix : '' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Birthday</span><span class="info-value">{{ ($app->birthday ?? null) ? \Carbon\Carbon::parse($app->birthday)->format('F d, Y') : '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Gender</span><span class="info-value">{{ ucfirst($app->gender ?? '—') }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Civil Status</span><span class="info-value">{{ ucfirst($app->civil_status ?? '—') }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Citizenship</span><span class="info-value">{{ $app->citizenship ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Cellphone</span><span class="info-value">{{ $app->cellphone_num ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Email</span><span class="info-value">{{ $app->email }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Monthly Income</span><span class="info-value text-success">{{ ($app->monthly_income ?? null) ? '₱' . number_format($app->monthly_income, 2) : '—' }}</span></div></div>
                    </div>
                </div>

                <!-- Present Address -->
                <div class="section-card mb-3">
                    <p class="section-title"><i class="feather-map-pin me-2"></i>Present Address</p>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Region</span><span class="info-value">{{ $address->region ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Province</span><span class="info-value">{{ $address->province ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">City / Municipality</span><span class="info-value">{{ $address->city ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Barangay</span><span class="info-value">{{ $address->barangay ?? '—' }}</span></div></div>
                        <div class="col-12"><div class="ld-field"><span class="info-label">Home Address</span><span class="info-value">{{ $address->home_address ?? '—' }}</span></div></div>
                    </div>
                </div>

                <!-- Work Information -->
                <div class="section-card mb-3">
                    <p class="section-title"><i class="feather-briefcase me-2"></i>Work Information</p>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Employment Status</span><span class="info-value">{{ ucfirst(str_replace('_', ' ', $work->employment_status ?? '—')) }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Company / School</span><span class="info-value">{{ $work->company_name ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Position</span><span class="info-value">{{ $work->position ?? '—' }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">Years in Company</span><span class="info-value">{{ $work->years_in_company ?? '—' }} years</span></div></div>
                    </div>
                </div>

                <!-- Valid ID -->
                @if($app->id_file_path ?? null)
                <div class="section-card mb-3">
                    <p class="section-title"><i class="feather-credit-card me-2"></i>Submitted Valid ID</p>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">ID Type</span><span class="info-value">{{ ucfirst(str_replace('_', ' ', $app->id_type ?? '—')) }}</span></div></div>
                        <div class="col-md-6"><div class="ld-field"><span class="info-label">ID Number</span><span class="info-value">{{ $app->id_number ?? '—' }}</span></div></div>
                        <div class="col-12">
                            @php $ext = strtolower(pathinfo($app->id_file_path, PATHINFO_EXTENSION)); @endphp
                            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                <div class="ld-id-viewer">
                                    <img src="{{ asset('storage/' . $app->id_file_path) }}" alt="Valid ID">
                                </div>
                                <div class="ld-id-actions">
                                    <a href="{{ asset('storage/' . $app->id_file_path) }}" target="_blank" class="btn-id-action">
                                        <i class="feather-maximize-2"></i> View Full Size
                                    </a>
                                    <a href="{{ asset('storage/' . $app->id_file_path) }}" download class="btn-id-action">
                                        <i class="feather-download"></i> Download
                                    </a>
                                </div>
                            @elseif($ext === 'pdf')
                                <div class="ld-id-viewer">
                                    <i class="feather-file-text" style="font-size:44px;color:#94a3b8;"></i>
                                    <p class="text-muted mt-2 mb-0" style="font-size:0.82rem;">PDF document</p>
                                </div>
                                <div class="ld-id-actions">
                                    <a href="{{ asset('storage/' . $app->id_file_path) }}" target="_blank" class="btn-id-action">
                                        <i class="feather-external-link"></i> View Full Size
                                    </a>
                                    <a href="{{ asset('storage/' . $app->id_file_path) }}" download class="btn-id-action">
                                        <i class="feather-download"></i> Download
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Discussion -->
                <div class="section-card">
                    <p class="section-title"><i class="feather-message-square me-2"></i>Discussion</p>

                    <div class="ld-discussion">
                        @forelse($comments as $c)
                        <div class="ld-comment">
                            <div class="ld-comment-avatar">{{ strtoupper(substr($c->admin_name ?? '?', 0, 1)) }}</div>
                            <div class="ld-comment-body">
                                <div class="ld-comment-head">
                                    <span class="ld-comment-name">{{ $c->admin_name }}</span>
                                    @isset($c->created_at)
                                    <span class="ld-comment-time">{{ \Carbon\Carbon::parse($c->created_at)->diffForHumans() }}</span>
                                    @endisset
                                </div>
                                <p class="ld-comment-text">{{ $c->comment }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="ld-discussion-empty">No comments yet. Be the first to add one.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('admin.loan-application-detail', $applicationId) }}" class="ld-comment-form">
                        @csrf
                        <textarea name="comment" class="form-control" placeholder="Add a comment..."></textarea>
                        <button type="submit" class="btn-post-comment">Post Comment</button>
                    </form>
                </div>

            </div>
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

    @if(session('success'))
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="feather-check-circle" style="font-size:48px; color:#16a34a;"></i>
                    <p class="mt-3 mb-0 fw-semibold">Decision saved successfully!</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($app->status !== 'rejected' && $app->status !== 'approved' && $app->status !== 'active' && !$myReview)
    <div class="modal fade modal-modern" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.loan-application-detail', $applicationId) }}" id="decisionForm">
                    @csrf
                    <input type="hidden" name="action" id="decisionActionInput" value="">

                    <div class="modal-header-clean">
                        <div class="header-left">
                            <h6 id="decisionModalLabel">Review Loan Application</h6>
                        </div>
                        <button type="button" class="btn-close-clean" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    </div>

                    <div class="modal-body">

                        <!-- Step 1: remarks -->
                        <div id="decisionStep1">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Review Remarks <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="remarks" id="decisionRemarks" class="form-control" rows="3" placeholder="Provide additional comments or reason for your decision..."></textarea>
                        </div>

                        <!-- Step 2: confirmation -->
                        <div id="decisionStep2" style="display:none;">
                            <div class="modal-icon-circle icon-success" id="decisionConfirmIconWrap">
                                <i id="decisionConfirmIcon" class="feather-check"></i>
                            </div>
                            <p id="decisionConfirmText" class="modal-title-text"></p>

                            <div class="modal-detail-card">
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Applicant</span>
                                    <span class="modal-detail-value">{{ $app->first_name }} {{ $app->last_name }}</span>
                                </div>
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Loan amount</span>
                                    <span class="modal-detail-value">₱{{ number_format($app->loan_amount, 2) }}</span>
                                </div>
                                <div class="modal-detail-row">
                                    <span class="modal-detail-label">Current status</span>
                                    <span class="modal-detail-value">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer" id="decisionFooterStep1">
                        <button type="button" class="btn btn-modal-primary btn-approve" onclick="decisionShowConfirm('approved')">Approve</button>
                        <button type="button" class="btn btn-modal-primary btn-reject" onclick="decisionShowConfirm('rejected')">Reject</button>
                    </div>
                    <div class="modal-footer" id="decisionFooterStep2" style="display:none;">
                        <button type="button" class="btn btn-modal-cancel" onclick="decisionBackToStep1()">Back</button>
                        <button type="submit" class="btn btn-modal-primary" id="decisionConfirmBtn"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endpush

@section('page-scripts')
       <script src="{{ asset('functions/loan-application-detail.js') }}"></script>
@endsection