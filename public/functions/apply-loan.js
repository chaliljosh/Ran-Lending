// Apply for a Loan — form validation, live summary calculator, and AJAX submission.
// Expects window.APPLY_LOAN_CONFIG = { csrfToken, submitUrl } to be set inline before this file loads.

(function () {
    const loanAmountInput   = document.getElementById('loanAmount');
    const loanTermSelect    = document.getElementById('loanTerm');
    const loanPurposeSelect = document.getElementById('loanPurpose');
    const referralSelect    = document.getElementById('referralSource');
    const referralNameField = document.getElementById('referralNameField');
    const referralNameInput = document.getElementById('referralName');
    const submitBtn         = document.getElementById('submitBtn');
    const agreeCheckbox     = document.getElementById('agreeTerms');
    const finalSubmitBtn    = document.getElementById('finalSubmitBtn');


    if (!loanAmountInput) return;

    const INTEREST_RATE = 0.05; // 5% monthly
    const MIN_AMOUNT = 1000;
    const MAX_AMOUNT = 10000;

    
    function clampLoanAmount() {
        if (loanAmountInput.value === '') return;


        if (loanAmountInput.value.length > String(MAX_AMOUNT).length) {
            loanAmountInput.value = loanAmountInput.value.slice(0, String(MAX_AMOUNT).length);
        }

        const amount = parseFloat(loanAmountInput.value);

        if (isNaN(amount)) {
            loanAmountInput.value = '';
        } else if (amount > MAX_AMOUNT) {
            loanAmountInput.value = MAX_AMOUNT;
        } else if (amount < 0) {
            loanAmountInput.value = 0;
        }
    }

    function recalcSummary() {
        clampLoanAmount();

        const amount = parseFloat(loanAmountInput.value) || 0;
        const term = parseInt(loanTermSelect.value) || 0;

        const totalInterest = amount * INTEREST_RATE * term;
        const totalAmount = amount + totalInterest;
        const monthlyPayment = term > 0 ? totalAmount / term : 0;

        document.getElementById('displayPrincipal').textContent = '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('displayTotalInterest').textContent = '₱' + totalInterest.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('displayTotalAmount').textContent = '₱' + totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('displayTerm').textContent = term ? term + ' months' : '— months';
        document.getElementById('displayMonthlyPayment').textContent = '₱' + monthlyPayment.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        validateForm();
    }

    function validateForm() {
        const amount = parseFloat(loanAmountInput.value) || 0;
        const validAmount = amount >= MIN_AMOUNT && amount <= MAX_AMOUNT;
        const validTerm = !!loanTermSelect.value;
        const validPurpose = !!loanPurposeSelect.value;
        const validReferral = !!referralSelect.value;
        const validReferralName = referralSelect.value !== 'friend_family' || referralNameInput.value.trim().length > 0;

        submitBtn.disabled = !(validAmount && validTerm && validPurpose && validReferral && validReferralName);
    }

    loanAmountInput.addEventListener('input', recalcSummary);
    loanTermSelect.addEventListener('change', recalcSummary);
    loanPurposeSelect.addEventListener('change', validateForm);
    referralNameInput.addEventListener('input', validateForm);

    referralSelect.addEventListener('change', function () {
        referralNameField.style.display = this.value === 'friend_family' ? 'block' : 'none';
        if (this.value !== 'friend_family') referralNameInput.value = '';
        validateForm();
    });

    agreeCheckbox.addEventListener('change', function () {
        finalSubmitBtn.disabled = !this.checked;
    });

    window.submitApplication = function () {
        finalSubmitBtn.disabled = true;
        finalSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

        const config = window.APPLY_LOAN_CONFIG || {};

        const formData = new FormData();
        formData.append('loanAmount', loanAmountInput.value);
        formData.append('loanTerm', loanTermSelect.value);
        formData.append('loanPurpose', loanPurposeSelect.value);
        formData.append('referralSource', referralSelect.value);
        formData.append('referralName', referralNameInput.value);
        formData.append('_token', config.csrfToken);

        fetch(config.submitUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('termsModal'))?.hide();
                document.getElementById('successOverlay').style.display = 'flex';
            } else {
                alert(data.message || 'Something went wrong. Please try again.');
                finalSubmitBtn.disabled = false;
                finalSubmitBtn.innerHTML = '<i class="feather-check me-2"></i>Accept & Submit';
            }
        })
        .catch(() => {
            alert('Something went wrong. Please check your connection and try again.');
            finalSubmitBtn.disabled = false;
            finalSubmitBtn.innerHTML = '<i class="feather-check me-2"></i>Accept & Submit';
        });
    };
})();