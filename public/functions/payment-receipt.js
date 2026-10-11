// Payment receipt upload — View Loan Details (borrower).
//
// Markup contract (see loan-details.blade.php):
//   [data-ld-upload]       button that opens the modal (data-mode: upload | replace | again)
//   [data-ld-view]         button that opens the receipt viewer
//                            data-src          URL of the picture (required)
//                            data-title        modal title (optional, default "Receipt")
//                            data-file-prefix  download filename prefix (optional)
//                            data-summary      line shown above the picture (optional),
//                                              e.g. "Received by Admin · ₱7,005.27 · October 6, 2026"
//   tr[data-installment]   the schedule row; also carries data-amount and data-due
//   #ldReceiptModal        the Bootstrap modal with the form inside
//
// If the page has no modal (no loan, or the loan is not approved/active),
// only the success toast logic runs and everything else is skipped.

(function () {
    "use strict";

    const MAX_SIZE = 5 * 1024 * 1024; // 5MB, same as the Valid ID upload
    const ALLOWED_TYPES = ["image/jpeg", "image/png"];
    const FLASH_KEY = "ldReceiptToast";

    // =========================================================
    // TOAST
    // =========================================================

    function showToast(message, type) {
        const toast = document.createElement("div");
        toast.className = "bwl-ld-toast is-" + (type || "success");
        toast.setAttribute("role", type === "error" ? "alert" : "status");
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add("is-visible");
        });

        setTimeout(function () {
            toast.classList.remove("is-visible");
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 4000);
    }

    // The page reloads after a successful upload, so the toast message
    // is saved first and shown here once the page is back.
    try {
        const flash = sessionStorage.getItem(FLASH_KEY);
        if (flash) {
            sessionStorage.removeItem(FLASH_KEY);
            showToast(flash, "success");
        }
    } catch (err) {
        // sessionStorage unavailable — not critical.
    }

    // =========================================================
    // VIEW RECEIPT MODAL
    // Runs on its own, so it works even when uploading is closed.
    // Used for two kinds of receipts:
    //   1. the receipt the borrower uploaded (unpaid installment)
    //   2. the official receipt the admin recorded (paid installment)
    // =========================================================

    const viewModalEl = document.getElementById("ldViewModal");

    if (viewModalEl) {
        const viewTitle = document.getElementById("ldViewTitle");
        const viewSummary = document.getElementById("ldViewSummary");
        const viewImg = document.getElementById("ldViewImg");
        const viewLoading = document.getElementById("ldViewLoading");
        const viewError = document.getElementById("ldViewError");
        const downloadBtn = document.getElementById("ldViewDownload");

        // Ang receipt na kasalukuyang nakabukas sa modal
        let currentUrl = "";
        let currentMonth = "";
        let currentPrefix = "receipt-month-";

        viewImg.addEventListener("load", function () {
            viewLoading.hidden = true;
            viewError.hidden = true;
            viewImg.hidden = false;
        });

        viewImg.addEventListener("error", function () {
            // Ignore the event that fires when we clear the picture on close.
            if (!viewImg.getAttribute("src")) {
                return;
            }
            viewLoading.hidden = true;
            viewImg.hidden = true;
            viewError.hidden = false;
        });

        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-ld-view]");
            if (!button) {
                return;
            }

            if (!window.bootstrap || !window.bootstrap.Modal) {
                console.error("Bootstrap modal is not available on this page.");
                return;
            }

            const row = button.closest("tr");
            const url = button.dataset.src;
            const baseTitle = button.dataset.title || "Receipt";
            const summary = button.dataset.summary || "";

            viewTitle.textContent = row
                ? baseTitle + " — Month " + row.dataset.installment
                : baseTitle;
            currentUrl = url;
            currentMonth = row ? row.dataset.installment : "";
            currentPrefix = button.dataset.filePrefix || "receipt-month-";

            // "Received by ..." line (only for the admin's receipt)
            viewSummary.textContent = summary ? "✓ " + summary : "";
            viewSummary.hidden = !summary;

            // Reset, then load the picture
            viewImg.hidden = true;
            viewError.hidden = true;
            viewLoading.hidden = false;
            viewImg.src = url;

            window.bootstrap.Modal.getOrCreateInstance(viewModalEl).show();
        });

        // I-download ang receipt bilang file (hal. receipt-month-5.jpg)
        downloadBtn.addEventListener("click", async function () {
            if (!currentUrl || downloadBtn.disabled) {
                return;
            }

            downloadBtn.disabled = true;

            try {
                const response = await fetch(currentUrl, { credentials: "same-origin" });
                if (!response.ok) {
                    throw new Error("Download failed");
                }

                const blob = await response.blob();
                const ext = blob.type === "image/png" ? "png" : "jpg";
                const href = URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = href;
                link.download = currentPrefix + currentMonth + "." + ext;
                document.body.appendChild(link);
                link.click();
                link.remove();

                setTimeout(function () {
                    URL.revokeObjectURL(href);
                }, 1000);
            } catch (err) {
                // Hindi makuha bilang file: buksan na lang ang larawan sa bagong tab.
                window.open(currentUrl, "_blank", "noopener");
            }

            downloadBtn.disabled = false;
        });

        viewModalEl.addEventListener("hidden.bs.modal", function () {
            viewImg.removeAttribute("src");
            viewImg.hidden = true;
            viewSummary.hidden = true;
            viewSummary.textContent = "";
            currentUrl = "";
        });
    }

    // =========================================================
    // ELEMENTS
    // =========================================================

    const modalEl = document.getElementById("ldReceiptModal");
    if (!modalEl) {
        return;
    }

    const form = document.getElementById("ldReceiptForm");
    const titleEl = document.getElementById("ldReceiptTitle");
    const amountEl = document.getElementById("ldReceiptAmount");
    const dueEl = document.getElementById("ldReceiptDue");
    const installmentInput = document.getElementById("ldReceiptInstallment");
    const fileInput = document.getElementById("ldReceiptFile");
    const dropEl = document.getElementById("ldReceiptDrop");
    const dropEmpty = document.getElementById("ldReceiptDropEmpty");
    const dropFilled = document.getElementById("ldReceiptDropFilled");
    const fileNameEl = document.getElementById("ldReceiptFileName");
    const previewImg = document.getElementById("ldReceiptPreviewImg");
    const errorEl = document.getElementById("ldReceiptError");
    const submitBtn = document.getElementById("ldReceiptSubmit");
    const spinner = document.getElementById("ldReceiptSpinner");
    const submitText = document.getElementById("ldReceiptSubmitText");

    const TITLES = {
        upload: "Upload Receipt",
        replace: "Replace Receipt",
        again: "Upload Receipt",
    };

    let previewUrl = null;
    let isSending = false;

    // =========================================================
    // HELPERS
    // =========================================================

    function showError(message) {
        errorEl.textContent = message;
        errorEl.hidden = false;
    }

    function clearError() {
        errorEl.textContent = "";
        errorEl.hidden = true;
    }

    function clearPreview() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
        previewImg.removeAttribute("src");
        fileNameEl.textContent = "";
        dropFilled.hidden = true;
        dropEmpty.hidden = false;
        dropEl.classList.remove("has-file", "is-dragover");
    }

    function setSending(sending) {
        isSending = sending;
        spinner.hidden = !sending;
        submitText.hidden = sending; // spinner lang ang makikita habang nagpapadala
        submitBtn.classList.toggle("is-sending", sending);
        // Submit stays disabled while sending (no double submit) and
        // while there is no valid file.
        submitBtn.disabled = sending || !fileInput.files.length;
    }

    function resetForm() {
        form.reset();
        clearPreview();
        clearError();
        setSending(false);
    }

    // =========================================================
    // OPEN THE MODAL
    // =========================================================

    document.addEventListener("click", function (event) {
        const button = event.target.closest("[data-ld-upload]");
        if (!button) {
            return;
        }

        const row = button.closest("tr");
        if (!row) {
            return;
        }

        if (!window.bootstrap || !window.bootstrap.Modal) {
            console.error("Bootstrap modal is not available on this page.");
            return;
        }

        resetForm();

        const mode = button.dataset.mode || "upload";
        const installment = row.dataset.installment;

        titleEl.textContent = (TITLES[mode] || TITLES.upload) + " — Month " + installment;
        amountEl.textContent = row.dataset.amount || "—";
        dueEl.textContent = row.dataset.due || "—";
        installmentInput.value = installment;

        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    modalEl.addEventListener("hidden.bs.modal", resetForm);

    // =========================================================
    // PICK A FILE (validate + preview)
    // =========================================================

    fileInput.addEventListener("change", function () {
        clearError();
        clearPreview();

        const file = fileInput.files[0];

        if (!file) {
            setSending(false);
            return;
        }

        if (ALLOWED_TYPES.indexOf(file.type) === -1) {
            fileInput.value = "";
            showError("Receipt must be a JPG or PNG picture.");
            setSending(false);
            return;
        }

        if (file.size > MAX_SIZE) {
            fileInput.value = "";
            showError("Receipt must be less than 5MB.");
            setSending(false);
            return;
        }

        previewUrl = URL.createObjectURL(file);
        previewImg.src = previewUrl;
        fileNameEl.textContent = file.name;
        dropEmpty.hidden = true;
        dropFilled.hidden = false;
        dropEl.classList.add("has-file");
        setSending(false);
    });

    // Highlight the box while an image is dragged over it. The drop itself is
    // handled by the browser (it fills the input and fires "change" above).
    ["dragenter", "dragover"].forEach(function (name) {
        dropEl.addEventListener(name, function () {
            dropEl.classList.add("is-dragover");
        });
    });
    ["dragleave", "drop"].forEach(function (name) {
        dropEl.addEventListener(name, function () {
            dropEl.classList.remove("is-dragover");
        });
    });

    // =========================================================
    // SUBMIT
    // =========================================================

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (isSending || !fileInput.files.length) {
            return;
        }

        clearError();
        setSending(true);

        try {
            const response = await fetch(modalEl.dataset.submitUrl, {
                method: "POST",
                body: new FormData(form), // includes the CSRF token from @csrf
                credentials: "same-origin",
                headers: {
                    // Without Accept: application/json, Laravel answers
                    // validation errors with an HTML redirect.
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const data = await response.json().catch(function () {
                return null;
            });

            if (response.ok && data && data.success) {
                try {
                    sessionStorage.setItem(FLASH_KEY, data.message || "Receipt submitted.");
                } catch (err) {
                    // Not critical — the page still reloads with the new state.
                }
                window.location.reload();
                return;
            }

            if (response.status === 419) {
                showError("Your session expired. Please refresh the page and try again.");
            } else {
                showError((data && data.message) || "Something went wrong. Please try again.");
            }
        } catch (err) {
            showError("Network error. Please check your connection and try again.");
        }

        setSending(false);
    });
})();