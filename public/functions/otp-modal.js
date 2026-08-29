
const OtpModal = (function () {
    const RESEND_SECONDS = 120;

    let overlay, inputsWrap, inputs, errorEl, verifyBtn, resendWrap, countdownEl, backBtn, maskedEmailEl, trustCheckbox;
    let countdownTimer = null;
    let currentOnVerify = null;
    let currentOnResend = null;
    let currentOnSuccess = null;

    function init() {
        overlay       = document.getElementById('otpOverlay');
        inputsWrap    = document.getElementById('otpInputs');
        inputs        = Array.from(inputsWrap.querySelectorAll('input'));
        errorEl       = document.getElementById('otpError');
        verifyBtn     = document.getElementById('otpVerifyBtn');
        resendWrap    = document.getElementById('otpResendWrap');
        countdownEl   = document.getElementById('otpCountdown');
        backBtn       = document.getElementById('otpBackBtn');
        maskedEmailEl = document.getElementById('otpMaskedEmail');
        trustCheckbox = document.getElementById('otpTrustDevice');

        inputs.forEach((input, i) => {
            input.addEventListener('input', () => handleInput(i));
            input.addEventListener('keydown', (e) => handleKeydown(e, i));
            input.addEventListener('paste', (e) => handlePaste(e));
        });

        verifyBtn.addEventListener('click', handleVerifyClick);
        backBtn.addEventListener('click', close);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close();
        });
    }

    function handleInput(i) {
        const input = inputs[i];
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);

        if (input.value) {
            input.classList.add('is-filled');
            if (i < inputs.length - 1) inputs[i + 1].focus();
        } else {
            input.classList.remove('is-filled');
        }

        clearError();
    }

    function handleKeydown(e, i) {
        if (e.key === 'Backspace' && !inputs[i].value && i > 0) {
            inputs[i - 1].focus();
        }
    }

    function handlePaste(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        if (!pasted) return;

        pasted.slice(0, 6).split('').forEach((digit, i) => {
            if (inputs[i]) {
                inputs[i].value = digit;
                inputs[i].classList.add('is-filled');
            }
        });

        const nextEmpty = inputs.findIndex(inp => !inp.value);
        (nextEmpty === -1 ? inputs[inputs.length - 1] : inputs[nextEmpty]).focus();
        clearError();
    }

    function getCode() {
        return inputs.map(inp => inp.value).join('');
    }

    function clearError() {
        errorEl.textContent = '';
    }

    function showError(message) {
        errorEl.textContent = message;
    }

    function setLoading(isLoading) {
        verifyBtn.disabled = isLoading;
        verifyBtn.classList.toggle('is-loading', isLoading);
    }

    function handleVerifyClick() {
        const code = getCode();
        const trustDevice = trustCheckbox ? trustCheckbox.checked : false;

        if (code.length !== 6) {
            showError('Please enter the full 6-digit code.');
            return;
        }

        clearError();
        setLoading(true);

        Promise.resolve(currentOnVerify(code, trustDevice))
            .then((data) => {
                setLoading(false);
                if (currentOnSuccess) currentOnSuccess(data);
            })
            .catch((err) => {
                setLoading(false);
                showError(err.message || 'Invalid or expired code. Please try again.');
                inputs.forEach(inp => { inp.value = ''; inp.classList.remove('is-filled'); });
                inputs[0].focus();
            });
    }

    function startCountdown() {
        let remaining = RESEND_SECONDS;
        resendWrap.innerHTML = 'Resend code in <span id="otpCountdown"></span>';
        countdownEl = document.getElementById('otpCountdown');
        updateCountdownDisplay(remaining);

        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                showResendLink();
            } else {
                updateCountdownDisplay(remaining);
            }
        }, 1000);
    }

    function updateCountdownDisplay(seconds) {
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        if (countdownEl) countdownEl.textContent = `${m}:${s}`;
    }

    function showResendLink() {
        resendWrap.innerHTML = `
            Didn't receive the code?
            <button type="button" class="otp-resend-link" id="otpResendBtn">Resend Code</button>
        `;
        document.getElementById('otpResendBtn').addEventListener('click', handleResendClick);
    }

    function handleResendClick() {
        const btn = document.getElementById('otpResendBtn');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        Promise.resolve(currentOnResend())
            .then(() => {
                startCountdown();
            })
            .catch((err) => {
                showError(err.message || 'Could not resend code. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Resend Code';
            });
    }

    function open(options) {
        if (!overlay) init();

        currentOnVerify  = options.onVerify;
        currentOnResend  = options.onResend;
        currentOnSuccess = options.onSuccess;

        maskedEmailEl.textContent = options.maskedEmail || '';
        inputs.forEach(inp => { inp.value = ''; inp.classList.remove('is-filled'); });
        if (trustCheckbox) trustCheckbox.checked = false;
        clearError();
        setLoading(false);

        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        startCountdown();
        setTimeout(() => inputs[0].focus(), 50);
    }

    function close() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        clearInterval(countdownTimer);
    }

    return { open, close };
})();