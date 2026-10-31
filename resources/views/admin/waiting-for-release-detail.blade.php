@extends('admin.layout')

@section('title', 'Waiting for Release')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-release-modal.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')
    @php $loanCode = strtoupper(substr($app->uuid, 0, 8)); @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Waiting for Release</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.waiting-for-release') }}">Waiting for Release</a></li>
                <li class="breadcrumb-item">Loan #{{ $loanCode }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.waiting-for-release') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content">

        <div class="olh-card">
            <div class="olh-left">
                <div class="olh-avatar js-borrower-avatar">
                    @if (!empty($borrowerProfilePicture))
                        <img src="{{ $borrowerProfilePicture }}" alt="{{ $app->first_name }} {{ $app->last_name }}">
                    @endif
                </div>
                <div class="olh-info">
                    <div class="olh-name-row">
                        <span class="olh-name">{{ $app->first_name }} {{ $app->last_name }}</span>
                        <span class="badge badge-active olh-status-badge">Awaiting Release</span>
                    </div>
                    <div class="olh-meta-row">
                        <span><strong>Loan ID:</strong> LN-{{ $loanCode }}</span>
                        <span class="olh-meta-sep">&bull;</span>
                        <span><strong>Email:</strong> {{ $app->email }}</span>
                    </div>
                </div>
            </div>

            <div class="olh-stats">
                <div class="olh-stat">
                    <div class="olh-stat-value">&#8369;{{ number_format($app->loan_amount ?? 0, 2) }}</div>
                    <div class="olh-stat-label">Amount to Release</div>
                </div>
                <div class="olh-stat">
                    <div class="olh-stat-value">&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</div>
                    <div class="olh-stat-label">Total to Pay</div>
                </div>
                <div class="olh-stat">
                    <div class="olh-stat-value">{{ $app->loan_term }} mo</div>
                    <div class="olh-stat-label">Term</div>
                </div>
            </div>
        </div>

        <div class="olr-card">
            <div class="olr-icon"><i class="feather-send"></i></div>
            <div class="olr-text">
                <div class="olr-title">Loan Disbursement</div>
                <div class="olr-sub">Send the borrower confirmation that the &#8369;{{ number_format($app->loan_amount ?? 0, 2) }} loan has been released. The payment schedule will be created once the proof is sent.</div>
            </div>
            <button type="button" class="btn-send-release" id="olsSendProofBtn"
                data-application-id="{{ $applicationId }}"
                data-redirect-url="{{ route('admin.ongoing-loan-detail', $app->uuid) }}"
                data-amount="{{ number_format($app->loan_amount ?? 0, 2) }}"
                data-borrower-name="{{ $app->first_name }} {{ $app->last_name }}"
                data-borrower-email="{{ $app->email }}">
                Release Loan
            </button>
        </div>

        <div class="loan-detail-grid">

            <div class="panel loan-summary-panel">
                <div class="panel-header">Loan Summary</div>
                <div class="panel-body">
                    <div class="loan-info-grid">
                        <div class="info-item2">
                            <label>Term</label>
                            <span>{{ $app->loan_term }} months</span>
                        </div>
                        <div class="info-item2">
                            <label>Interest Rate</label>
                            <span>{{ $interestRate }}%</span>
                        </div>
                        <div class="info-item2">
                            <label>Total Interest</label>
                            <span>&#8369;{{ number_format($totalInterest, 2) }}</span>
                        </div>
                        <div class="info-item2 is-primary">
                            <label>Total to Pay</label>
                            <span>&#8369;{{ number_format($app->total_amount ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <div class="loan-info-grid secondary">
                        <div class="info-item2">
                            <label>Loan Amount</label>
                            <span>&#8369;{{ number_format($app->loan_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Monthly Payment</label>
                            <span>&#8369;{{ number_format($monthlyPayment, 2) }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Purpose</label>
                            <span>{{ $appDisplay['purpose'] }}</span>
                        </div>
                        <div class="info-item2">
                            <label>Applied On</label>
                            <span>{{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel loan-review-panel">
                <div class="panel-header">Admin Review Status</div>
                <div class="panel-body">
                    @forelse ($allReviews as $r)
                        <div class="review-item {{ $r->decision === 'approved' ? 'is-approved' : 'is-rejected' }}">
                            <div class="review-avatar"><i class="feather-user"></i></div>
                            <div class="review-body">
                                <span class="review-name">{{ $r->admin_name }}</span>
                                @if ($r->remarks)
                                    <span class="review-remark">{{ $r->remarks }}</span>
                                @endif
                                <span class="review-time">{{ \Carbon\Carbon::parse($r->reviewed_at)->format('M d, Y \a\t g:i A') }}</span>
                            </div>
                            <span class="badge {{ $r->decision === 'approved' ? 'badge-approved' : 'badge-rejected' }}">
                                {{ ucfirst($r->decision) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted empty-note">No admin decisions recorded.</p>
                    @endforelse

                    @if ($comments->count() > 0)
                        <p class="comments-label">Comments</p>
                        @foreach ($comments as $c)
                            <div class="review-item is-comment">
                                <div class="review-avatar {{ $c->admin_id == $adminId ? 'mine' : '' }}"><i class="feather-user"></i></div>
                                <div class="review-body">
                                    <span class="review-name">{{ $c->admin_name }} {{ $c->admin_id == $adminId ? '(You)' : '' }}</span>
                                    <span class="review-remark">{{ $c->comment }}</span>
                                    <span class="review-time">{{ \Carbon\Carbon::parse($c->created_at)->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade nxl-account-modal" id="olsSendProofModal" tabindex="-1"
        aria-labelledby="olsProofTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content olrm-modal">

                <div class="olrm-body">
                    <div class="olrm-head">
                        <h5 class="olrm-title" id="olsProofTitle">Release Confirmation</h5>
                        <button type="button" class="olrm-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="feather-x"></i>
                        </button>
                    </div>

                    <p class="olrm-desc">
                        Please verify the borrower and loan details before proceeding.
                        Attaching the proof <strong>of release</strong> will initiate the payment schedule.
                    </p>

                    <div class="olrm-summary">
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Borrower</span>
                            <span class="olrm-value" id="olsProofBorrowerName"></span>
                        </div>
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Loan Amount</span>
                            <span class="olrm-value">&#8369;<span id="olsProofAmount">0.00</span></span>
                        </div>
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Loan Account</span>
                            <span class="olrm-value">LN-{{ $loanCode }}</span>
                        </div>
                        <div class="olrm-summary-item">
                            <span class="olrm-label">Recipient Email</span>
                            <span class="olrm-value" id="olsProofBorrowerEmail"></span>
                        </div>
                    </div>

                    <div class="olrm-field">
                        <span class="olrm-field-label">Attach proof of release <span class="ols-req">*</span></span>

                        <label for="olsProofInput" class="ols-dropzone olrm-dropzone" id="olsProofDropzone">
                            <i class="feather-upload-cloud"></i>
                            <span class="ols-dropzone-text">Click to upload image</span>
                            <span class="ols-dropzone-hint">JPG or PNG (up to 5MB)</span>
                        </label>
                        <input type="file" id="olsProofInput" accept="image/jpeg,image/png" hidden>

                        <div class="ols-preview" id="olsProofPreview" style="display:none;">
                            <img id="olsProofPreviewImg" alt="Proof preview">
                            <div class="ols-preview-meta">
                                <span id="olsProofFileName"></span>
                                <span id="olsProofFileSize"></span>
                            </div>
                            <button type="button" class="ols-preview-remove" id="olsProofRemove"
                                aria-label="Remove proof">
                                <i class="feather-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ols-form-error" id="olsProofError" style="display:none;"></div>

                    <div class="olrm-note">
                        <span class="olrm-note-icon" aria-hidden="true">i</span>
                        <div class="olrm-note-text">
                            <strong>Important</strong>
                            <p>This action can only be completed once for this loan. Please ensure that all
                                details and the attached proof are correct before confirming.</p>
                        </div>
                    </div>
                </div>

                <div class="olrm-footer">
                    <button type="button" class="olrm-btn olrm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="olrm-btn olrm-btn-confirm" id="olsProofSendBtn">
                        <span class="ols-confirm-btn-label">Confirm &amp; Send</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
    <script src="{{ asset('functions/waiting-for-release.js') }}?v={{ time() }}"></script>
@endsection