{{-- Success modal pagkatapos ma-send ang release confirmation.
     Ang JS (ongoing-loan-detail.js) ang naglalagay ng message sa #olsReleasedMessage. --}}
<div class="modal fade nxl-account-modal" id="olsReleasedModal" tabindex="-1"
    aria-labelledby="olsReleasedTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content olrd-modal">
            <div class="olrd-body">
                <div class="olrd-icon"><i class="feather-check"></i></div>
                <h5 class="olrd-title" id="olsReleasedTitle">Loan Released</h5>
                <p class="olrd-message" id="olsReleasedMessage"></p>
            </div>
            <div class="olrd-footer">
                <button type="button" class="olrd-btn-done" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>