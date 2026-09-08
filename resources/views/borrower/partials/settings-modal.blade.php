<div class="modal fade bwl-acct-modal" id="bwlSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bwl-acct-header">
                <h6 class="modal-title">Account Settings</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                {{-- ============ PROFILE HEADER ============ --}}
                <div class="bwl-acct-top">
                    <div class="bwl-acct-avatar-wrap">
                        <div class="bwl-acct-avatar js-borrower-avatar" id="bwlSetAvatarPreview" data-initials="{{ $initials }}">
                            @if($profilePicture ?? false)
                                <img src="{{ $profilePicture }}" alt="{{ $userName }}"
                                     onerror="this.closest('.js-borrower-avatar').textContent = this.closest('.js-borrower-avatar').dataset.initials;">
                            @else
                                {{ $initials }}
                            @endif
                        </div>
                        <button type="button" class="bwl-acct-camera-btn" id="bwlSetChangePhotoBtn" title="Change photo"
                                data-upload-url="{{ route('borrower.profile.upload-picture') }}">
                            <i class="feather-camera"></i>
                        </button>
                        <input type="file" id="bwlSetFileInput" accept="image/png,image/jpeg,image/webp" hidden>
                    </div>

                    <div class="bwl-acct-info">
                        <div class="bwl-acct-name">{{ $userName }}</div>
                        <div class="bwl-acct-meta">
                            <span>Borrower</span>
                            <span class="bwl-acct-dot">&bull;</span>
                            <span class="bwl-acct-status-inline {{ ($isActive ?? true) ? 'is-active' : 'is-inactive' }}">
                                <span class="bwl-acct-status-dot"></span>
                                {{ ($isActive ?? true) ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        {{-- ============ CHANGE + REMOVE — magkatabi na ngayon ============ --}}
                        <div class="bwl-acct-photo-actions">
                            <button type="button" class="bwl-acct-change-photo-text" id="bwlSetChangePhotoTextBtn">
                                <i class="feather-upload" id="bwlSetChangePhotoIcon"></i>
                                <span class="spinner-border spinner-border-sm d-none" id="bwlSetChangePhotoSpinner" role="status" aria-hidden="true"></span>
                                <span id="bwlSetChangePhotoLabel">{{ ($profilePicture ?? false) ? 'Change Profile Picture' : 'Upload Profile Picture' }}</span>
                            </button>
                            <button type="button" class="bwl-acct-remove-photo" id="bwlSetRemovePhotoBtn"
                                    data-remove-url="{{ route('borrower.profile.remove-picture') }}"
                                    style="{{ ($profilePicture ?? false) ? '' : 'display:none;' }}">
                                Remove Picture
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bwl-acct-divider"></div>

                {{-- ============ PROFILE INFORMATION (view-only) ============ --}}
                <div class="bwl-acct-field">
                    <label>Full Name</label>
                    <span>{{ $userName }}</span>
                </div>
                <div class="bwl-acct-field">
                    <label>Email</label>
                    <span>{{ $userEmail }}</span>
                </div>
                <div class="bwl-acct-field">
                    <label>Phone Number</label>
                    <span>{{ $userPhone ?? '—' }}</span>
                </div>

                <div class="bwl-acct-divider"></div>

                <div class="bwl-acct-field-grid">
                    <div class="bwl-acct-field">
                        <label>Role</label>
                        <span>Borrower</span>
                    </div>
                    <div class="bwl-acct-field">
                        <label>Account Status</label>
                        <span class="bwl-acct-status-pill {{ ($isActive ?? true) ? 'is-active' : 'is-inactive' }}">
                            <span class="bwl-acct-status-dot"></span>
                            {{ ($isActive ?? true) ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     CHANGE PASSWORD MODAL
============================================================ --}}
<div class="modal fade bwl-acct-modal" id="bwlChangePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
           
            <div class="modal-body">

                <div id="bwlSetPasswordError" class="bwl-acct-alert bwl-acct-alert-danger d-none"></div>
             
                <div class="bwl-acct-form-field">
                    <label for="bwlSetCurrentPassword">Current Password <span class="bwl-required">*</span></label>
                    <div class="bwl-acct-pw-wrap">
                        <input type="password" id="bwlSetCurrentPassword" class="bwl-acct-input" placeholder="Enter current password">
                        <button type="button" class="bwl-acct-pw-toggle" data-target="bwlSetCurrentPassword">
                            <i class="feather-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="bwl-acct-form-field">
                    <label for="bwlSetNewPassword">New Password <span class="bwl-required">*</span></label>
                    <div class="bwl-acct-pw-wrap">
                        <input type="password" id="bwlSetNewPassword" class="bwl-acct-input" placeholder="Enter new password">
                        <button type="button" class="bwl-acct-pw-toggle" data-target="bwlSetNewPassword">
                            <i class="feather-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="bwl-acct-form-field">
                    <div class="bwl-pw-confirm-label-row">
                        <label for="bwlSetConfirmPassword" class="mb-0">Confirm New Password <span class="bwl-required">*</span></label>
                        <button type="button" class="bwl-pw-clear-btn" id="bwlSetClearConfirmBtn">Clear</button>
                    </div>
                    <div class="bwl-acct-pw-wrap">
                        <input type="password" id="bwlSetConfirmPassword" class="bwl-acct-input" placeholder="Re-enter new password">
                        <button type="button" class="bwl-acct-pw-toggle" data-target="bwlSetConfirmPassword">
                            <i class="feather-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="bwl-pw-strength" id="bwlPwStrength">
                    <div class="bwl-pw-strength-bar">
                        <span class="bwl-pw-strength-seg" data-seg="1"></span>
                        <span class="bwl-pw-strength-seg" data-seg="2"></span>
                        <span class="bwl-pw-strength-seg" data-seg="3"></span>
                    </div>
                    <p class="bwl-pw-strength-label" id="bwlPwStrengthLabel">Password strength. Must contain;</p>
                    <ul class="bwl-pw-checklist">
                        <li id="bwlPwReqUpper"><i class="feather-x-circle"></i> At least 1 uppercase</li>
                        <li id="bwlPwReqNumber"><i class="feather-x-circle"></i> At least 1 number</li>
                        <li id="bwlPwReqLength"><i class="feather-x-circle"></i> At least 8 characters</li>
                    </ul>
                </div>

            </div>
            <div class="modal-footer bwl-acct-footer">
                <button type="button" class="bwl-acct-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="bwl-acct-btn-save" id="bwlSetSavePasswordBtn">
                    <span id="bwlSetSavePasswordText">Change Password</span>
                    <span id="bwlSetSavePasswordSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     REMOVE PHOTO — CONFIRMATION MODAL (replaces native confirm())
============================================================ --}}
<div class="modal fade bwl-acct-modal" id="bwlRemovePhotoConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered bwl-acct-modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="bwl-remove-confirm-title">Remove Profile Picture</div>
                <div class="bwl-remove-confirm-desc">
                    This action will permanently delete the current photo from your account. You can upload a new photo later if you wish.
                </div>
            </div>
            <div class="modal-footer bwl-acct-footer">
                <button type="button" class="bwl-acct-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="bwl-acct-btn-save bwl-btn-danger-fill" id="bwlConfirmRemovePhotoBtn">
                    <span id="bwlConfirmRemovePhotoText">Remove</span>
                    <span id="bwlConfirmRemovePhotoSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CROP MODAL --}}
<div class="modal fade bwl-acct-crop-modal" id="bwlSetCropModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bwl-acct-header">
                <h6 class="modal-title" style="font-size:0.95rem;">Crop Photo</h6>
                <button type="button" class="btn-close" id="bwlSetCropCloseX"></button>
            </div>
            <div class="bwl-acct-crop-body">
                <div class="bwl-acct-crop-wrap is-round-crop">
                    <img id="bwlSetCropImage" src="" alt="Crop preview">
                </div>
                <p class="bwl-acct-hint text-center">Drag to reposition. Scroll or pinch to zoom.</p>
            </div>
            <div class="modal-footer bwl-acct-footer">
                <button type="button" class="bwl-acct-btn-cancel" id="bwlSetCropCancelBtn">Cancel</button>
                <button type="button" class="bwl-acct-btn-save" id="bwlSetCropApplyBtn">
                    <span id="bwlSetCropApplyText">Apply</span>
                    <span id="bwlSetCropApplySpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>