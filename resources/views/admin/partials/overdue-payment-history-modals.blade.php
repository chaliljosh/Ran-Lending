{{-- I-save bilang: resources/views/admin/partials/overdue-payment-history-modals.blade.php --}}
<div class="modal fade opd-pd-modal" id="opdPayDetailsModal" tabindex="-1"
    aria-labelledby="opdPdTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="opdPdTitle">Payment Details</h5>
                    <div class="opd-pd-sub" id="opdPdMonth"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="opd-pd-grid">
                    <div><dt>Due Date</dt><dd id="opdPdDue"></dd></div>
                    <div><dt>Date Paid</dt><dd id="opdPdPaidOn"></dd></div>
                    <div><dt>Installment Amount</dt><dd id="opdPdAmount"></dd></div>
                    <div><dt>Status</dt><dd id="opdPdStatus"></dd></div>
                </dl>

                <div class="opd-pd-section-title">Payments</div>
                <div class="opd-pd-list" id="opdPdList" hidden></div>
                <div class="opd-pd-empty" id="opdPdEmpty" hidden>No payment records yet.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade opd-pd-modal" id="opdPayViewerModal" tabindex="-1"
    aria-labelledby="opdPdViewerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="opdPdViewerTitle">Payment Receipt</h5>
                    <div class="opd-pd-sub" id="opdPdViewerSub"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="opd-pd-grid">
                    <div><dt>Due Date</dt><dd id="opdPdVDue"></dd></div>
                    <div><dt>Date Paid</dt><dd id="opdPdVPaidOn"></dd></div>
                    <div><dt>Installment Amount</dt><dd id="opdPdVInstallment"></dd></div>
                    <div><dt>Amount Paid</dt><dd id="opdPdVPaid"></dd></div>
                    <div><dt>Penalty Paid</dt><dd id="opdPdVPenalty"></dd></div>
                    <div><dt>Payment Type</dt><dd id="opdPdVKind"></dd></div>
                    <div><dt>Status</dt><dd id="opdPdVStatus"></dd></div>
                </dl>

                <div class="opd-pd-viewer-body">
                    <span class="spinner-border" id="opdPdViewerLoading" role="status" aria-label="Loading receipt"></span>
                    <img id="opdPdViewerImg" alt="Payment receipt" hidden>
                    <span class="opd-pd-state" id="opdPdViewerError" hidden>We couldn't load this receipt. Please try again.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="opd-btn opd-btn-waive" data-bs-dismiss="modal">Close</button>
                <a href="#" class="opd-btn opd-btn-mark-paid" id="opdPdViewerDownload">
                    <i class="feather-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>