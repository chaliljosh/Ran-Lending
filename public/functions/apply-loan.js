// Apply for a Loan — optimized form validation,
// live summary calculator, and AJAX submission.
//
// Expects:
// window.APPLY_LOAN_CONFIG = {
//     csrfToken,
//     submitUrl
// };

(function () {
    'use strict';

    // =========================================================
    // DOM ELEMENTS
    // =========================================================

    const loanAmountInput   = document.getElementById('loanAmount');
    const loanTermSelect    = document.getElementById('loanTerm');
    const loanPurposeSelect = document.getElementById('loanPurpose');
    const referralSelect    = document.getElementById('referralSource');

    const referralNameField = document.getElementById('referralNameField');
    const referralNameInput = document.getElementById('referralName');

    const submitBtn         = document.getElementById('submitBtn');
    const agreeCheckbox     = document.getElementById('agreeTerms');
    const finalSubmitBtn    = document.getElementById('finalSubmitBtn');

    // Summary elements
    const displayPrincipal       = document.getElementById('displayPrincipal');
    const displayTotalInterest   = document.getElementById('displayTotalInterest');
    const displayTotalAmount     = document.getElementById('displayTotalAmount');
    const displayTerm            = document.getElementById('displayTerm');
    const displayMonthlyPayment  = document.getElementById('displayMonthlyPayment');

    // Modal / overlay
    const termsModalElement = document.getElementById('termsModal');
    const successOverlay    = document.getElementById('successOverlay');


    // =========================================================
    // STOP IF FORM DOES NOT EXIST
    // =========================================================

    if (!loanAmountInput) {
        return;
    }


    // =========================================================
    // CONSTANTS
    // =========================================================

    const INTEREST_RATE = 0.05; // 5% monthly
    const MIN_AMOUNT = 1000;
    const MAX_AMOUNT = 10000;


    // =========================================================
    // NUMBER FORMATTER
    // =========================================================

    const pesoFormatter = new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    function formatPeso(value) {
        return '₱' + pesoFormatter.format(value);
    }


    // =========================================================
    // CLAMP LOAN AMOUNT
    // =========================================================

    function clampLoanAmount() {

        const value = loanAmountInput.value;

        if (value === '') {
            return 0;
        }

        // Only allow a reasonable number of digits.
        if (value.length > String(MAX_AMOUNT).length) {
            loanAmountInput.value =
                value.slice(0, String(MAX_AMOUNT).length);
        }

        let amount = parseFloat(loanAmountInput.value);

        if (Number.isNaN(amount)) {
            loanAmountInput.value = '';
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

        const validAmount =
            amount >= MIN_AMOUNT &&
            amount <= MAX_AMOUNT;

        const validTerm =
            loanTermSelect &&
            !!loanTermSelect.value;

        const validPurpose =
            loanPurposeSelect &&
            !!loanPurposeSelect.value;

        const validReferral =
            referralSelect &&
            !!referralSelect.value;

        const validReferralName =
            !referralSelect ||
            referralSelect.value !== 'friend_family' ||
            (
                referralNameInput &&
                referralNameInput.value.trim().length > 0
            );

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
            loanTermSelect &&
            loanTermSelect.value
                ? parseInt(loanTermSelect.value, 10)
                : 0;

        const totalInterest =
            amount * INTEREST_RATE * term;

        const totalAmount =
            amount + totalInterest;

        const monthlyPayment =
            term > 0
                ? totalAmount / term
                : 0;


        // Only update elements that exist.
        if (displayPrincipal) {
            displayPrincipal.textContent =
                formatPeso(amount);
        }

        if (displayTotalInterest) {
            displayTotalInterest.textContent =
                formatPeso(totalInterest);
        }

        if (displayTotalAmount) {
            displayTotalAmount.textContent =
                formatPeso(totalAmount);
        }

        if (displayTerm) {
            displayTerm.textContent =
                term > 0
                    ? term + ' months'
                    : '— months';
        }

        if (displayMonthlyPayment) {
            displayMonthlyPayment.textContent =
                formatPeso(monthlyPayment);
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
    loanAmountInput.addEventListener(
        'input',
        scheduleSummaryUpdate,
        { passive: true }
    );


    // Loan term
    if (loanTermSelect) {
        loanTermSelect.addEventListener(
            'change',
            updateSummary
        );
    }


    // Loan purpose
    if (loanPurposeSelect) {
        loanPurposeSelect.addEventListener(
            'change',
            validateForm
        );
    }


    // Referral name
    if (referralNameInput) {
        referralNameInput.addEventListener(
            'input',
            validateForm,
            { passive: true }
        );
    }


    // Referral source
    if (referralSelect) {

        referralSelect.addEventListener(
            'change',
            function () {

                const isFriendFamily =
                    this.value === 'friend_family';

                if (referralNameField) {
                    referralNameField.style.display =
                        isFriendFamily
                            ? 'block'
                            : 'none';
                }

                if (!isFriendFamily && referralNameInput) {
                    referralNameInput.value = '';
                }

                validateForm();
            }
        );
    }


    // Terms checkbox
    if (agreeCheckbox && finalSubmitBtn) {

        agreeCheckbox.addEventListener(
            'change',
            function () {

                finalSubmitBtn.disabled =
                    !this.checked;
            }
        );
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
            '<span class="spinner-border spinner-border-sm me-2"></span>' +
            'Submitting...';


        const config =
            window.APPLY_LOAN_CONFIG || {};


        // =====================================================
        // FORM DATA
        // =====================================================

        const formData = new FormData();

        formData.append(
            'loanAmount',
            loanAmountInput.value
        );

        formData.append(
            'loanTerm',
            loanTermSelect
                ? loanTermSelect.value
                : ''
        );

        formData.append(
            'loanPurpose',
            loanPurposeSelect
                ? loanPurposeSelect.value
                : ''
        );

        formData.append(
            'referralSource',
            referralSelect
                ? referralSelect.value
                : ''
        );

        formData.append(
            'referralName',
            referralNameInput
                ? referralNameInput.value
                : ''
        );

        formData.append(
            '_token',
            config.csrfToken || ''
        );


        // =====================================================
        // AJAX REQUEST
        // =====================================================

        fetch(config.submitUrl, {
            method: 'POST',

            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },

            body: formData
        })

        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    'Request failed with status ' +
                    response.status
                );
            }

            return response.json();
        })

        .then(function (data) {

            if (data.success) {

                // Hide terms modal
                if (termsModalElement) {

                    const modal =
                        bootstrap.Modal.getInstance(
                            termsModalElement
                        );

                    if (modal) {
                        modal.hide();
                    }
                }


                // Show success overlay
                if (successOverlay) {
                    successOverlay.style.display = 'flex';
                }

            } else {

                alert(
                    data.message ||
                    'Something went wrong. Please try again.'
                );

                resetSubmitButton();
            }
        })

        .catch(function (error) {

            console.error(
                'Loan submission error:',
                error
            );

            alert(
                'Something went wrong. Please check your connection and try again.'
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

        finalSubmitBtn.disabled =
            agreeCheckbox
                ? !agreeCheckbox.checked
                : false;

        finalSubmitBtn.innerHTML =
            '<i class="feather-check me-2"></i>' +
            'Accept & Submit';
    }


    // =========================================================
    // INITIAL STATE
    // =========================================================

    updateSummary();

})();