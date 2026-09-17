// Apply for a Loan — optimized form validation,
// live summary calculator, AJAX submission, and the
// post-submit success toast check.
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
    // Server computes this based on loan history (₱10,000 for
    // first-time borrowers, ₱100,000 once they've completed a loan).
    const MAX_AMOUNT = scriptTag
        ? parseInt(scriptTag.dataset.maxAmount, 10) || 10000
        : 10000;

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
    const loanTermSelect = document.getElementById("loanTerm");
    const loanPurposeSelect = document.getElementById("loanPurpose");
    const referralSelect = document.getElementById("referralSource");

    const referralNameField = document.getElementById("referralNameField");
    const referralNameInput = document.getElementById("referralName");

    const submitBtn = document.getElementById("submitBtn");
    const agreeCheckbox = document.getElementById("agreeTerms");
    const finalSubmitBtn = document.getElementById("finalSubmitBtn");

    // Summary elements
    const displayPrincipal = document.getElementById("displayPrincipal");
    const displayTotalInterest = document.getElementById(
        "displayTotalInterest",
    );
    const displayTotalAmount = document.getElementById("displayTotalAmount");
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
    // CLAMP LOAN AMOUNT
    // =========================================================

    function clampLoanAmount() {
        const value = loanAmountInput.value;

        if (value === "") {
            return 0;
        }

        // Only allow a reasonable number of digits.
        if (value.length > String(MAX_AMOUNT).length) {
            loanAmountInput.value = value.slice(0, String(MAX_AMOUNT).length);
        }

        let amount = parseFloat(loanAmountInput.value);

        if (Number.isNaN(amount)) {
            loanAmountInput.value = "";
            return 0;
        }

        if (amount > MAX_AMOUNT) {
            amount = MAX_AMOUNT;
            loanAmountInput.value = MAX_AMOUNT;
        }

        if (amount < 0) {
            amount = 0;
            loanAmountInput.value = 0;
        }

        return amount;
    }

    // =========================================================
    // FORM VALIDATION
    // =========================================================

    function validateForm() {
        const amount = parseFloat(loanAmountInput.value) || 0;

        const validAmount = amount >= MIN_AMOUNT && amount <= MAX_AMOUNT;

        const validTerm = loanTermSelect && !!loanTermSelect.value;

        const validPurpose = loanPurposeSelect && !!loanPurposeSelect.value;

        const validReferral = referralSelect && !!referralSelect.value;

        const validReferralName =
            !referralSelect ||
            referralSelect.value !== "friend_family" ||
            (referralNameInput && referralNameInput.value.trim().length > 0);

        const isValid =
            validAmount &&
            validTerm &&
            validPurpose &&
            validReferral &&
            validReferralName;

        if (submitBtn) {
            submitBtn.disabled = !isValid;
        }

        return isValid;
    }

    // =========================================================
    // UPDATE SUMMARY
    // =========================================================

    function updateSummary() {
        const amount = clampLoanAmount();

        const term =
            loanTermSelect && loanTermSelect.value
                ? parseInt(loanTermSelect.value, 10)
                : 0;

        const totalInterest = amount * INTEREST_RATE * term;

        const totalAmount = amount + totalInterest;

        const monthlyPayment = term > 0 ? totalAmount / term : 0;

        // Only update elements that exist.
        if (displayPrincipal) {
            displayPrincipal.textContent = formatPeso(amount);
        }

        if (displayTotalInterest) {
            displayTotalInterest.textContent = formatPeso(totalInterest);
        }

        if (displayTotalAmount) {
            displayTotalAmount.textContent = formatPeso(totalAmount);
        }

        if (displayTerm) {
            displayTerm.textContent = term > 0 ? term + " months" : "— months";
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

    // Loan amount
    loanAmountInput.addEventListener("input", scheduleSummaryUpdate, {
        passive: true,
    });

    // Loan term
    if (loanTermSelect) {
        loanTermSelect.addEventListener("change", updateSummary);
    }

    // Loan purpose
    if (loanPurposeSelect) {
        loanPurposeSelect.addEventListener("change", validateForm);
    }

    // Referral name
    if (referralNameInput) {
        referralNameInput.addEventListener("input", validateForm, {
            passive: true,
        });
    }

    // Referral source
    if (referralSelect) {
        referralSelect.addEventListener("change", function () {
            const isFriendFamily = this.value === "friend_family";

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

    // Terms checkbox
    if (agreeCheckbox && finalSubmitBtn) {
        agreeCheckbox.addEventListener("change", function () {
            finalSubmitBtn.disabled = !this.checked;
        });
    }

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

        formData.append("loanAmount", loanAmountInput.value);

        formData.append("loanTerm", loanTermSelect ? loanTermSelect.value : "");

        formData.append(
            "loanPurpose",
            loanPurposeSelect ? loanPurposeSelect.value : "",
        );

        formData.append(
            "referralSource",
            referralSelect ? referralSelect.value : "",
        );

        formData.append(
            "referralName",
            referralNameInput ? referralNameInput.value : "",
        );

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

        finalSubmitBtn.textContent = "Accept & Submit";
    }

    // =========================================================
    // INITIAL STATE
    // =========================================================

    updateSummary();
})();