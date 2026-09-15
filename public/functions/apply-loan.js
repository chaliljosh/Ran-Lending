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
                if (!response.ok) {
                    throw new Error(
                        "Request failed with status " + response.status,
                    );
                }

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
                    alert(
                        data.message ||
                            "Something went wrong. Please try again.",
                    );

                    resetSubmitButton();
                }
            })

            .catch(function (error) {
                console.error("Loan submission error:", error);

                alert(
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