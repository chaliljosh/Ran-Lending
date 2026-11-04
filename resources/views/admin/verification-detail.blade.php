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

        // Helper: ipakita ang value o '—' kapag walang laman
        $v = fn($val) => (!is_null($val) && trim((string) $val) !== '') ? $val : '—';

        // ---- Optional data galing sa controller (kung wala, '—' ang lalabas) ----
        // $work       = work/income row ng borrower (employment_status, company_name, position, ...)
        // $appDisplay = labels galing sa config/loan_labels.php (kung meron)
        // $appDocs    = ['front','back','selfie','payslip','billing' => ['url','is_image']]
        $work = $work ?? null;
        $w = $appDisplay['work'] ?? null;
        $docs = $appDocs ?? ($borrowerDocs ?? []);

        // Permanent address: kung "same as present" at walang naka-save, gamitin ang present address
        $permSame = !empty($d->perm_same_as_current ?? null);
        $permFields = [
            'Region'                                => ['perm_region', 'region'],
            'Province'                              => ['perm_province', 'province'],
            'City / Municipality'                   => ['perm_city', 'city'],
            'Barangay'                              => ['perm_barangay', 'barangay'],
            'Zipcode'                               => ['perm_zipcode', 'zipcode'],
        ];
        $permHome = !empty($d->perm_home_address ?? null)
            ? $d->perm_home_address
            : ($permSame ? ($d->home_address ?? null) : null);

        // Source of income labels
        $employmentStatus = $w['status_label'] ?? ucfirst(str_replace('_', ' ', $work->employment_status ?? $d->employment_status ?? ''));
        if (($work->employment_status ?? $d->employment_status ?? null) === 'others' && !empty($work->employment_status_other ?? null)) {
            $employmentStatus .= ' — ' . $work->employment_status_other;
        }

        $companyLabel  = $w['company_label'] ?? 'Company / Business Name';
        $positionLabel = $w['position_label'] ?? 'Position';
        $yearsLabel    = $w['years_label'] ?? 'Years in Company';

        $companyVal  = $w['company'] ?? ($work->company_name ?? $d->company_name ?? null);
        $positionVal = $w['position'] ?? ($work->position ?? $d->position ?? null);
        $yearsVal    = $w['years'] ?? ($work->years_in_company ?? $d->years_in_company ?? null);
        $primaryVal  = $w['primary_source'] ?? ($work->primary_source ?? $d->primary_source ?? null);
        $otherVal    = $w['other_sources'] ?? ($work->other_sources ?? $d->other_sources ?? null);

        // ID type: gamitin ang label kung meron
        $idTypeVal = $appDisplay['id_type'] ?? ($d->id_type ?? null);
        $idNumVal  = $d->id_number ?? null;

        $idPictures = [
            ['key' => 'front',  'title' => 'Front ID',       'alt' => 'Valid ID — Front', 'empty' => 'No front ID on file'],
            ['key' => 'back',   'title' => 'Back ID',        'alt' => 'Valid ID — Back',  'empty' => 'No back ID on file'],
            ['key' => 'selfie', 'title' => 'Selfie with ID', 'alt' => 'Selfie with ID',   'empty' => 'No selfie on file'],
        ];

        $docThumbStyle = 'max-width:100%; max-height:180px; border-radius:8px; border:1px solid #e5eaf0; display:block; cursor:zoom-in;';
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

        {{-- ============ PERMANENT ADDRESS ============ --}}
        <div class="panel">
            <div class="panel-header">
                <i class="feather-home cld-hicon"></i>Permanent Address
                @if ($permSame)
                    <span class="badge badge-approved px-2 py-1 ms-2" style="font-size:0.7rem;">Same as present address</span>
                @endif
            </div>
            <div class="panel-body">
                <div class="vd-grid">
                    <div class="info-item2">
                        <label>Country</label>
                        <span>{{ $d->country ?? 'Philippines' }}</span>
                    </div>
                    @foreach ($permFields as $label => [$permKey, $presentKey])
                        @php
                            $pv = $d->{$permKey} ?? null;
                            $shown = !empty($pv) ? $pv : ($permSame ? ($d->{$presentKey} ?? null) : null);
                        @endphp
                        <div class="info-item2">
                            <label>{{ $label }}</label>
                            <span>{{ $v($shown) }}</span>
                        </div>
                    @endforeach
                    <div class="info-item2 vd-span-2">
                        <label>House No., Street, Subdivision / Sitio</label>
                        <span>{{ $v($permHome) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ SOURCE OF INCOME ============ --}}
        <div class="panel">
            <div class="panel-header"><i class="feather-briefcase cld-hicon"></i>Source of Income</div>
            <div class="panel-body">
                <div class="vd-grid">
                    <div class="info-item2">
                        <label>Employment Status</label>
                        <span>{{ $v(trim($employmentStatus)) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>{{ $companyLabel }}</label>
                        <span>{{ $v($companyVal) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>{{ $positionLabel }}</label>
                        <span>{{ $v($positionVal) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>{{ $yearsLabel }}</label>
                        <span>{{ $v($yearsVal) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Primary Source of Funds</label>
                        <span>{{ $v($primaryVal) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>Other Sources of Income</label>
                        <span>{{ $v($otherVal) }}</span>
                    </div>
                    <div class="info-item2 is-primary">
                        <label>Monthly Income</label>
                        <span>{{ $monthlyIncome }}</span>
                    </div>

                    <div class="info-item2">
                        <label>Pay Slip / Proof of Income</label>
                        @if (!empty($docs['payslip']))
                            @if ($docs['payslip']['is_image'])
                                <a href="{{ $docs['payslip']['url'] }}" class="js-img-zoom"
                                   data-full="{{ $docs['payslip']['url'] }}" data-title="Pay Slip / Proof of Income">
                                    <img src="{{ $docs['payslip']['url'] }}" alt="Pay Slip / Proof of Income" style="{{ $docThumbStyle }}">
                                </a>
                            @else
                                <a href="{{ $docs['payslip']['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="feather-file me-1"></i> View Pay Slip
                                </a>
                            @endif
                        @else
                            <span class="text-muted">No pay slip on file</span>
                        @endif
                    </div>

                    <div class="info-item2">
                        <label>Proof of Billing</label>
                        @if (!empty($docs['billing']))
                            @if ($docs['billing']['is_image'])
                                <a href="{{ $docs['billing']['url'] }}" class="js-img-zoom"
                                   data-full="{{ $docs['billing']['url'] }}" data-title="Proof of Billing">
                                    <img src="{{ $docs['billing']['url'] }}" alt="Proof of Billing" style="{{ $docThumbStyle }}">
                                </a>
                            @else
                                <a href="{{ $docs['billing']['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="feather-file me-1"></i> View Proof of Billing
                                </a>
                            @endif
                        @else
                            <span class="text-muted">No proof of billing on file</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ SUBMITTED VALID ID ============ --}}
        <div class="panel">
            <div class="panel-header"><i class="feather-credit-card cld-hicon"></i>Submitted Valid ID</div>
            <div class="panel-body">
                <div class="vd-grid">
                    <div class="info-item2">
                        <label>ID Type</label>
                        <span>{{ $v($idTypeVal) }}</span>
                    </div>
                    <div class="info-item2">
                        <label>ID Number</label>
                        <span>{{ $v($idNumVal) }}</span>
                    </div>

                    @foreach ($idPictures as $pic)
                        @php $doc = $docs[$pic['key']] ?? null; @endphp
                        <div class="info-item2">
                            <label>{{ $pic['title'] }}</label>
                            @if ($doc)
                                @if ($doc['is_image'])
                                    <a href="{{ $doc['url'] }}" class="js-img-zoom"
                                       data-full="{{ $doc['url'] }}" data-title="{{ $pic['title'] }}">
                                        <img src="{{ $doc['url'] }}" alt="{{ $pic['alt'] }}" style="{{ $docThumbStyle }}">
                                    </a>
                                @else
                                    <a href="{{ $doc['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="feather-file me-1"></i> View {{ $pic['title'] }}
                                    </a>
                                @endif
                            @else
                                <span class="text-muted">{{ $pic['empty'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- ============ IMAGE ZOOM (lightbox) ============ --}}
    <div id="imgZoomOverlay"
         style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,.8); align-items:center; justify-content:center; padding:24px; cursor:zoom-out;">
        <div style="position:relative; max-width:90vw; max-height:90vh; text-align:center;">
            <button type="button" id="imgZoomClose" aria-label="Close"
                    style="position:absolute; top:-14px; right:-14px; width:32px; height:32px; border-radius:50%; border:0; background:#fff; color:#111; font-size:18px; line-height:1; cursor:pointer;">&times;</button>
            <img id="imgZoomImg" src="" alt=""
                 style="max-width:90vw; max-height:85vh; border-radius:8px; display:block; margin:0 auto; background:#fff;">
            <div id="imgZoomTitle" style="color:#fff; margin-top:8px; font-size:.9rem;"></div>
        </div>
    </div>

@endsection

@section('page-scripts')
    <script src="{{ asset('functions/verification-detail.js') }}?v={{ time() }}"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('imgZoomOverlay');
        var img     = document.getElementById('imgZoomImg');
        var title   = document.getElementById('imgZoomTitle');
        var closeBtn = document.getElementById('imgZoomClose');

        if (!overlay) return;

        function openZoom(url, text) {
            img.src = url;
            title.textContent = text || '';
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeZoom() {
            overlay.style.display = 'none';
            img.src = '';
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.js-img-zoom').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                openZoom(a.dataset.full, a.dataset.title);
            });
        });

        overlay.addEventListener('click', function (e) {
            if (e.target !== img) closeZoom();
        });

        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeZoom();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.style.display !== 'none') closeZoom();
        });
    });
    </script>
@endsection