@extends('admin.layout')

@section('title', 'Payment Methods')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/payment-methods.css') }}?v={{ time() }}">
@endsection

@section('content')

    @php
        // Finds the logo file for each known bank / e-wallet. Supports png, jpg, jpeg, webp.
        // Files live in: public/assets/admin/images/banks/  (lowercase names, e.g. gcash.jpg)
        $logoMap = [];
        foreach (['gcash', 'maya', 'bpi', 'bdo', 'metrobank', 'unionbank', 'landbank'] as $key) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                if (file_exists(public_path("assets/admin/images/banks/{$key}.{$ext}"))) {
                    $logoMap[$key] = "{$key}.{$ext}";
                    break;
                }
            }
        }
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Payment Methods</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Payment Methods</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <button type="button" class="pm-btn pm-btn-primary" id="pmAddBtn">
                <i class="feather-plus"></i> Add Payment Method
            </button>
        </div>
    </div>

    <div class="main-content pm-page" id="pmPage"
        data-store-url="{{ route('admin.payment-methods.store') }}"
        data-logo-base="{{ asset('assets/admin/images/banks') }}"
        data-logo-map="{{ json_encode($logoMap) }}">

        <div class="pm-panel">
            <div class="pm-panel-header">
                <div>
                    <div class="pm-panel-title">Manage Payment Accounts</div>
                    <div class="pm-panel-sub">Add and manage payment accounts that can be assigned to borrowers for loan repayments.</div>
                </div>
                <div class="pm-count-pill">
                    <i class="feather-credit-card"></i>
                    <span><strong>{{ $methods->count() }}</strong> {{ \Illuminate\Support\Str::plural('account', $methods->count()) }}</span>
                </div>
            </div>

            <div class="pm-panel-body">
                @if ($methods->count() > 0)
                    <div class="pm-list">
                        @foreach ($methods as $m)
                            @php
                                $logoFile = $logoMap[strtolower($m->provider)] ?? null;
                            @endphp
                            <div class="pm-item {{ $m->is_active ? '' : 'is-inactive' }}">
                                <div class="pm-item-top">
                                    <span class="pm-item-provider">
                                        <span class="pm-logo">
                                            @if ($logoFile)
                                                <img src="{{ asset('assets/admin/images/banks/' . $logoFile) }}" alt="{{ $m->provider }}" width="30" height="30">
                                            @else
                                                <span class="pm-logo-fallback">{{ strtoupper(mb_substr($m->provider, 0, 2)) }}</span>
                                            @endif
                                        </span>
                                        {{ $m->provider }}
                                    </span>
                                    <span class="pm-pill {{ $m->is_active ? 'is-active' : 'is-inactive' }}">
                                        {{ $m->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <div class="pm-item-grid">
                                    <div class="pm-item-field">
                                        <label>Account Holder</label>
                                        <span>{{ $m->account_name }}</span>
                                    </div>
                                    <div class="pm-item-field">
                                        <label>Account Number</label>
                                        <span class="pm-mono">{{ $m->account_number }}</span>
                                    </div>
                                    <div class="pm-item-field">
                                        <label>Loans Using It</label>
                                        <span>{{ $m->loans_count }}</span>
                                    </div>
                                </div>

                                <div class="pm-item-actions">
                                    <button type="button" class="pm-btn pm-btn-sm js-pm-edit"
                                        data-update-url="{{ route('admin.payment-methods.update', $m->id) }}"
                                        data-provider="{{ $m->provider }}"
                                        data-account-name="{{ $m->account_name }}"
                                        data-account-number="{{ $m->account_number }}">
                                        Edit
                                    </button>

                                    <button type="button"
                                        class="pm-btn pm-btn-sm js-pm-toggle {{ $m->is_active ? 'pm-btn-danger' : '' }}"
                                        data-toggle-url="{{ route('admin.payment-methods.toggle', $m->id) }}"
                                        data-active="{{ $m->is_active ? '1' : '0' }}"
                                        data-label="{{ $m->provider }} — {{ $m->account_name }}"
                                        data-loans="{{ $m->loans_count }}">
                                        {{ $m->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>

                                    {{-- Delete: inactive accounts only. Active ones must be deactivated first.
                                         If loans still use it, they are unassigned when it is deleted. --}}
                                    @if (!$m->is_active)
                                        <button type="button" class="pm-btn pm-btn-sm pm-btn-danger js-pm-delete"
                                            data-delete-url="{{ route('admin.payment-methods.destroy', $m->id) }}"
                                            data-label="{{ $m->provider }} — {{ $m->account_name }}"
                                            data-loans="{{ $m->loans_count }}">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="pm-empty">
                        <strong>No payment methods yet</strong>
                        Add a GCash, Maya or bank account so you can assign it to a loan.
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('modals')
    {{-- ADD / EDIT --}}
    <div class="modal fade pm-modal" id="pmFormModal" tabindex="-1" aria-labelledby="pmFormTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="pmFormTitle">Add Payment Method</h6>
                    <button type="button" class="btn-close" id="pmFormCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="pm-field">
                        <label for="pmProvider" class="pm-label">Bank / E-wallet <span class="pm-req">*</span></label>
                        <div class="pm-select-row">
                            <span class="pm-logo" id="pmProviderLogo"><i class="feather-credit-card"></i></span>
                            <select id="pmProvider" class="pm-input">
                                <option value="">Select one</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="BPI">BPI</option>
                                <option value="BDO">BDO</option>
                                <option value="Metrobank">Metrobank</option>
                                <option value="UnionBank">UnionBank</option>
                                <option value="Landbank">Landbank</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <input type="text" id="pmProviderOther" class="pm-input" maxlength="60"
                            placeholder="Type the bank or e-wallet name" autocomplete="off"
                            style="display:none; margin-top:8px;">
                    </div>

                    <div class="pm-grid-2">
                        <div class="pm-field">
                            <label for="pmAccountName" class="pm-label">Account Holder <span class="pm-req">*</span></label>
                            <input type="text" id="pmAccountName" class="pm-input" maxlength="120"
                                placeholder="e.g. Juan Dela Cruz" autocomplete="off">
                        </div>
                        <div class="pm-field">
                            <label for="pmAccountNumber" class="pm-label">Account Number <span class="pm-req">*</span></label>
                            <input type="text" id="pmAccountNumber" class="pm-input" maxlength="20" inputmode="numeric"
                                placeholder="Select a bank / e-wallet first" autocomplete="off">
                            <div id="pmNumberHint" class="pm-hint" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="pm-error" id="pmFormError" style="display:none;"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="pm-btn" id="pmFormCancelBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="pm-btn pm-btn-primary" id="pmFormSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ACTIVATE / DEACTIVATE --}}
    <div class="modal fade pm-modal" id="pmToggleModal" tabindex="-1" aria-labelledby="pmToggleTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="pmToggleTitle">Deactivate payment method?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="pm-confirm-text" id="pmToggleText"></p>
                    <div class="pm-error" id="pmToggleError" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="pm-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="pm-btn pm-btn-primary" id="pmToggleConfirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    {{-- DELETE --}}
    <div class="modal fade pm-modal" id="pmDeleteModal" tabindex="-1" aria-labelledby="pmDeleteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="pmDeleteTitle">Delete payment method?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="pm-confirm-text" id="pmDeleteText"></p>
                    <div class="pm-error" id="pmDeleteError" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="pm-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="pm-btn pm-btn-danger-solid" id="pmDeleteConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- SUCCESS --}}
    <div class="modal fade pm-modal pm-success-modal" id="pmSuccessModal" tabindex="-1" aria-labelledby="pmSuccessTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body pm-success-body">
                    <div class="pm-success-icon"><i class="feather-check"></i></div>
                    <div class="pm-success-title" id="pmSuccessTitle">Account Added Successfully!</div>
                    <p class="pm-success-text" id="pmSuccessText"></p>

                    <div class="pm-success-details">
                        <div class="pm-success-row">
                            <label>Payment Provider</label>
                            <strong id="pmSuccessProvider"></strong>
                        </div>
                        <div class="pm-success-row">
                            <label>Account Holder</label>
                            <strong id="pmSuccessHolder"></strong>
                        </div>
                        <div class="pm-success-row">
                            <label>Status</label>
                            <span class="pm-pill is-active" id="pmSuccessStatus">Active</span>
                        </div>
                    </div>

                    <button type="button" class="pm-success-done" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('page-scripts')
   <script src="{{ asset('functions/payment-methods.js') }}?v={{ time() }}"></script>
@endsection