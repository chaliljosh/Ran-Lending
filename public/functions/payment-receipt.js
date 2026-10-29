// Payment receipt upload — View Loan Details (borrower).
//
// Markup contract (see loan-details.blade.php):
//   [data-ld-upload]       button that opens the modal (data-mode: upload | replace | again)
//   [data-ld-view]         button that opens the receipt viewer
//                            data-src          URL of the picture (required)
//                            data-title        modal title (optional, default "Receipt")
//                            data-file-prefix  download filename prefix (optional)
//                            data-summary      line shown above the picture (optional)
//   tr[data-installment]   the schedule row; also carries data-amount and data-due
//   #ldReceiptModal        the Bootstrap modal with the form inside
//   #ldReceiptPaidDateDisplay / #ldReceiptPaidDate   date picker (display + hidden "paid_date")
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
    // =========================================================

    const viewModalEl = document.getElementById("ldViewModal");

    if (viewModalEl) {
        const viewTitle = document.getElementById("ldViewTitle");
        const viewSummary = document.getElementById("ldViewSummary");
        const viewImg = document.getElementById("ldViewImg");
        const viewLoading = document.getElementById("ldViewLoading");
        const viewError = document.getElementById("ldViewError");
        const downloadBtn = document.getElementById("ldViewDownload");

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

            viewSummary.textContent = summary ? "✓ " + summary : "";
            viewSummary.hidden = !summary;

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
    const paidDateDisplay = document.getElementById("ldReceiptPaidDateDisplay");
    const paidDateInput = document.getElementById("ldReceiptPaidDate");
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
    // DATE PICKER (same picker as the admin "Record Payment" modal)
    // =========================================================

    function todayLocal() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 10);
    }

    function createDatePicker(displayEl, hiddenEl, hostEl) {
        const MONTHS = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];
        const WEEKDAYS = ["S", "M", "T", "W", "T", "F", "S"];

        const pad = function (n) { return String(n).padStart(2, "0"); };
        const toYmd = function (y, m, d) { return y + "-" + pad(m + 1) + "-" + pad(d); };
        const parseYmd = function (str) {
            const parts = str.split("-").map(Number);
            return { y: parts[0], m: parts[1] - 1, d: parts[2] };
        };

        let minYmd = "";
        let maxYmd = "";
        let view = "days";
        let viewYear = 0;
        let viewMonth = 0;
        let isOpen = false;

        const pop = document.createElement("div");
        pop.className = "bwl-ld-dp";
        pop.style.display = "none";
        pop.setAttribute("role", "dialog");
        hostEl.appendChild(pop);

        function setValue(ymd) {
            hiddenEl.value = ymd || "";
            if (ymd) {
                const p = parseYmd(ymd);
                displayEl.value = pad(p.m + 1) + "/" + pad(p.d) + "/" + p.y;
            } else {
                displayEl.value = "";
            }
        }

        function setRange(min, max) {
            minYmd = min || "";
            maxYmd = max || "";
        }

        function isDisabled(ymd) {
            return (minYmd && ymd < minYmd) || (maxYmd && ymd > maxYmd);
        }

        function monthIndex(ymd) {
            const p = parseYmd(ymd);
            return p.y * 12 + p.m;
        }

        function headerHtml(showNav) {
            const prevOff = minYmd && (viewYear * 12 + viewMonth) <= monthIndex(minYmd);
            const nextOff = maxYmd && (viewYear * 12 + viewMonth) >= monthIndex(maxYmd);
            const caret = view === "years" ? "feather-chevron-up" : "feather-chevron-down";
            let html = '<div class="bwl-ld-dp-head">' +
                '<button type="button" class="bwl-ld-dp-title" data-act="toggle-view">' +
                MONTHS[viewMonth] + " " + viewYear + ' <i class="' + caret + '"></i></button>';
            if (showNav) {
                html += '<div class="bwl-ld-dp-nav">' +
                    '<button type="button" class="bwl-ld-dp-navbtn" data-act="prev" aria-label="Previous month"' + (prevOff ? " disabled" : "") + '><i class="feather-chevron-left"></i></button>' +
                    '<button type="button" class="bwl-ld-dp-navbtn" data-act="next" aria-label="Next month"' + (nextOff ? " disabled" : "") + '><i class="feather-chevron-right"></i></button>' +
                    "</div>";
            }
            return html + "</div>";
        }

        function render() {
            if (view === "years") {
                renderYears();
            } else {
                renderDays();
            }
            if (isOpen) position();
        }

        function renderDays() {
            const firstWeekday = new Date(viewYear, viewMonth, 1).getDay();
            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            const selected = hiddenEl.value;
            const today = todayLocal();

            let html = headerHtml(true);
            html += '<div class="bwl-ld-dp-weekdays">' + WEEKDAYS.map(function (w) { return "<span>" + w + "</span>"; }).join("") + "</div>";
            html += '<div class="bwl-ld-dp-grid">';
            for (let i = 0; i < firstWeekday; i++) html += "<span></span>";
            for (let d = 1; d <= daysInMonth; d++) {
                const ymd = toYmd(viewYear, viewMonth, d);
                let cls = "bwl-ld-dp-day";
                if (ymd === today) cls += " is-today";
                if (ymd === selected) cls += " is-selected";
                html += '<button type="button" class="' + cls + '" data-ymd="' + ymd + '"' +
                    (isDisabled(ymd) ? " disabled" : "") + ">" + d + "</button>";
            }
            html += "</div>";
            pop.innerHTML = html;
        }

        function renderYears() {
            const thisYear = new Date().getFullYear();
            const minY = minYmd ? parseYmd(minYmd).y : thisYear - 10;
            const maxY = maxYmd ? parseYmd(maxYmd).y : thisYear + 20;

            let html = headerHtml(false) + '<div class="bwl-ld-dp-years">';
            for (let y = minY; y <= maxY; y++) {
                html += '<button type="button" class="bwl-ld-dp-year' + (y === viewYear ? " is-selected" : "") +
                    '" data-year="' + y + '">' + y + "</button>";
            }
            html += "</div>";
            pop.innerHTML = html;

            const grid = pop.querySelector(".bwl-ld-dp-years");
            const current = pop.querySelector(".bwl-ld-dp-year.is-selected");
            if (grid && current) {
                grid.scrollTop = current.offsetTop - (grid.clientHeight / 2) + (current.clientHeight / 2);
            }
        }

        function position() {
            const r = displayEl.getBoundingClientRect();
            const w = pop.offsetWidth;
            const h = pop.offsetHeight;

            let top = r.bottom + 6;
            if (top + h > window.innerHeight - 8) {
                top = Math.max(8, r.top - h - 6);
            }
            const left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));

            pop.style.top = top + "px";
            pop.style.left = left + "px";
        }

        function open() {
            if (isOpen) return;
            const base = hiddenEl.value || todayLocal();
            let p = parseYmd(base);
            if (isDisabled(base)) {
                p = parseYmd(maxYmd && base > maxYmd ? maxYmd : minYmd);
            }
            viewYear = p.y;
            viewMonth = p.m;
            view = "days";
            isOpen = true;
            pop.style.display = "block";
            render();
        }

        function close() {
            if (!isOpen) return;
            isOpen = false;
            pop.style.display = "none";
        }

        function shiftMonth(delta) {
            const idx = viewYear * 12 + viewMonth + delta;
            viewYear = Math.floor(idx / 12);
            viewMonth = idx % 12;
            render();
        }

        function pickYear(year) {
            viewYear = year;
            if (minYmd && (viewYear * 12 + viewMonth) < monthIndex(minYmd)) viewMonth = parseYmd(minYmd).m;
            if (maxYmd && (viewYear * 12 + viewMonth) > monthIndex(maxYmd)) viewMonth = parseYmd(maxYmd).m;
            view = "days";
            render();
        }

        pop.addEventListener("click", function (e) {
            const actBtn = e.target.closest("[data-act]");
            if (actBtn) {
                const act = actBtn.dataset.act;
                if (act === "toggle-view") { view = view === "days" ? "years" : "days"; render(); }
                if (act === "prev") shiftMonth(-1);
                if (act === "next") shiftMonth(1);
                return;
            }
            const dayBtn = e.target.closest(".bwl-ld-dp-day");
            if (dayBtn && !dayBtn.disabled) {
                setValue(dayBtn.dataset.ymd);
                close();
                displayEl.focus();
                return;
            }
            const yearBtn = e.target.closest(".bwl-ld-dp-year");
            if (yearBtn) pickYear(Number(yearBtn.dataset.year));
        });

        displayEl.addEventListener("click", function () { isOpen ? close() : open(); });
        displayEl.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " " || e.key === "ArrowDown") {
                e.preventDefault();
                open();
            }
        });

        document.addEventListener("mousedown", function (e) {
            if (!isOpen) return;
            if (pop.contains(e.target) || displayEl.parentElement.contains(e.target)) return;
            close();
        });

        // Escape closes the picker first, not the whole modal.
        hostEl.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && isOpen) {
                e.stopImmediatePropagation();
                e.preventDefault();
                close();
            }
        }, true);

        hostEl.addEventListener("hide.bs.modal", close);
        hostEl.addEventListener("scroll", close);
        window.addEventListener("resize", close);

        return { setValue: setValue, setRange: setRange, close: close };
    }

    const datePicker = createDatePicker(paidDateDisplay, paidDateInput, modalEl);

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

        // Default: today. Future dates are blocked.
        const today = todayLocal();
        datePicker.close();
        datePicker.setRange("", today);
        datePicker.setValue(today);
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

    // Highlight the box while an image is dragged over it.
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

        const paidDate = paidDateInput.value;
        if (!paidDate) {
            showError("Please select the date you made the payment.");
            return;
        }
        if (paidDate > todayLocal()) {
            showError("The payment date cannot be in the future.");
            return;
        }

        setSending(true);

        try {
            const response = await fetch(modalEl.dataset.submitUrl, {
                method: "POST",
                body: new FormData(form), // includes @csrf and paid_date
                credentials: "same-origin",
                headers: {
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