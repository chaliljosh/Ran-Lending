@extends('admin.layout')

@section('title', 'Borrower Details')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/modals-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/avatar-placeholder.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/ongoing-loan-detail.css') }}" />
    {{-- Same design ng Completed Loan Detail (cld-*), kaya pareho ang laki ng boxes at text --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/completed-loan-detail.css') }}?v={{ time() }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/detail-icons.css') }}?v={{ time() }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/darkmode-global.css') }}?v={{ time() }}" />
@endsection

@section('content')

    @php
        $loanStats = $loanStats ?? [];
        $borrowerPhotoUrl = $borrowerProfilePicture ?? null;

        // Borrower ID = BRW- + unang 8 characters ng UUID ng borrower (kapareho ng Ongoing Loans, Overdue, atbp.)
        // Ang $d->uuid ay galing sa bpd.* ng verificationDetail(). Kapag wala, numeric ID ang ipapakita.
        $borrowerCode = !empty($d->uuid) ? 'BRW-' . strtoupper(substr($d->uuid, 0, 8)) : $detailId;

        // Full name (with middle name and suffix)
        $fullName = trim(($d->first_name ?? '') . ' ' . ($d->middle_name ?? '') . ' ' . ($d->last_name ?? ''));
        if (!empty($d->suffix)) {
            $fullName .= ', ' . $d->suffix;
        }

        // Monthly income: kung number, lagyan ng peso sign. Kung range/text, ipakita as is.
        $monthlyIncome = $d->monthly_income ?? null;
        if (empty($monthlyIncome)) {
            $monthlyIncome = '—';
        } elseif (is_numeric($monthlyIncome)) {
            $monthlyIncome = '₱' . number_format($monthlyIncome, 2);
        }
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Borrower Details</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.borrowers') }}">All Borrowers</a></li>
                <li class="breadcrumb-item">{{ $borrowerCode }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('admin.borrowers') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="main-content cld-page vd-page">

        {{-- ============ BORROWER HEADER + STATS (merged) ============ --}}
        <div class="cld-summary-card mb-3">
            <div class="cld-summary-left">
                <div class="cld-avatar js-borrower-avatar" data-initials="">
                    @if ($borrowerPhotoUrl)
                        <img src="{{ $borrowerPhotoUrl }}" alt="{{ $d->first_name }} {{ $d->last_name }}" class="cld-avatar-img">
                    @endif
                </div>
                <div class="cld-borrower-info">
                    <h5>{{ $d->first_name }} {{ $d->last_name }}</h5>
                    <div class="cld-borrower-meta">
                        <span>{{ $d->reg_email }}</span>
                        <span class="cld-dot">&middot;</span>
                        <span>Borrower ID: <strong>{{ $borrowerCode }}</strong></span>
                    </div>
                </div>
            </div>
            <div class="cld-summary-right">
                <div class="cld-stat">
                    <i class="feather-layers cld-stat-icon"></i>
                    <div class="cld-stat-text">
                        <span class="cld-stat-value">{{ $loanStats['total'] ?? '—' }}</span>
                        <span class="cld-stat-label">Total Loans</span>
                    </div>
                </div>
                <div class="cld-stat">
                    <i class="feather-activity cld-stat-icon"></i>
                    <div class="cld-stat-text">
                        <span class="cld-stat-value">{{ $loanStats['active'] ?? '—' }}</span>
                        <span class="cld-stat-label">Active</span>
                    </div>
                </div>
                <div class="cld-stat">
                    <i class="feather-check-circle cld-stat-icon"></i>
                    <div class="cld-stat-text">
                        <span class="cld-stat-value">{{ $loanStats['completed'] ?? '—' }}</span>
                        <span class="cld-stat-label">Completed</span>
                    </div>
                </div>
                <div class="cld-stat">
                    <i class="feather-alert-circle cld-stat-icon"></i>
                    <div class="cld-stat-text">
                        <span class="cld-stat-value">{{ $loanStats['overdue'] ?? '—' }}</span>
                        <span class="cld-stat-label">Overdue</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ PERSONAL INFORMATION ============ --}}
        <div class="panel">
            <div class="panel-header"><i class="feather-user cld-hicon"></i>Personal Information</div>
            <div class="panel-body">
                <div class="vd-grid">
                    <div class="info-item2">
                        <label>Full Name</label>
                        <span>{{ $fullName }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Last Name</label>
                        <span>{{ $d->last_name ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>First Name</label>
                        <span>{{ $d->first_name ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Middle Name</label>
                        <span>{{ !empty($d->middle_name) ? $d->middle_name : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Suffix</label>
                        <span>{{ !empty($d->suffix) ? $d->suffix : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Birthday</label>
                        <span>{{ $d->birthday ? \Carbon\Carbon::parse($d->birthday)->format('F d, Y') : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Age</label>
                        <span>{{ $d->birthday ? \Carbon\Carbon::parse($d->birthday)->age . ' yrs old' : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Gender</label>
                        <span>{{ ucfirst($d->gender ?? '—') }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Civil Status</label>
                        <span>{{ ucfirst($d->civil_status ?? '—') }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Citizenship</label>
                        <span>{{ $d->citizenship ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Cellphone</label>
                        <span>{{ $d->cellphone_num ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Email</label>
                        <span>{{ $d->reg_email }}</span>
                    </div>
                    <div class="info-item2 is-primary">
                        <label>Monthly Income</label>
                        <span>{{ $monthlyIncome }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ BIRTH PLACE ============ --}}
        <div class="panel">
            <div class="panel-header"><i class="feather-flag cld-hicon"></i>Birth Place</div>
            <div class="panel-body">
                <div class="vd-grid">
                    <div class="info-item2">
                        <label>Country</label>
                        <span>{{ !empty($d->birth_country) ? $d->birth_country : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Province</label>
                        <span>{{ !empty($d->birth_province) ? $d->birth_province : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>City</label>
                        <span>{{ !empty($d->birth_city) ? $d->birth_city : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ PRESENT ADDRESS ============ --}}
        <div class="panel">
            <div class="panel-header"><i class="feather-map-pin cld-hicon"></i>Present Address</div>
            <div class="panel-body">
                <div class="vd-grid">
                    <div class="info-item2">
                        <label>Country</label>
                        <span>{{ $d->country ?? 'Philippines' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Region</label>
                        <span>{{ $d->region ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Province</label>
                        <span>{{ $d->province ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>City / Municipality</label>
                        <span>{{ $d->city ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Barangay</label>
                        <span>{{ $d->barangay ?? '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Zipcode</label>
                        <span>{{ !empty($d->zipcode) ? $d->zipcode : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>House / Building No.</label>
                        <span>{{ !empty($d->house_no) ? $d->house_no : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Street / Sitio / Zone</label>
                        <span>{{ !empty($d->street) ? $d->street : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Subdivision / Village</label>
                        <span>{{ !empty($d->subdivision) ? $d->subdivision : '—' }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Landmark</label>
                        <span>{{ !empty($d->address_details) ? $d->address_details : '—' }}</span>
                    </div>
                    <div class="info-item2 vd-span-2">
                        <label>Home Address</label>
                        <span>{{ !empty($d->home_address) ? $d->home_address : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/verification-detail.js') }}?v={{ time() }}"></script>
@endsection