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

        // Labels at documents galing sa AdminController (config/loan_labels.php)
        $w = $appDisplay['work'];

        // Place of birth (city, province, country) — walang laman = '—'
        $placeOfBirth = collect([$app->birth_city ?? null, $app->birth_province ?? null, $app->birth_country ?? null])
            ->filter(fn($v) => !empty($v))
            ->implode(', ');

        // Dagdag na address details (lalabas lang sa view mode kung may laman; sa edit mode lahat)
        $addressExtraFields = [
            'house_no' => 'House / Building No.',
            'street' => 'Street',
            'subdivision' => 'Subdivision / Village',
            'address_details' => 'Additional Details',
        ];

        // ---- Borrower info editing (galing sa AdminController) ----
        $canEditInfo = $canEditInfo ?? false;
        $infoEdits = $infoEdits ?? [];
        $editOptions = $editOptions ?? [];

        // Attributes ng isang field: class (may yellow kapag edited) + data-* para sa edit mode.
        // $o: max, req (required), any (listahan ng ibang keys na nakaapekto sa combined field).
        $fld = function (string $key, $raw = null, string $type = 'text', array $o = []) use ($canEditInfo, $infoEdits, $editOptions) {
            $edited = $key !== '' && isset($infoEdits[$key]);
            foreach (($o['any'] ?? []) as $k) {
                $edited = $edited || isset($infoEdits[$k]);
            }

            $html = 'class="ld-field' . ($edited ? ' is-edited' : '') . '"';

            if ($canEditInfo && $key !== '') {
                $html .= ' data-edit="' . e($key) . '" data-type="' . e($type) . '" data-raw="' . e((string) $raw) . '"';
                if (!empty($o['max'])) {
                    $html .= ' data-max="' . (int) $o['max'] . '"';
                }
                if (!empty($o['req'])) {
                    $html .= ' data-required="1"';
                }
                if ($type === 'select') {
                    $html .= ' data-options="' . e(json_encode($editOptions[$key] ?? [])) . '"';
                }
            }

            return $html;
        };

        // Maliit na note sa ilalim ng edited field: "Edited by Admin · Oct 28, 2026 · was: ..."
        $editNote = function ($keys, bool $showOld = true) use ($infoEdits) {
            $latest = null;
            foreach ((array) $keys as $k) {
                if (isset($infoEdits[$k]) && (!$latest || $infoEdits[$k]['ts'] > $latest['ts'])) {
                    $latest = $infoEdits[$k];
                }
            }
            if (!$latest) {
                return '';
            }

            $text = 'Edited by ' . e($latest['admin']) . ' · ' . e($latest['at']);
            if ($showOld) {
                $text .= ' · was: ' . e($latest['old']);
            }

            return '<span class="ld-edited-note"><i class="feather-edit-2"></i> ' . $text . '</span>';
        };

        // Edit / Cancel / Save buttons sa title ng card
        $editControls = function (string $section) use ($canEditInfo) {
            if (!$canEditInfo) {
                return '';
            }

            return '<span class="ld-edit-actions">'
                . '<button type="button" class="ld-edit-btn js-edit-start" data-section="' . $section . '"><i class="feather-edit-2"></i> Edit</button>'
                . '<span class="ld-edit-group">'
                . '<button type="button" class="ld-edit-btn js-edit-cancel">Cancel</button>'
                . '<button type="button" class="ld-edit-btn is-primary js-edit-save">Save</button>'
                . '</span></span>';
        };

        $yearsType = !empty($editOptions['years_in_company']) ? 'select' : 'text';
        $sourceType = !empty($editOptions['primary_source']) ? 'select' : 'text';
        $birthdayRaw = !empty($app->birthday) ? \Carbon\Carbon::parse($app->birthday)->format('Y-m-d') : '';
        $nameKeys = ['first_name', 'middle_name', 'last_name', 'suffix'];
        $birthKeys = ['birth_city', 'birth_province', 'birth_country'];
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

        @if (count($infoEdits) > 0)
            <div class="ld-edited-legend">
                <span class="ld-edited-dot"></span>
                Highlighted fields were corrected by an admin. The borrower was notified by email.
            </div>
        @endif

        {{-- ═══════════ ROW 1: Loan Summary | Source of Income ═══════════ --}}
        <div class="row g-4 ld-section-grid">

            <!-- Loan Summary -->
            <div class="col-lg-6">
                <div class="section-card">
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
                                    class="info-value">₱{{ number_format($app->monthly_payment ?? 0, 2) }}</span></div>
                        </div>
                        <div class="col-6">
                            <div class="ld-field"><span class="info-label">Applied</span><span
                                    class="info-value">{{ date('M d, Y', strtotime($app->applied_at)) }}</span></div>
                        </div>
                        <div class="col-6">
                            <div class="ld-field"><span class="info-label">Purpose</span><span
                                    class="info-value">{{ $appDisplay['purpose'] }}</span></div>
                        </div>
                        <div class="col-6">
                            <div class="ld-field"><span class="info-label">Where did they find us</span><span
                                    class="info-value">{{ $appDisplay['referral'] }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Source of Income (galing sa Apply for a Loan form, Step 3) -->
            <div class="col-lg-6">
                <div class="section-card" data-edit-section="income">
                    <p class="section-title"><span>Source of Income</span>{!! $w['has_data'] ? $editControls('income') : '' !!}</p>

                    @if (!$w['has_data'])
                        <p class="ld-discussion-empty mb-0">No source of income on file.</p>
                    @else
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="ld-field"><span class="info-label">Employment Status</span><span
                                        class="info-value">{{ $w['status_label'] }}@if (($work->employment_status ?? null) === 'others' && !empty($work->employment_status_other)) — {{ $work->employment_status_other }}@endif</span>
                                </div>
                            </div>

                            @if ($w['show_company'])
                                <div class="col-6">
                                    <div {!! $fld('company_name', $work->company_name ?? '', 'text', ['max' => 150]) !!}><span
                                            class="info-label">{{ $w['company_label'] }}</span><span
                                            class="info-value">{{ $w['company'] }}</span>{!! $editNote('company_name') !!}</div>
                                </div>
                            @endif

                            @if ($w['show_job'])
                                <div class="col-6">
                                    <div {!! $fld('position', $work->position ?? '', 'text', ['max' => 100]) !!}><span
                                            class="info-label">{{ $w['position_label'] }}</span><span
                                            class="info-value">{{ $w['position'] }}</span>{!! $editNote('position') !!}</div>
                                </div>
                                <div class="col-6">
                                    <div {!! $fld('years_in_company', $work->years_in_company ?? '', $yearsType, ['max' => 50]) !!}><span
                                            class="info-label">{{ $w['years_label'] }}</span><span
                                            class="info-value">{{ $w['years'] }}</span>{!! $editNote('years_in_company') !!}</div>
                                </div>
                            @endif

                            @if ($w['show_funds'])
                                <div class="col-6">
                                    <div {!! $fld('primary_source', $work->primary_source ?? '', $sourceType, ['max' => 100]) !!}><span class="info-label">Primary Source of
                                            Funds</span><span class="info-value">{{ $w['primary_source'] }}</span>{!! $editNote('primary_source') !!}</div>
                                </div>

                                {{-- Other Sources of Income: lalabas lang kapag "Others" ang Primary Source.
                                     Sa edit mode, laging may input para pwedeng punan kapag ginawang "Others". --}}
                                <div class="col-6 {{ $w['show_other_sources'] ? '' : 'ld-edit-only' }}">
                                    <div {!! $fld('other_sources', $work->other_sources ?? '', 'text', ['max' => 255]) !!}><span class="info-label">Other Sources of
                                            Income</span><span class="info-value">{{ $w['other_sources'] }}</span>{!! $editNote('other_sources') !!}</div>
                                </div>
                            @endif

                            <div class="col-6">
                                <div {!! $fld('monthly_income', $app->monthly_income ?? '', 'text', ['max' => 50]) !!}><span class="info-label">Monthly Income</span><span
                                        class="info-value text-success">{{ $app->monthly_income ?? null ? (is_numeric($app->monthly_income) ? '₱' . number_format($app->monthly_income, 2) : $app->monthly_income) : '—' }}</span>{!! $editNote('monthly_income') !!}
                                </div>
                            </div>

                            @if ($w['show_payslip'])
                                <div class="col-12">
                                    <label class="form-label fw-semibold"
                                        style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Pay
                                        Slip / Proof of Income</label><br>
                                    @if (!empty($appDocs['payslip']))
                                        @if ($appDocs['payslip']['is_image'])
                                            <span class="id-thumb-wrap"
                                                onclick="openIdViewer('{{ $appDocs['payslip']['url'] }}')">
                                                <img src="{{ $appDocs['payslip']['url'] }}"
                                                    alt="Pay Slip / Proof of Income">
                                            </span>
                                        @else
                                            <a href="{{ $appDocs['payslip']['url'] }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="feather-file me-1"></i> View Pay Slip
                                            </a>
                                        @endif
                                    @else
                                        <span class="info-value text-muted">No pay slip on file</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Proof of Billing: Unemployed, Retired, at Others lang --}}
                            @if ($w['show_billing'])
                                <div class="col-12">
                                    <label class="form-label fw-semibold"
                                        style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">Proof
                                        of Billing</label><br>
                                    @if (!empty($appDocs['billing']))
                                        @if ($appDocs['billing']['is_image'])
                                            <span class="id-thumb-wrap"
                                                onclick="openIdViewer('{{ $appDocs['billing']['url'] }}')">
                                                <img src="{{ $appDocs['billing']['url'] }}" alt="Proof of Billing">
                                            </span>
                                        @else
                                            <a href="{{ $appDocs['billing']['url'] }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="feather-file me-1"></i> View Proof of Billing
                                            </a>
                                        @endif
                                    @else
                                        <span class="info-value text-muted">No proof of billing on file</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════ ROW 2: Personal Information | Present Address ═══════════ --}}
        <div class="row g-4 ld-section-grid">

            <!-- Personal Information (galing sa Registration) -->
            <div class="col-lg-6">
                <div class="section-card" data-edit-section="personal">
                    <p class="section-title"><span>Personal Information</span>{!! $editControls('personal') !!}</p>
                    <div class="row g-3">
                        {{-- View mode: combined full name --}}
                        <div class="col-6 ld-view-only">
                            <div {!! $fld('', null, 'text', ['any' => $nameKeys]) !!}><span class="info-label">Full Name</span><span
                                    class="info-value">{{ $app->first_name }} {{ $app->middle_name }}
                                    {{ $app->last_name }}{{ $app->suffix ?? null ? ', ' . $app->suffix : '' }}</span>{!! $editNote($nameKeys, false) !!}
                            </div>
                        </div>

                        {{-- Edit mode: separate name fields --}}
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('first_name', $app->first_name, 'text', ['max' => 50, 'req' => true]) !!}><span class="info-label">First Name</span></div>
                        </div>
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('middle_name', $app->middle_name, 'text', ['max' => 50]) !!}><span class="info-label">Middle Name</span></div>
                        </div>
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('last_name', $app->last_name, 'text', ['max' => 50, 'req' => true]) !!}><span class="info-label">Last Name</span></div>
                        </div>
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('suffix', $app->suffix, 'text', ['max' => 10]) !!}><span class="info-label">Suffix</span></div>
                        </div>

                        <div class="col-6">
                            <div {!! $fld('birthday', $birthdayRaw, 'date', ['req' => true]) !!}><span class="info-label">Birthday</span><span
                                    class="info-value">{{ $app->birthday ?? null ? \Carbon\Carbon::parse($app->birthday)->format('F d, Y') : '—' }}</span>{!! $editNote('birthday') !!}
                            </div>
                        </div>

                        {{-- View mode: combined place of birth --}}
                        <div class="col-6 ld-view-only">
                            <div {!! $fld('', null, 'text', ['any' => $birthKeys]) !!}><span class="info-label">Place of Birth</span><span
                                    class="info-value">{{ $placeOfBirth !== '' ? $placeOfBirth : '—' }}</span>{!! $editNote($birthKeys, false) !!}</div>
                        </div>

                        {{-- Edit mode: separate place of birth fields --}}
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('birth_city', $app->birth_city ?? '', 'text', ['max' => 100]) !!}><span class="info-label">Birth City</span></div>
                        </div>
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('birth_province', $app->birth_province ?? '', 'text', ['max' => 100]) !!}><span class="info-label">Birth Province</span></div>
                        </div>
                        <div class="col-6 ld-edit-only">
                            <div {!! $fld('birth_country', $app->birth_country ?? '', 'text', ['max' => 100]) !!}><span class="info-label">Birth Country</span></div>
                        </div>

                        <div class="col-6">
                            <div {!! $fld('gender', $app->gender ?? '', 'select', ['req' => true]) !!}><span class="info-label">Gender</span><span
                                    class="info-value">{{ ucfirst($app->gender ?? '—') }}</span>{!! $editNote('gender') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('civil_status', $app->civil_status ?? '', 'select', ['req' => true]) !!}><span class="info-label">Civil Status</span><span
                                    class="info-value">{{ ucfirst($app->civil_status ?? '—') }}</span>{!! $editNote('civil_status') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('citizenship', $app->citizenship ?? '', 'text', ['max' => 60, 'req' => true]) !!}><span class="info-label">Citizenship</span><span
                                    class="info-value">{{ $app->citizenship ?? '—' }}</span>{!! $editNote('citizenship') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('cellphone_num', $app->cellphone_num ?? '', 'tel', ['max' => 20, 'req' => true]) !!}><span class="info-label">Cellphone</span><span
                                    class="info-value">{{ $app->cellphone_num ?? '—' }}</span>{!! $editNote('cellphone_num') !!}</div>
                        </div>
                        <div class="col-6">
                            <div class="ld-field"><span class="info-label">Email @if ($canEditInfo)<i class="feather-lock ld-lock-icon" title="Email is the borrower's login, so it can't be edited here"></i>@endif</span><span
                                    class="info-value" style="word-break:break-all;">{{ $app->email }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Present Address (galing sa Registration) -->
            <div class="col-lg-6">
                <div class="section-card" data-edit-section="address">
                    <p class="section-title"><span>Present Address</span>{!! $editControls('address') !!}</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div {!! $fld('region', $address->region ?? '', 'text', ['max' => 100, 'req' => true]) !!}><span class="info-label">Region</span><span
                                    class="info-value">{{ $address->region ?? '—' }}</span>{!! $editNote('region') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('province', $address->province ?? '', 'text', ['max' => 100, 'req' => true]) !!}><span class="info-label">Province</span><span
                                    class="info-value">{{ $address->province ?? '—' }}</span>{!! $editNote('province') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('city', $address->city ?? '', 'text', ['max' => 100, 'req' => true]) !!}><span class="info-label">City / Municipality</span><span
                                    class="info-value">{{ $address->city ?? '—' }}</span>{!! $editNote('city') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('barangay', $address->barangay ?? '', 'text', ['max' => 100, 'req' => true]) !!}><span class="info-label">Barangay</span><span
                                    class="info-value">{{ $address->barangay ?? '—' }}</span>{!! $editNote('barangay') !!}</div>
                        </div>
                        <div class="col-6">
                            <div {!! $fld('zipcode', $address->zipcode ?? '', 'text', ['max' => 10]) !!}><span class="info-label">Zipcode</span><span
                                    class="info-value">{{ !empty($address->zipcode ?? null) ? $address->zipcode : '—' }}</span>{!! $editNote('zipcode') !!}</div>
                        </div>
                        @foreach ($addressExtraFields as $extraKey => $extraLabel)
                            @php $extraValue = $address->{$extraKey} ?? null; @endphp
                            <div class="col-6 {{ empty($extraValue) ? 'ld-edit-only' : '' }}">
                                <div {!! $fld($extraKey, $extraValue ?? '', 'text', ['max' => 255]) !!}><span class="info-label">{{ $extraLabel }}</span><span
                                        class="info-value">{{ $extraValue }}</span>{!! $editNote($extraKey) !!}</div>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <div {!! $fld('home_address', $address->home_address ?? '', 'text', ['max' => 255]) !!}><span class="info-label">Home Address</span><span
                                    class="info-value">{{ !empty($address->home_address ?? null) ? $address->home_address : '—' }}</span>{!! $editNote('home_address') !!}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════ Valid ID (full width) ═══════════ --}}
        <div class="section-card" data-edit-section="id">
            <p class="section-title"><span>Submitted Valid ID</span>{!! $editControls('id') !!}</p>
            <div class="row g-3 mb-1">
                <div class="col-md-6">
                    <div class="ld-field"><span class="info-label">ID Type</span><span
                            class="info-value">{{ $appDisplay['id_type'] }}</span></div>
                </div>
                <div class="col-md-6">
                    <div {!! $fld('id_number', $app->id_number ?? '', 'text', ['max' => 50, 'req' => true]) !!}><span class="info-label">ID Number</span><span
                            class="info-value">{{ !empty($app->id_number) ? $app->id_number : '—' }}</span>{!! $editNote('id_number') !!}</div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                @php
                    $idPictures = [
                        ['key' => 'front', 'title' => 'Front ID', 'alt' => 'Valid ID — Front', 'empty' => 'No front ID on file'],
                        ['key' => 'back', 'title' => 'Back ID', 'alt' => 'Valid ID — Back', 'empty' => 'No back ID on file'],
                        ['key' => 'selfie', 'title' => 'Selfie with ID', 'alt' => 'Selfie with ID', 'empty' => 'No selfie on file'],
                    ];
                @endphp

                @foreach ($idPictures as $pic)
                    @php $doc = $appDocs[$pic['key']] ?? null; @endphp
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"
                            style="font-size:0.7rem; color:#8a9bb0; text-transform:uppercase; letter-spacing:0.4px;">{{ $pic['title'] }}</label><br>
                        @if ($doc)
                            @if ($doc['is_image'])
                                <span class="id-thumb-wrap" onclick="openIdViewer('{{ $doc['url'] }}')">
                                    <img src="{{ $doc['url'] }}" alt="{{ $pic['alt'] }}">
                                </span>
                            @else
                                <a href="{{ $doc['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="feather-file me-1"></i> View {{ $pic['title'] }}
                                </a>
                            @endif
                        @else
                            <span class="info-value text-muted">{{ $pic['empty'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="mt-3 mb-0" style="font-size:0.78rem; color:#8a9bb0;">
                Click a picture to enlarge it. Check that the face in the selfie matches the face on the ID.
            </p>
        </div>

        {{-- ═══════════ Admin Review Status (full width) ═══════════ --}}
        <div class="section-card">
            <p class="section-title">Admin Review Status ({{ $approvedCount ?? 0 }}/3 approved)</p>
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

        {{-- ═══════════ Payment Schedule (full width) ═══════════ --}}
        @if ($paymentSchedule->isNotEmpty())
            <div class="section-card">
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
                                    $isOverdue = is_null($s->paid_at) && \Carbon\Carbon::parse($s->due_date)->isPast();
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
                                    <td>{{ $s->paid_at ? \Carbon\Carbon::parse($s->paid_at)->format('M d, Y') : '—' }}</td>
                                    <td><span class="ld-schedule-badge {{ $scheduleBadge }}">{{ $scheduleLabel }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ═══════════ Discussion (full width) ═══════════ --}}
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

@push('modals')

    {{-- ============================================
         EDIT BORROWER INFO — confirm + reason
    ============================================ --}}
    @if ($canEditInfo)
        <div class="modal fade modal-modern" id="editInfoModal" tabindex="-1" aria-labelledby="editInfoTitle"
            aria-hidden="true" data-save-url="{{ route('admin.loan-application-borrower-info', $applicationId) }}">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header-clean">
                        <div class="header-left">
                            <h6 id="editInfoTitle">Save changes</h6>
                        </div>
                        <button type="button" class="btn-close-clean" data-bs-dismiss="modal"
                            aria-label="Close">&times;</button>
                    </div>

                    <div class="modal-body">
                        <p class="ld-edit-modal-desc">The borrower will be emailed about these changes.</p>

                        <div class="ld-change-list" id="editInfoChanges"></div>

                        <label class="form-label fw-semibold" for="editInfoReason" style="font-size:0.85rem;">Reason for
                            the change <span class="text-danger">*</span></label>
                        <textarea id="editInfoReason" class="form-control" rows="3" maxlength="255"
                            placeholder="e.g. Borrower mistyped the barangay"></textarea>
                        <div class="decision-char-counter"><span id="editInfoReasonCount">0</span>/255</div>

                        <div class="ld-edit-error" id="editInfoError" style="display:none;"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-decision btn-decision-cancel"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-decision btn-decision-black" id="editInfoConfirmBtn">Save
                            &amp; notify borrower</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================
             EDIT BORROWER INFO — success (lalabas pagkatapos ma-save)
        ============================================ --}}
        <div class="modal fade modal-modern" id="editInfoSuccessModal" tabindex="-1"
            aria-labelledby="editInfoSuccessTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header-clean">
                        <div class="header-left">
                            <h6 id="editInfoSuccessTitle">Information updated</h6>
                        </div>
                        <button type="button" class="btn-close-clean" data-bs-dismiss="modal"
                            aria-label="Close">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="modal-icon-circle icon-success">
                            <i class="feather-check"></i>
                        </div>
                        <p class="modal-title-text">Changes saved</p>
                        <p class="modal-subtext-text" id="editInfoSuccessMessage"></p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-decision btn-decision-black"
                            data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
    <script src="{{ asset('functions/loan-application-detail.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('functions/remove-blur.js') }}"></script>
@endsection