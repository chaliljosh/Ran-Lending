// Apply for a Loan — 3-step wizard (Loan Details → Valid ID → Source of Income),
// auto-saved draft, cancel confirmation, live summary calculator,
// AJAX submission, and the post-submit success toast check.
//
// Config comes from data-* attributes on this <script> tag itself
// (set server-side in apply-loan.blade.php), so no separate inline
// <script> block is needed in the view:
//   <script src="...apply-loan.js" data-csrf="..." data-submit-url="..." data-max-amount="..."></script>

(function () {
    "use strict";

    // =========================================================
    // CONFIG — read from this script tag's own attributes
    // =========================================================

    const scriptTag = document.currentScript;
    const CSRF_TOKEN = scriptTag ? scriptTag.dataset.csrf : "";
    const SUBMIT_URL = scriptTag ? scriptTag.dataset.submitUrl : "";
    const USER_ID = scriptTag ? scriptTag.dataset.userId : "";
    // Server computes this based on loan history (₱15,000 for
    // first-time borrowers, +₱5,000 for every fully repaid loan).
    const MAX_AMOUNT = scriptTag
        ? parseInt(scriptTag.dataset.maxAmount, 10) || 15000
        : 15000;

    // =========================================================
    // ERROR TOAST — pamalit sa native alert() para sa submit
    // errors. Self-contained (inline styles) para walang kailangang
    // idagdag na CSS file.
    // =========================================================

    function showErrorToast(message) {
        let toast = document.getElementById("loanErrorToast");

        if (!toast) {
            toast = document.createElement("div");
            toast.id = "loanErrorToast";
            toast.style.cssText = `
                display: none;
                align-items: center;
                gap: 10px;
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 100000;
                min-width: 320px;
                max-width: 90vw;
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-left: 4px solid #dc2626;
                color: #b91c1c;
                border-radius: 10px;
                padding: 12px 16px;
                font-size: 0.9rem;
                font-family: inherit;
                box-shadow: 0 12px 32px rgba(15,23,42,0.18);
            `;
            toast.innerHTML = `
                <i class="feather-alert-circle" style="flex-shrink:0;font-size:16px;"></i>
                <span class="loan-toast-message" style="flex:1;line-height:1.4;"></span>
                <button type="button" class="loan-toast-close" aria-label="Close" style="background:none;border:none;color:#b91c1c;opacity:0.6;cursor:pointer;padding:0 0 0 6px;font-size:16px;line-height:1;flex-shrink:0;">&times;</button>
            `;
            document.body.appendChild(toast);

            toast
                .querySelector(".loan-toast-close")
                .addEventListener("click", function () {
                    toast.style.display = "none";
                });
        }

        toast.querySelector(".loan-toast-message").textContent = message;
        toast.style.display = "flex";

        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(function () {
            toast.style.display = "none";
        }, 6000);
    }

    // =========================================================
    // POST-SUBMIT SUCCESS TOAST
    // =========================================================
    //
    // Runs unconditionally (not gated behind the form-exists check
    // below) because after a successful submission the page reloads
    // into the "pending application" view — the form (and
    // loanAmountInput) won't exist on that reloaded page, but the
    // toast still needs to show there.

    document.addEventListener("DOMContentLoaded", function () {
        if (sessionStorage.getItem("loanApplicationSubmitted") === "1") {
            sessionStorage.removeItem("loanApplicationSubmitted");

            const overlay = document.getElementById("successOverlay");
            if (overlay) {
                // Small delay so the toast animates in after paint,
                // instead of appearing already-visible on load.
                requestAnimationFrame(function () {
                    overlay.classList.add("show");
                });

                setTimeout(function () {
                    overlay.classList.remove("show");
                }, 6000);
            }
        }
    });

    // =========================================================
    // DOM ELEMENTS
    // =========================================================

    const loanAmountInput = document.getElementById("loanAmount");

    // Term is a <select> (1–6 months for first-timers, 1–12 after that;
    // the options are generated server-side in the blade view).
    const loanTermSelect = document.getElementById("loanTerm");

    const loanPurposeSelect = document.getElementById("loanPurpose");
    const loanPurposeOtherField = document.getElementById(
        "loanPurposeOtherField",
    );
    const loanPurposeOtherInput = document.getElementById("loanPurposeOther");
    const referralSelect = document.getElementById("referralSource");

    // Referral "Others" (where did you find us)
    const referralOtherField = document.getElementById("referralOtherField");
    const referralOtherInput = document.getElementById("referralOther");

    const referralNameField = document.getElementById("referralNameField");
    const referralNameInput = document.getElementById("referralName");

    // Valid ID
    const idTypeSelect = document.getElementById("idType");
    const idTypeOthersWrapper = document.getElementById("idTypeOthersWrapper");
    const idTypeOthersInput = document.getElementById("idTypeOthers");
    const idNumberInput = document.getElementById("idNumber");
    const idNumberLabelText = document.getElementById("idNumberLabelText");
    const idFrontInput = document.getElementById("validIdFront");
    const idBackInput = document.getElementById("validIdBack");
    const selfieInput = document.getElementById("selfieWithId");
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    // Source of income + pay slip / proof of billing (step 3)
    const employmentSelect = document.getElementById("employmentStatus");
    // Employment status "Others" — Please specify
    const employmentOtherWrapper = document.getElementById(
        "employmentOtherWrapper",
    );
    const employmentOtherInput = document.getElementById("employmentOther");
    const companyNameWrapper = document.getElementById("companyNameWrapper");
    const companyNameLabel = document.getElementById("companyNameLabel");
    const companyNameInput = document.getElementById("companyName");
    const positionWrapper = document.getElementById("positionWrapper");
    const positionLabel = document.getElementById("positionLabel");
    const positionInput = document.getElementById("position");
    const primarySourceWrapper = document.getElementById("primarySourceWrapper");
    const primarySourceSelect = document.getElementById("primarySource");
    const otherSourcesWrapper = document.getElementById("otherSourcesWrapper");
    const otherSourcesInput = document.getElementById("otherSources");
    const yearsWrapper = document.getElementById("yearsWrapper");
    const yearsLabel = document.getElementById("yearsLabel");
    const yearsInput = document.getElementById("yearsInCompany"); // dropdown of year ranges
    const paySlipSection = document.getElementById("paySlipSection");
    const paySlipTitle = document.getElementById("paySlipTitle");
    const paySlipLabel = document.getElementById("paySlipLabel");
    const paySlipHint = document.getElementById("paySlipHint");
    const paySlipInput = document.getElementById("paySlip");
    const billingSection = document.getElementById("billingSection");
    const billingInput = document.getElementById("proofOfBilling");

    // Wizard
    const progressEl = document.getElementById("alnProgress");
    const stepEls = document.querySelectorAll(".aln-step");
    const cancelBtn = document.getElementById("wizCancel");
    const backBtn = document.getElementById("wizBack");
    const nextBtn = document.getElementById("wizNext");
    const submitBtn = document.getElementById("submitBtn");
    const TOTAL_STEPS = 3;

    // Draft + cancel confirmation
    const formEl = document.getElementById("loanApplicationForm");
    const leaveBtn = document.getElementById("alnLeaveBtn");

    const agreeCheckbox = document.getElementById("agreeTerms");
    const finalSubmitBtn = document.getElementById("finalSubmitBtn");

    // Summary elements (Total to Pay was removed from the modal)
    const displayPrincipal = document.getElementById("displayPrincipal");
    const displayTerm = document.getElementById("displayTerm");
    const displayMonthlyPayment = document.getElementById(
        "displayMonthlyPayment",
    );

    // =========================================================
    // STOP HERE IF FORM DOES NOT EXIST
    // =========================================================
    //
    // (e.g. the borrower already has a pending/active application,
    // so the blade view rendered the pending-status card instead of
    // the form — the toast check above already ran regardless.)

    if (!loanAmountInput) {
        return;
    }

    // =========================================================
    // CONSTANTS
    // =========================================================

    const MIN_AMOUNT = 1000;
    const INTEREST_RATE = 0.05; // 5% monthly

    // Label at placeholder ng ID number, ayon sa napiling uri ng ID.
    const ID_NUMBER_INFO = {
        national_id: {
            label: "PhilSys Card Number (PCN)",
            placeholder: "e.g. 1234-5678-9012-3456",
        },
        passport: {
            label: "Passport Number",
            placeholder: "e.g. P1234567A",
        },
        drivers_license: {
            label: "License Number",
            placeholder: "e.g. N01-23-456789",
        },
        umid: {
            label: "CRN (Common Reference Number)",
            placeholder: "e.g. 0000-0000000-0",
        },
        sss: {
            label: "SSS Number",
            placeholder: "e.g. 00-0000000-0",
        },
        tin: {
            label: "TIN",
            placeholder: "e.g. 000-000-000-000",
        },
        postal: {
            label: "Postal ID Number (PRN)",
            placeholder: "Enter your PRN",
        },
        voters: {
            label: "Voter's ID Number (VIN)",
            placeholder: "Enter your VIN",
        },
        prc: {
            label: "PRC License Number",
            placeholder: "e.g. 0000000",
        },
        others: {
            label: "ID Number",
            placeholder: "Enter ID number",
        },
    };
    const ID_NUMBER_DEFAULT = {
        label: "ID Number",
        placeholder: "Select an ID type first",
    };

    // Mga ID na numbers (at hyphen) lang ang laman. Ang iba (Driver's License,
    // Passport, Postal, Voter's, Others) ay may letra rin, kaya letters + numbers + hyphen.
    const ID_DIGITS_ONLY = ["national_id", "umid", "sss", "tin", "prc"];

    // What step 3 shows for each employment status.
    //   company / position : label ng field (null = nakatago)
    //   funds   : true = may Primary Source of Funds (Other Sources lalabas lang kapag "Others")
    //   paySlip : true = may upload ng pay slip / proof of income
    //   billing : true = may upload ng proof of billing (water bill, etc.)
    //   other   : true = may "Please specify" na text box
    const WORK_CONFIG = {
        employed: {
            company: "Employer / Company Name",
            companyPh: "Enter employer / company name",
            position: "Job Title",
            positionPh: "Enter job title",
            tenure: "Years Employed",
            funds: false,
            paySlip: true,
            billing: false,
            paySlipTitle: "Pay Slip",
            paySlipLabel: "Upload Pay Slip",
            paySlipHint: "Latest pay slip. JPG or PNG, max 5MB.",
        },
        self_employed: {
            company: "Business Name",
            companyPh: "Enter business name",
            position: "Job Title",
            positionPh: "e.g. Sari-sari store owner",
            tenure: "Years in Business",
            funds: false,
            paySlip: true,
            billing: false,
            paySlipTitle: "Proof of Income",
            paySlipLabel: "Upload Pay Slip / Proof of Income",
            paySlipHint:
                "Recent pay slip or proof of income (e.g. sales record). JPG or PNG, max 5MB.",
        },
        unemployed: {
            company: null,
            position: null,
            funds: true,
            paySlip: false,
            billing: true,
        },
        student: {
            company: "School Name",
            companyPh: "Enter school name",
            position: null,
            funds: true,
            paySlip: false,
            billing: false,
        },
        retired: {
            company: null,
            position: null,
            funds: true,
            paySlip: false,
            billing: true,
        },
        others: {
            company: null,
            position: null,
            funds: false,
            paySlip: false,
            billing: true,
            other: true,
        },
    };

    // =========================================================
    // NUMBER FORMATTER
    // =========================================================

    const pesoFormatter = new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    function formatPeso(value) {
        return "₱" + pesoFormatter.format(value);
    }

    // =========================================================
    // SELECTED TERM
    // =========================================================

    function getSelectedTerm() {
        return loanTermSelect ? loanTermSelect.value : "";
    }

    // =========================================================
    // LOAN AMOUNT — may kuwit (,) habang nagta-type
    // =========================================================
    //
    // Text input na ito (hindi type="number"), kaya kaya nitong
    // magpakita ng "10,000". Ang totoong halaga ay laging mula sa
    // mga digit lang (rawDigits), at iyon din ang ipinapadala sa server.

    function rawDigits(str) {
        return String(str).replace(/\D/g, "");
    }

    function getLoanAmountValue() {
        const digits = rawDigits(loanAmountInput.value);
        return digits === "" ? 0 : parseInt(digits, 10);
    }

    // Nililinis ang value (digits lang, may kuwit, hindi lalampas sa max).
    function clampLoanAmount() {
        let digits = rawDigits(loanAmountInput.value).replace(/^0+/, "");

        if (digits === "") {
            if (loanAmountInput.value !== "") {
                loanAmountInput.value = "";
            }
            return 0;
        }

        let amount = parseInt(digits, 10);

        if (amount > MAX_AMOUNT) {
            amount = MAX_AMOUNT;
        }

        const formatted = amount.toLocaleString("en-US");
        if (loanAmountInput.value !== formatted) {
            loanAmountInput.value = formatted;
        }

        return amount;
    }

    // Nilalagyan ng kuwit nang hindi tumatalon ang cursor sa dulo.
    function formatLoanAmountInput() {
        const el = loanAmountInput;
        const caret = el.selectionStart;
        const digitsBeforeCaret =
            caret === null ? null : rawDigits(el.value.slice(0, caret)).length;

        clampLoanAmount();

        if (digitsBeforeCaret === null) {
            return;
        }

        const value = el.value;
        let pos = 0;
        let seen = 0;
        while (pos < value.length && seen < digitsBeforeCaret) {
            if (/\d/.test(value[pos])) {
                seen++;
            }
            pos++;
        }

        try {
            el.setSelectionRange(pos, pos);
        } catch (err) {
            // Hindi suportado ng ibang browser — hindi mahalaga.
        }
    }

    // =========================================================
    // VALID ID HELPERS
    // =========================================================

    // May file na pinili, O may naka-save na sa account.
    function hasFile(input) {
        return (
            !!input &&
            ((input.files && input.files.length > 0) ||
                input.dataset.saved === "1")
        );
    }

    function toggleIdTypeOthers() {
        const isOthers = idTypeSelect && idTypeSelect.value === "others";

        if (idTypeOthersWrapper) {
            idTypeOthersWrapper.style.display = isOthers ? "" : "none";
        }
        if (!isOthers && idTypeOthersInput) {
            idTypeOthersInput.value = "";
        }
    }

    // Binabago ang label at placeholder ng ID number ayon sa uri ng ID.
    function updateIdNumberField() {
        const info =
            (idTypeSelect && ID_NUMBER_INFO[idTypeSelect.value]) ||
            ID_NUMBER_DEFAULT;

        if (idNumberLabelText) {
            idNumberLabelText.textContent = info.label;
        }
        if (idNumberInput) {
            idNumberInput.placeholder = info.placeholder;
        }
    }

    // Tinatanggal ang mga hindi pwedeng character habang nagta-type.
    // Numbers + hyphen lang para sa PhilSys, UMID, SSS, TIN, PRC;
    // letters + numbers + hyphen para sa iba.
    function sanitizeIdNumber() {
        if (!idNumberInput) return;

        const digitsOnly =
            !!idTypeSelect && ID_DIGITS_ONLY.indexOf(idTypeSelect.value) !== -1;
        const pattern = digitsOnly ? /[^0-9-]/g : /[^A-Za-z0-9-]/g;

        const before = idNumberInput.value;
        const cleaned = before.replace(pattern, "");

        if (cleaned !== before) {
            const caret = idNumberInput.selectionStart;
            const removed = before.length - cleaned.length;
            idNumberInput.value = cleaned;

            try {
                const pos = Math.max(0, (caret || 0) - removed);
                idNumberInput.setSelectionRange(pos, pos);
            } catch (err) {
                // hindi mahalaga
            }
        }
    }

    // ---------------------------------------------------------
    // LIGHTBOX — i-click ang preview para makita nang malaki
    // ---------------------------------------------------------
    let lightboxEl = null;

    function closeLightbox() {
        if (lightboxEl) {
            lightboxEl.classList.remove("show");
        }
        document.body.style.overflow = "";
    }

    function openLightbox(src, caption) {
        if (!lightboxEl) {
            lightboxEl = document.createElement("div");
            lightboxEl.className = "aln-lightbox";
            lightboxEl.innerHTML =
                '<button type="button" class="aln-lightbox__close" aria-label="Close">&times;</button>' +
                '<figure class="aln-lightbox__figure">' +
                '<img class="aln-lightbox__img" alt="Image preview">' +
                '<figcaption class="aln-lightbox__caption"></figcaption>' +
                "</figure>";
            document.body.appendChild(lightboxEl);

            // Click sa labas ng larawan o sa X = isara
            lightboxEl.addEventListener("click", function (e) {
                if (!e.target.classList.contains("aln-lightbox__img")) {
                    closeLightbox();
                }
            });

            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape") closeLightbox();
            });
        }

        lightboxEl.querySelector(".aln-lightbox__img").src = src;
        lightboxEl.querySelector(".aln-lightbox__caption").textContent =
            caption || "";
        lightboxEl.classList.add("show");
        document.body.style.overflow = "hidden";
    }

    function clearPreview(input, preview) {
        // Bitawan ang object URL ngayon lang, hindi na agad-agad,
        // para gumana pa rin ang pag-click para palakihin.
        if (input._objectUrl) {
            URL.revokeObjectURL(input._objectUrl);
            input._objectUrl = null;
        }
        preview.innerHTML = "";
    }

    // Used for the ID front/back, the selfie with valid ID, the pay slip,
    // and the proof of billing.
    //
    // Kung may <label data-dropzone-for="<id ng input>"> sa page,
    // gagana rin ang drag & drop, at itatago ang dropzone habang
    // may napiling file (lalabas ulit kapag na-Remove).
    function setupFilePreview(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!input || !preview) return;

        const zone = document.querySelector(
            '[data-dropzone-for="' + input.id + '"]',
        );

        // Itago ang dropzone kapag may file na, ipakita kapag wala.
        function syncZone() {
            if (zone) {
                zone.classList.toggle(
                    "aln-hide",
                    !!(input.files && input.files.length > 0),
                );
            }
        }

        if (zone) {
            zone.addEventListener("dragover", function (e) {
                e.preventDefault();
                zone.classList.add("is-dragover");
            });

            zone.addEventListener("dragleave", function () {
                zone.classList.remove("is-dragover");
            });

            zone.addEventListener("drop", function (e) {
                e.preventDefault();
                zone.classList.remove("is-dragover");

                const dropped = e.dataTransfer && e.dataTransfer.files[0];
                if (!dropped) return;

                // Isang file lang ang kukunin
                const dt = new DataTransfer();
                dt.items.add(dropped);
                input.files = dt.files;

                // Para tumakbo ang parehong validation at preview sa baba
                input.dispatchEvent(new Event("change", { bubbles: true }));
            });
        }

        input.addEventListener("change", function () {
            clearPreview(input, preview);
            const file = input.files[0];

            if (!file) {
                syncZone();
                validateForm();
                return;
            }

            if (file.size > MAX_FILE_SIZE) {
                showErrorToast("File size must be less than 5MB.");
                input.value = "";
                syncZone();
                validateForm();
                return;
            }

            const ALLOWED_TYPES = ["image/jpeg", "image/png"];
            const okExt = /\.(jpe?g|png)$/i.test(file.name);
            if (!ALLOWED_TYPES.includes(file.type) || !okExt) {
                showErrorToast("Only JPG or PNG pictures are allowed.");
                input.value = "";
                syncZone();
                validateForm();
                return;
            }

            input._objectUrl = URL.createObjectURL(file);

            const wrap = document.createElement("div");
            wrap.className = "aln-file-preview__item";

            const imgBtn = document.createElement("button");
            imgBtn.type = "button";
            imgBtn.className = "aln-file-preview__thumb";
            imgBtn.title = "Click to enlarge";
            imgBtn.setAttribute("aria-label", "View uploaded image");

            const img = document.createElement("img");
            img.alt = "Image preview";
            img.src = input._objectUrl;
            imgBtn.appendChild(img);

            const zoom = document.createElement("span");
            zoom.className = "aln-file-preview__zoom";
            zoom.textContent = "Click to enlarge";
            imgBtn.appendChild(zoom);

            imgBtn.addEventListener("click", function () {
                openLightbox(input._objectUrl, "");
            });

            const remove = document.createElement("button");
            remove.type = "button";
            remove.className = "aln-file-preview__remove";
            remove.textContent = "Remove";
            remove.addEventListener("click", function () {
                input.value = "";
                clearPreview(input, preview);
                syncZone();
                validateForm();
            });

            wrap.append(imgBtn, remove);
            preview.appendChild(wrap);

            syncZone();
            validateForm();
        });
    }

    // =========================================================
    // CUSTOM DROPDOWNS — replaces the native <select> option list
    // =========================================================
    //
    // The native <select> stays in the DOM (hidden) and remains the
    // source of truth, so all the code that reads .value or listens
    // for "change" keeps working.

    const alnDropdowns = [];

    // Mga dropdown na itatago ang "Select ..." na item sa listahan.
    // Makikita pa rin ito sa trigger bilang placeholder bago pumili.
    const HIDE_PLACEHOLDER_IN = ["idType"];

    function buildDropdown(select) {
        const options = Array.from(select.options);

        const wrap = document.createElement("div");
        wrap.className = "aln-dd";

        const trigger = document.createElement("button");
        trigger.type = "button";
        trigger.className = "aln-dd__trigger";
        trigger.setAttribute("aria-haspopup", "listbox");
        trigger.setAttribute("aria-expanded", "false");

        const labelEl = document.createElement("span");
        labelEl.className = "aln-dd__label";
        trigger.appendChild(labelEl);

        const panel = document.createElement("div");
        panel.className = "aln-dd__panel";

        const list = document.createElement("ul");
        list.className = "aln-dd__list";
        list.setAttribute("role", "listbox");

        const items = options.map(function (opt, index) {
            const li = document.createElement("li");
            li.className = "aln-dd__item";
            li.setAttribute("role", "option");
            li.dataset.index = String(index);
            li.textContent = opt.textContent.trim();

            if (opt.value === "") {
                li.classList.add("is-placeholder");

                // Hindi ipapakita sa listahan (para sa ID type lang)
                if (HIDE_PLACEHOLDER_IN.indexOf(select.id) !== -1) {
                    li.hidden = true;
                }
            }

            // Keep focus where it is so the search box doesn't blur mid-click.
            li.addEventListener("mousedown", function (e) {
                e.preventDefault();
            });
            li.addEventListener("click", function () {
                choose(index);
            });

            list.appendChild(li);
            return li;
        });

        panel.append(list);
        wrap.append(trigger, panel);

        select.classList.add("aln-native-select");
        select.insertAdjacentElement("afterend", wrap);

        function visibleItems() {
            return items.filter(function (li) {
                return !li.hidden;
            });
        }

        function setActive(li) {
            items.forEach(function (item) {
                item.classList.remove("is-active");
            });
            if (li) {
                li.classList.add("is-active");
                li.scrollIntoView({ block: "nearest" });
            }
        }

        // Sync the trigger text + selected item from the native select.
        function refresh() {
            const opt = select.options[select.selectedIndex] || select.options[0];

            labelEl.textContent = opt ? opt.textContent.trim() : "";
            trigger.classList.toggle("is-placeholder", !select.value);

            items.forEach(function (li, i) {
                li.classList.toggle("is-selected", i === select.selectedIndex);
            });
        }

        function close() {
            wrap.classList.remove("is-open");
            trigger.setAttribute("aria-expanded", "false");
        }

        function open() {
            alnDropdowns.forEach(function (dd) {
                if (dd.wrap !== wrap) dd.close();
            });

            // Open upward when there isn't room below.
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            wrap.classList.toggle(
                "drop-up",
                spaceBelow < 300 && rect.top > spaceBelow,
            );

            refresh();
            wrap.classList.add("is-open");
            trigger.setAttribute("aria-expanded", "true");

            setActive(items[select.selectedIndex] || null);
        }

        function choose(index) {
            select.selectedIndex = index;
            refresh();
            close();
            select.dispatchEvent(new Event("change", { bubbles: true }));
            trigger.focus();
        }

        trigger.addEventListener("click", function () {
            if (wrap.classList.contains("is-open")) {
                close();
            } else {
                open();
            }
        });

        wrap.addEventListener("keydown", function (e) {
            const isOpen = wrap.classList.contains("is-open");

            if (!isOpen) {
                if (
                    e.target === trigger &&
                    ["ArrowDown", "ArrowUp", "Enter", " "].includes(e.key)
                ) {
                    e.preventDefault();
                    open();
                }
                return;
            }

            if (e.key === "Escape") {
                e.preventDefault();
                close();
                trigger.focus();
            } else if (e.key === "Tab") {
                close();
            } else if (e.key === "ArrowDown" || e.key === "ArrowUp") {
                e.preventDefault();

                const visible = visibleItems();
                if (!visible.length) return;

                let idx = visible.findIndex(function (li) {
                    return li.classList.contains("is-active");
                });
                idx =
                    e.key === "ArrowDown"
                        ? Math.min(idx + 1, visible.length - 1)
                        : Math.max(idx - 1, 0);

                setActive(visible[idx]);
            } else if (e.key === "Enter" || (e.key === " " && e.target === trigger)) {
                e.preventDefault();

                const active = items.find(function (li) {
                    return li.classList.contains("is-active") && !li.hidden;
                });
                if (active) {
                    choose(parseInt(active.dataset.index, 10));
                }
            }
        });

        alnDropdowns.push({ wrap: wrap, close: close, refresh: refresh });
        refresh();
    }

    function initDropdowns() {
        document.querySelectorAll("select.aln-input").forEach(buildDropdown);

        // Click outside closes any open dropdown.
        document.addEventListener("click", function (e) {
            alnDropdowns.forEach(function (dd) {
                if (!dd.wrap.contains(e.target)) dd.close();
            });
        });
    }

    // Re-sync every trigger (needed after the code sets a select's value).
    function refreshDropdowns() {
        alnDropdowns.forEach(function (dd) {
            dd.refresh();
        });
    }

    // =========================================================
    // SOURCE OF INCOME HELPERS (step 3)
    // =========================================================

    function getWorkConfig() {
        return (employmentSelect && WORK_CONFIG[employmentSelect.value]) || null;
    }

    function setVisible(el, visible) {
        if (el) {
            el.style.display = visible ? "" : "none";
        }
    }

    // Shows/hides the fields and changes their labels
    // depending on the selected employment status.
    function updateWorkFields() {
        const cfg = getWorkConfig();

        // "Other Sources" lalabas lang kapag "Others" ang Primary Source of Funds.
        const showOtherSources =
            !!cfg &&
            cfg.funds &&
            !!primarySourceSelect &&
            primarySourceSelect.value === "others";

        setVisible(companyNameWrapper, !!cfg && !!cfg.company);
        setVisible(positionWrapper, !!cfg && !!cfg.position);
        setVisible(yearsWrapper, !!cfg && !!cfg.tenure);
        setVisible(primarySourceWrapper, !!cfg && cfg.funds);
        setVisible(otherSourcesWrapper, showOtherSources);
        setVisible(paySlipSection, !!cfg && cfg.paySlip);
        setVisible(billingSection, !!cfg && !!cfg.billing);
        setVisible(employmentOtherWrapper, !!cfg && !!cfg.other);

        if (cfg && cfg.company) {
            companyNameLabel.textContent = cfg.company;
            companyNameInput.placeholder = cfg.companyPh;
        }

        if (cfg && cfg.position) {
            positionLabel.textContent = cfg.position;
            positionInput.placeholder = cfg.positionPh;
        }

        if (cfg && cfg.tenure && yearsLabel) {
            yearsLabel.textContent = cfg.tenure;
        }

        if (cfg && cfg.paySlip) {
            paySlipTitle.textContent = cfg.paySlipTitle;
            paySlipLabel.textContent = cfg.paySlipLabel;
            paySlipHint.textContent = cfg.paySlipHint;
        }

        // Clear whatever is now hidden so it is never sent to the server.
        if (!cfg || !cfg.company) {
            if (companyNameInput) companyNameInput.value = "";
        }
        if (!cfg || !cfg.position) {
            if (positionInput) positionInput.value = "";
        }
        if (!cfg || !cfg.tenure) {
            // Walang blank option na — ibalik sa unang choice (0 – 5 years).
            if (yearsInput) yearsInput.selectedIndex = 0;
        }
        if (!cfg || !cfg.funds) {
            if (primarySourceSelect) primarySourceSelect.value = "";
        }
        if (!showOtherSources) {
            if (otherSourcesInput) otherSourcesInput.value = "";
        }
        if (!cfg || !cfg.other) {
            if (employmentOtherInput) employmentOtherInput.value = "";
        }
        if (!cfg || !cfg.paySlip) {
            if (paySlipInput) {
                paySlipInput.value = "";
                paySlipInput.dataset.saved = "0";
            }
            const paySlipPreview = document.getElementById("paySlipPreview");
            if (paySlipInput && paySlipPreview) {
                clearPreview(paySlipInput, paySlipPreview);
            }
            // Ibalik ang dropzone para sa susunod na pagpili
            const paySlipZone = document.querySelector(
                '[data-dropzone-for="paySlip"]',
            );
            if (paySlipZone) paySlipZone.classList.remove("aln-hide");
        }
        if (!cfg || !cfg.billing) {
            if (billingInput) {
                billingInput.value = "";
                billingInput.dataset.saved = "0";
            }
            const billingPreview = document.getElementById("billingPreview");
            if (billingInput && billingPreview) {
                clearPreview(billingInput, billingPreview);
            }
            const billingZone = document.querySelector(
                '[data-dropzone-for="proofOfBilling"]',
            );
            if (billingZone) billingZone.classList.remove("aln-hide");
        }

        refreshDropdowns();
    }

    // =========================================================
    // FORM VALIDATION (per step)
    // =========================================================

    function isFilled(input) {
        return !!input && input.value.trim().length > 0;
    }

    // Step 1 — Loan details
    function isStep1Valid() {
        const amount = getLoanAmountValue();

        const validAmount = amount >= MIN_AMOUNT && amount <= MAX_AMOUNT;

        const validTerm = !!getSelectedTerm();

        const validPurpose = loanPurposeSelect && !!loanPurposeSelect.value;

        // Kapag "Others" ang purpose, kailangang may nakasulat.
        const validPurposeOther =
            !loanPurposeSelect ||
            loanPurposeSelect.value !== "others" ||
            isFilled(loanPurposeOtherInput);

        const validReferral = referralSelect && !!referralSelect.value;

        // Kapag "Others" ang referral source, kailangang may nakasulat.
        const validReferralOther =
            !referralSelect ||
            referralSelect.value !== "others" ||
            isFilled(referralOtherInput);

        const validReferralName =
            !referralSelect ||
            referralSelect.value !== "friend_family" ||
            isFilled(referralNameInput);

        return (
            validAmount &&
            validTerm &&
            validPurpose &&
            validPurposeOther &&
            validReferral &&
            validReferralOther &&
            validReferralName
        );
    }

    // Step 2 — Valid ID
    function isStep2Valid() {
        const validIdType = idTypeSelect && !!idTypeSelect.value;
        const validIdOthers =
            !idTypeSelect ||
            idTypeSelect.value !== "others" ||
            isFilled(idTypeOthersInput);
        const validIdNumber = isFilled(idNumberInput);
        const validIdFiles =
            hasFile(idFrontInput) &&
            hasFile(idBackInput) &&
            hasFile(selfieInput);

        return validIdType && validIdOthers && validIdNumber && validIdFiles;
    }

    // Step 3 — Source of income + pay slip / proof of billing
    function isStep3Valid() {
        const cfg = getWorkConfig();

        if (!employmentSelect || !employmentSelect.value || !cfg) {
            return false;
        }

        // Company / Business / School name
        if (cfg.company && !isFilled(companyNameInput)) {
            return false;
        }

        // Job Title
        if (cfg.position && !isFilled(positionInput)) {
            return false;
        }

        // "Others" — kailangang may nakasulat sa Please specify
        if (cfg.other && !isFilled(employmentOtherInput)) {
            return false;
        }

        // Primary Source of Funds
        if (cfg.funds && !(primarySourceSelect && primarySourceSelect.value)) {
            return false;
        }

        // Kapag "Others" ang Primary Source of Funds, kailangang may nakasulat
        if (
            cfg.funds &&
            primarySourceSelect &&
            primarySourceSelect.value === "others" &&
            !isFilled(otherSourcesInput)
        ) {
            return false;
        }

        if (cfg.paySlip && !hasFile(paySlipInput)) {
            return false;
        }

        // Proof of billing (Unemployed / Retired / Others)
        if (cfg.billing && !hasFile(billingInput)) {
            return false;
        }

        return true;
    }

    function isStepValid(step) {
        if (step === 1) return isStep1Valid();
        if (step === 2) return isStep2Valid();
        return isStep3Valid();
    }

    // Updates the Next / Submit buttons. Returns true if everything is valid.
    function validateForm() {
        refreshDropdowns();

        const allValid = isStep1Valid() && isStep2Valid() && isStep3Valid();

        if (nextBtn) {
            nextBtn.disabled = !isStepValid(currentStep);
        }

        if (submitBtn) {
            submitBtn.disabled = !allValid;
        }

        return allValid;
    }

    // =========================================================
    // WIZARD — step navigation
    // =========================================================

    let currentStep = 1;

    function showStep(step) {
        currentStep = Math.min(Math.max(step, 1), TOTAL_STEPS);

        // Show only the active step.
        stepEls.forEach(function (el) {
            el.classList.toggle(
                "is-active",
                parseInt(el.dataset.step, 10) === currentStep,
            );
        });

        // Progress circles + connectors.
        if (progressEl) {
            const steps = progressEl.querySelectorAll(".aln-progress-step");
            const connectors = progressEl.querySelectorAll(
                ".aln-progress-connector",
            );

            steps.forEach(function (el, index) {
                const number = index + 1;
                const circle = el.querySelector(".aln-progress-circle");

                el.classList.toggle("active", number === currentStep);
                el.classList.toggle("done", number < currentStep);

                if (circle) {
                    circle.textContent = number < currentStep ? "✓" : number;
                }
            });

            connectors.forEach(function (el, index) {
                el.classList.toggle("done", index + 1 < currentStep);
            });
        }

        // Buttons: Cancel on step 1, Back on steps 2–3,
        // Next on steps 1–2, Submit on the last step.
        const isFirst = currentStep === 1;
        const isLast = currentStep === TOTAL_STEPS;

        if (cancelBtn) cancelBtn.classList.toggle("aln-hide", !isFirst);
        if (backBtn) backBtn.classList.toggle("aln-hide", isFirst);
        if (nextBtn) nextBtn.classList.toggle("aln-hide", isLast);
        if (submitBtn) submitBtn.classList.toggle("aln-hide", !isLast);

        validateForm();
    }

    function goToStep(step) {
        showStep(step);
        scheduleDraftSave();

        if (progressEl) {
            progressEl.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            if (isStepValid(currentStep)) {
                goToStep(currentStep + 1);
            }
        });
    }

    if (backBtn) {
        backBtn.addEventListener("click", function () {
            goToStep(currentStep - 1);
        });
    }

    // =========================================================
    // UPDATE SUMMARY
    // =========================================================

    function updateSummary() {
        const amount = clampLoanAmount();

        const selectedTerm = getSelectedTerm();
        const term = selectedTerm ? parseInt(selectedTerm, 10) : 0;

        // Total is still computed here only to get the monthly payment;
        // it is no longer shown in the modal.
        const totalInterest = amount * INTEREST_RATE * term;

        const totalAmount = amount + totalInterest;

        const monthlyPayment = term > 0 ? totalAmount / term : 0;

        // Only update elements that exist.
        if (displayPrincipal) {
            displayPrincipal.textContent = formatPeso(amount);
        }

        if (displayTerm) {
            displayTerm.textContent =
                term > 0 ? term + (term === 1 ? " month" : " months") : "— months";
        }

        if (displayMonthlyPayment) {
            displayMonthlyPayment.textContent = formatPeso(monthlyPayment);
        }

        validateForm();
    }

    // =========================================================
    // REQUEST ANIMATION FRAME
    // =========================================================
    //
    // Prevents multiple calculations from firing back-to-back
    // when the user types quickly.
    //

    let summaryFrame = null;

    function scheduleSummaryUpdate() {
        if (summaryFrame !== null) {
            cancelAnimationFrame(summaryFrame);
        }

        summaryFrame = requestAnimationFrame(function () {
            summaryFrame = null;
            updateSummary();
        });
    }

    // =========================================================
    // EVENT LISTENERS
    // =========================================================

    // Loan amount: lagyan agad ng kuwit, tapos i-update ang summary
    loanAmountInput.addEventListener("input", function () {
        formatLoanAmountInput();
        scheduleSummaryUpdate();
    });

    // Loan term (dropdown)
    if (loanTermSelect) {
        loanTermSelect.addEventListener("change", updateSummary);
    }

    // Loan purpose (may text box kapag "Others")
    if (loanPurposeSelect) {
        loanPurposeSelect.addEventListener("change", function () {
            const isOthers = this.value === "others";

            if (loanPurposeOtherField) {
                loanPurposeOtherField.style.display = isOthers
                    ? "block"
                    : "none";
            }

            if (isOthers) {
                if (loanPurposeOtherInput) {
                    loanPurposeOtherInput.focus();
                }
            } else if (loanPurposeOtherInput) {
                loanPurposeOtherInput.value = "";
            }

            validateForm();
        });
    }
    if (loanPurposeOtherInput) {
        loanPurposeOtherInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }

    // Referral "Others" text
    if (referralOtherInput) {
        referralOtherInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }

    // Referral name
    if (referralNameInput) {
        referralNameInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }

    // Referral source (may text box kapag "Others", may name field kapag "Friend / Family")
    if (referralSelect) {
        referralSelect.addEventListener("change", function () {
            const isOthers = this.value === "others";
            const isFriendFamily = this.value === "friend_family";

            if (referralOtherField) {
                referralOtherField.style.display = isOthers ? "block" : "none";
            }

            if (isOthers) {
                if (referralOtherInput) {
                    referralOtherInput.focus();
                }
            } else if (referralOtherInput) {
                referralOtherInput.value = "";
            }

            if (referralNameField) {
                referralNameField.style.display = isFriendFamily
                    ? "block"
                    : "none";
            }

            if (!isFriendFamily && referralNameInput) {
                referralNameInput.value = "";
            }

            validateForm();
        });
    }

    // Valid ID
    if (idTypeSelect) {
        idTypeSelect.addEventListener("change", function () {
            toggleIdTypeOthers();
            updateIdNumberField();
            sanitizeIdNumber();
            validateForm();
        });
        toggleIdTypeOthers(); // para sa prefilled "others"
    }
    updateIdNumberField(); // para sa prefilled na uri ng ID

    if (idTypeOthersInput) {
        idTypeOthersInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }
    if (idNumberInput) {
        idNumberInput.addEventListener(
            "input",
            function () {
                sanitizeIdNumber();
                validateForm();
            },
            { passive: true },
        );
    }

    setupFilePreview(idFrontInput, "idFrontPreview");
    setupFilePreview(idBackInput, "idBackPreview");
    setupFilePreview(selfieInput, "selfiePreview");

    // Source of income (step 3)
    if (employmentSelect) {
        employmentSelect.addEventListener("change", function () {
            updateWorkFields();
            validateForm();
        });
    }
    if (employmentOtherInput) {
        employmentOtherInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }
    if (companyNameInput) {
        companyNameInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }
    if (positionInput) {
        positionInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }
    if (primarySourceSelect) {
        // Ipapakita / itatago ang "Other Sources" depende kung "Others" ang napili.
        primarySourceSelect.addEventListener("change", function () {
            updateWorkFields();
            validateForm();
        });
    }
    if (otherSourcesInput) {
        otherSourcesInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }

    setupFilePreview(paySlipInput, "paySlipPreview");
    setupFilePreview(billingInput, "billingPreview");

    // Show the right fields for the default / prefilled employment status.
    updateWorkFields();

    // Certification checkbox
    if (agreeCheckbox && finalSubmitBtn) {
        agreeCheckbox.addEventListener("change", function () {
            finalSubmitBtn.disabled = !this.checked;
        });
    }

    // =========================================================
    // LOAN SUMMARY MODAL — purpose + documents checklist
    // =========================================================
    //
    // Refreshed every time the modal opens, so it always matches
    // what the borrower entered. The checkbox starts unchecked
    // each time, so Confirm & Submit stays disabled until ticked.

    const displayPurpose = document.getElementById("displayPurpose");
    const displayDocs = document.getElementById("displayDocs");
    const summaryModalEl = document.getElementById("loanSummaryModal");

    function refreshModalReview() {
        if (displayPurpose && loanPurposeSelect) {
            let text = "—";
            if (loanPurposeSelect.value === "others") {
                text =
                    (loanPurposeOtherInput &&
                        loanPurposeOtherInput.value.trim()) ||
                    "Others";
            } else if (loanPurposeSelect.selectedIndex >= 0) {
                const opt =
                    loanPurposeSelect.options[loanPurposeSelect.selectedIndex];
                if (opt && loanPurposeSelect.value) {
                    text = opt.textContent.trim();
                }
            }
            displayPurpose.textContent = text;
        }

        if (displayDocs) {
            const docs = [
                ["Front of ID", hasFile(idFrontInput)],
                ["Back of ID", hasFile(idBackInput)],
                ["Selfie with Valid ID", hasFile(selfieInput)],
            ];
            const payslipVisible =
                paySlipSection && paySlipSection.style.display !== "none";
            if (payslipVisible) {
                docs.push(["Pay Slip / Proof of Income", hasFile(paySlipInput)]);
            }

            const billingVisible =
                billingSection && billingSection.style.display !== "none";
            if (billingVisible) {
                docs.push(["Proof of Billing", hasFile(billingInput)]);
            }

            displayDocs.innerHTML = docs
                .map(function (d) {
                    return (
                        '<li class="' +
                        (d[1] ? "is-ok" : "is-missing") +
                        '"><span class="aln-doc-mark">' +
                        (d[1] ? "✓" : "✕") +
                        "</span>" +
                        d[0] +
                        "</li>"
                    );
                })
                .join("");
        }

        if (agreeCheckbox) {
            agreeCheckbox.checked = false;
        }
        if (finalSubmitBtn) {
            finalSubmitBtn.disabled = true;
        }
    }

    if (summaryModalEl) {
        summaryModalEl.addEventListener("show.bs.modal", refreshModalReview);
    }

    // =========================================================
    // DRAFT — auto-saved in this browser (localStorage)
    // =========================================================
    //
    // Only text fields and the current step are saved.
    // Pictures can't be stored, and the ID number is deliberately
    // NOT saved, since it is sensitive and this may be a shared device.
    // The draft expires after 7 days and is cleared on submit.

    const DRAFT_KEY = "alnLoanDraft:" + (USER_ID || "guest");
    const DRAFT_MAX_AGE = 7 * 24 * 60 * 60 * 1000;

    const DRAFT_FIELDS = {
        loanAmount: loanAmountInput,
        loanTerm: loanTermSelect,
        loanPurpose: loanPurposeSelect,
        loanPurposeOther: loanPurposeOtherInput,
        referralSource: referralSelect,
        referralOther: referralOtherInput,
        referralName: referralNameInput,
        idType: idTypeSelect,
        idTypeOthers: idTypeOthersInput,
        employmentStatus: employmentSelect,
        employmentOther: employmentOtherInput,
        companyName: companyNameInput,
        position: positionInput,
        yearsInCompany: yearsInput,
        primarySource: primarySourceSelect,
        otherSources: otherSourcesInput,
    };

    function collectDraft() {
        const data = {};

        Object.keys(DRAFT_FIELDS).forEach(function (key) {
            const el = DRAFT_FIELDS[key];
            if (!el) return;

            data[key] =
                key === "loanAmount"
                    ? String(getLoanAmountValue() || "")
                    : el.value;
        });

        return data;
    }

    // Walang "Select status" na, kaya laging may laman ang
    // employmentStatus (Employed). Hindi natin ito binibilang
    // para hindi mag-save ng draft kung wala namang ginalaw.
    function hasDraftContent(data) {
        return Object.keys(data).some(function (key) {
            return key !== "employmentStatus" && data[key] !== "";
        });
    }

    function saveDraft() {
        try {
            const data = collectDraft();

            if (!hasDraftContent(data)) {
                localStorage.removeItem(DRAFT_KEY);
                return;
            }

            localStorage.setItem(
                DRAFT_KEY,
                JSON.stringify({
                    savedAt: Date.now(),
                    step: currentStep,
                    data: data,
                }),
            );
        } catch (err) {
            // localStorage unavailable (private mode, full, etc.) — not critical.
        }
    }

    let draftTimer = null;

    function scheduleDraftSave() {
        clearTimeout(draftTimer);
        draftTimer = setTimeout(saveDraft, 500);
    }

    function clearDraft() {
        clearTimeout(draftTimer);
        try {
            localStorage.removeItem(DRAFT_KEY);
        } catch (err) {
            // ignore
        }
    }

    // Returns the step to open (0 if there is no draft).
    function restoreDraft() {
        let draft = null;

        try {
            draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || "null");
        } catch (err) {
            draft = null;
        }

        if (!draft || !draft.data) {
            return 0;
        }

        if (Date.now() - draft.savedAt > DRAFT_MAX_AGE) {
            clearDraft();
            return 0;
        }

        const data = draft.data;

        Object.keys(DRAFT_FIELDS).forEach(function (key) {
            const el = DRAFT_FIELDS[key];
            if (el && data[key]) {
                el.value = data[key];
            }
        });

        // Run the existing change handlers so the "Others" boxes,
        // work fields and labels show up correctly.
        [loanPurposeSelect, referralSelect, idTypeSelect, employmentSelect].forEach(
            function (el) {
                if (el) el.dispatchEvent(new Event("change"));
            },
        );

        clampLoanAmount();

        if (document.activeElement && document.activeElement.blur) {
            document.activeElement.blur();
        }

        return parseInt(draft.step, 10) || 1;
    }

    // Save while the borrower types / selects
    if (formEl) {
        formEl.addEventListener("input", scheduleDraftSave, { passive: true });
        formEl.addEventListener("change", scheduleDraftSave);
    }

    // Cancel confirmation modal: "Save & Leave" saves right away
    if (leaveBtn) {
        leaveBtn.addEventListener("click", saveDraft);
    }

    // Save immediately when the tab is refreshed, closed, or hidden,
    // so a pending (debounced) save is never lost.
    window.addEventListener("pagehide", saveDraft);
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "hidden") {
            saveDraft();
        }
    });

    // =========================================================
    // SUBMIT APPLICATION
    // =========================================================

    window.submitApplication = function () {
        if (!finalSubmitBtn) {
            return;
        }

        // Prevent double submission
        finalSubmitBtn.disabled = true;

        finalSubmitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        // =====================================================
        // FORM DATA
        // =====================================================

        const formData = new FormData();

        // Digits lang ang ipinapadala (walang kuwit).
        formData.append("loanAmount", String(getLoanAmountValue()));

        formData.append("loanTerm", getSelectedTerm());

        formData.append(
            "loanPurpose",
            loanPurposeSelect ? loanPurposeSelect.value : "",
        );

        formData.append(
            "loanPurposeOther",
            loanPurposeOtherInput ? loanPurposeOtherInput.value.trim() : "",
        );

        formData.append(
            "referralSource",
            referralSelect ? referralSelect.value : "",
        );

        formData.append(
            "referralOther",
            referralOtherInput ? referralOtherInput.value.trim() : "",
        );

        formData.append(
            "referralName",
            referralNameInput ? referralNameInput.value : "",
        );

        // Valid ID
        formData.append("idType", idTypeSelect ? idTypeSelect.value : "");
        formData.append(
            "idTypeOthers",
            idTypeOthersInput ? idTypeOthersInput.value : "",
        );
        formData.append("idNumber", idNumberInput ? idNumberInput.value : "");

        if (idFrontInput && idFrontInput.files[0]) {
            formData.append("validIdFront", idFrontInput.files[0]);
        }
        if (idBackInput && idBackInput.files[0]) {
            formData.append("validIdBack", idBackInput.files[0]);
        }
        if (selfieInput && selfieInput.files[0]) {
            formData.append("selfieWithId", selfieInput.files[0]);
        }

        // Source of income + pay slip / proof of billing
        // companyName  = Employer / Company Name, Business Name, o School Name
        // position     = Job Title
        // primarySource / otherSources = para sa Unemployed, Student, Retired
        //   (otherSources ay may laman lang kapag "Others" ang primarySource)
        // employmentOther = "Please specify" kapag "Others" ang employment status
        formData.append(
            "employmentStatus",
            employmentSelect ? employmentSelect.value : "",
        );
        formData.append(
            "employmentOther",
            employmentOtherInput ? employmentOtherInput.value.trim() : "",
        );
        formData.append(
            "companyName",
            companyNameInput ? companyNameInput.value.trim() : "",
        );
        formData.append(
            "position",
            positionInput ? positionInput.value.trim() : "",
        );
        formData.append(
            "yearsInCompany",
            yearsInput ? yearsInput.value : "",
        );
        formData.append(
            "primarySource",
            primarySourceSelect ? primarySourceSelect.value : "",
        );
        formData.append(
            "otherSources",
            otherSourcesInput ? otherSourcesInput.value.trim() : "",
        );

        if (paySlipInput && paySlipInput.files[0]) {
            formData.append("paySlip", paySlipInput.files[0]);
        }

        // Proof of billing (Unemployed / Retired / Others)
        if (billingInput && billingInput.files[0]) {
            formData.append("proofOfBilling", billingInput.files[0]);
        }

        formData.append("_token", CSRF_TOKEN || "");

        // =====================================================
        // AJAX REQUEST
        // =====================================================

        fetch(SUBMIT_URL, {
            method: "POST",

            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },

            body: formData,
        })
            .then(function (response) {
                // MAHALAGA: hindi na natin ginagamit ang response.ok bilang
                // basehan kung dapat throw. Isang valid na JSON error
                // response pa rin ang 422 (hal. "You already have an
                // active or pending loan application.") — kailangan
                // pa rin nating i-parse ang JSON nito bago tayo mag-desisyon.
                // Genuine network errors/connection failures na lang
                // ang dapat tumapon papunta sa .catch() sa ibaba.
                return response.json();
            })

            .then(function (data) {
                if (data.success) {
                    clearDraft();

                    // Flag the toast to show after reload, since the
                    // page needs to reload anyway to re-render the
                    // "pending application" card from the server
                    // (hasPendingApplication is computed server-side).
                    try {
                        sessionStorage.setItem(
                            "loanApplicationSubmitted",
                            "1",
                        );
                    } catch (err) {
                        // sessionStorage unavailable (private mode, etc.)
                        // — not critical, the reload still happens.
                    }

                    window.location.reload();
                } else {
                    showErrorToast(
                        data.message ||
                            "Something went wrong. Please try again.",
                    );

                    resetSubmitButton();
                }
            })

            .catch(function (error) {
                // Dito na lang dapat tumatama ang totoong network
                // failures o hindi-JSON na responses.
                console.error("Loan submission error:", error);

                showErrorToast(
                    "Something went wrong. Please check your connection and try again.",
                );

                resetSubmitButton();
            });
    };

    // =========================================================
    // RESET SUBMIT BUTTON
    // =========================================================

    function resetSubmitButton() {
        if (!finalSubmitBtn) {
            return;
        }

        finalSubmitBtn.disabled = agreeCheckbox
            ? !agreeCheckbox.checked
            : false;

        finalSubmitBtn.textContent = "Confirm & Submit";
    }

    // =========================================================
    // INITIAL STATE
    // =========================================================

    initDropdowns();
    const restoredStep = restoreDraft();
    showStep(restoredStep || 1);
    updateSummary();
})();