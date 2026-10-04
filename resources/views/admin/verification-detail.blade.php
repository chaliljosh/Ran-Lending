@extends('admin.layout')

    @section('title', 'Borrower Details')

    @section('page-styles')
        <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}?v={{ time() }}">
        <link rel="stylesheet" href="{{ asset('assets/admin/css/verification-detail.css') }}?v={{ time() }}" />
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
    @endsection

    @section('content')

        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Borrower Details</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                    <li class="breadcrumb-item">Details #{{ $detailId }}</li>
                </ul>
            </div>
            <div class="page-header-right ms-auto d-flex align-items-center gap-2">
                <a href="{{ route('admin.borrowers') }}" class="btn btn-light">
                    Back
                </a>
            </div>
        </div>

        <div class="main-content">

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
            Income</label><span>{{ $d->monthly_income ?? '—' }}</span></div>
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

                    <!-- Valid ID / Attached ID -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="section-title">Valid ID</div>
                            <div class="info-row">
                                <div class="info-item"><label>ID Type</label><span>{{ $d->id_type ?? '—' }}</span></div>
                                <div class="info-item"><label>ID Number</label><span>{{ $d->id_number ?? '—' }}</span></div>
                            </div>

                            @if ($idFrontUrl || $idBackUrl)
                                <div class="mt-1 d-flex flex-wrap gap-4">
                                    @if ($idFrontUrl)
                                        <div>
                                            <label class="form-label fw-semibold"
                                                style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Front
                                                ID</label><br>
                                            @php $extFront = strtolower(pathinfo($d->id_file_path, PATHINFO_EXTENSION)); @endphp
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
                                            @php $extBack = strtolower(pathinfo($d->id_file_path_back, PATHINFO_EXTENSION)); @endphp
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
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </div>

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
        <script src="{{ asset('functions/verification-detail.js') }}?v={{ time() }}"></script>
    @endsection