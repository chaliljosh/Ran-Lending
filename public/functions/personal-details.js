const PSGC_API_BASE = 'https://psgc.cloud/api';
const NCR_CODE = '130000000';

async function fetchWithFallback(url) {
    const fallbackBase = 'https://psgc.gitlab.io/api';
    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return JSON.parse(await response.text());
    } catch (error) {
        console.warn(`Primary API failed (${url}), trying fallback...`);
        const fallbackResponse = await fetch(url.replace(PSGC_API_BASE, fallbackBase));
        if (!fallbackResponse.ok) throw new Error(`Fallback also failed: HTTP ${fallbackResponse.status}`);
        return JSON.parse(await fallbackResponse.text());
    }
}

/* ============================================================
   TOAST — pamalit sa native alert() para sa error messages.
   Gumagamit ng parehong red palette/classes ng .pd-step-alert
   para consistent ang look (kasama dark mode support).
   ============================================================ */
function showErrorToast(message) {
    let toast = document.getElementById('pdErrorToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'pdErrorToast';
        toast.className = 'pd-toast';
        toast.innerHTML = `
            <i class="feather-alert-circle"></i>
            <span class="pd-toast-message"></span>
            <button type="button" class="pd-toast-close" aria-label="Close">&times;</button>
        `;
        document.body.appendChild(toast);
        toast.querySelector('.pd-toast-close').addEventListener('click', () => {
            toast.classList.remove('is-shown');
        });
    }

    toast.querySelector('.pd-toast-message').textContent = message;
    toast.classList.add('is-shown');

    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(() => {
        toast.classList.remove('is-shown');
    }, 6000);
}

function handleEmploymentStatus() {
    const status = document.getElementById('employmentStatus').value;

    const companyWrapper = document.getElementById('companyNameWrapper');
    const positionWrapper = document.getElementById('positionWrapper');
    const yearLevelWrapper = document.getElementById('yearLevelWrapper');
    const yearsWrapper = document.getElementById('yearsWrapper');
    const monthsWrapper = document.getElementById('monthsWrapper');

    const companyInput = document.getElementById('companyName');
    const positionInput = document.getElementById('position');
    const yearLevelInput = document.getElementById('yearLevel');
    const yearsInput = document.getElementById('yearsInCompany');
    const monthsInput = document.getElementById('monthsInCompany');

    const companyLabel = document.getElementById('companyNameLabel');
    const positionLabel = document.getElementById('positionLabel');

    // I-clear lagi ang values pag nagpalit ng status, para hindi ma-carry over
    // ang laman (hal. School Name papunta sa Company Name, o Course papunta sa Position).
    companyInput.value = '';
    positionInput.value = '';
    yearLevelInput.value = '';
    yearsInput.value = '';
    monthsInput.value = '0';

    if (status === 'unemployed' || status === 'retired') {
        companyWrapper.style.display = 'none';
        positionWrapper.style.display = 'none';
        yearLevelWrapper.style.display = 'none';
        yearsWrapper.style.display = 'none';
        monthsWrapper.style.display = 'none';
        companyInput.removeAttribute('required');
        positionInput.removeAttribute('required');
        yearLevelInput.removeAttribute('required');
        yearsInput.removeAttribute('required');

    } else if (status === 'student') {
        companyWrapper.style.display = '';
        positionWrapper.style.display = '';
        yearLevelWrapper.style.display = '';
        yearsWrapper.style.display = 'none';
        monthsWrapper.style.display = 'none';
        companyInput.setAttribute('required', 'required');
        positionInput.setAttribute('required', 'required');
        yearLevelInput.setAttribute('required', 'required');
        yearsInput.removeAttribute('required');
        companyInput.placeholder = 'Enter school name';
        positionInput.placeholder = 'Enter course (e.g. BSIT)';
        companyLabel.innerHTML = 'School Name <span class="text-danger">*</span>';
        positionLabel.innerHTML = 'Course <span class="text-danger">*</span>';

    } else if (status === 'employed' || status === 'self_employed') {
        const yearsLabel = document.getElementById('yearsLabel');
        const monthsLabel = document.getElementById('monthsLabel');

        companyWrapper.style.display = '';
        positionWrapper.style.display = '';
        yearLevelWrapper.style.display = 'none';
        yearsWrapper.style.display = '';
        monthsWrapper.style.display = '';
        companyInput.setAttribute('required', 'required');
        positionInput.setAttribute('required', 'required');
        yearLevelInput.removeAttribute('required');
        yearsInput.setAttribute('required', 'required');
        companyInput.placeholder = 'Enter company/business name';
        positionInput.placeholder = 'Enter position/occupation';
        companyLabel.innerHTML = 'Company/Business Name <span class="text-danger">*</span>';
        positionLabel.innerHTML = 'Position/Occupation <span class="text-danger">*</span>';
        yearsLabel.innerHTML = 'Years in Company <span class="text-danger">*</span>';
        // Months in Company ay hindi kasama sa [required] validation,
        // kaya walang asterisk.
        monthsLabel.innerHTML = 'Months in Company';

    } else {
        companyWrapper.style.display = 'none';
        positionWrapper.style.display = 'none';
        yearLevelWrapper.style.display = 'none';
        yearsWrapper.style.display = 'none';
        monthsWrapper.style.display = 'none';
        companyInput.removeAttribute('required');
        positionInput.removeAttribute('required');
        yearLevelInput.removeAttribute('required');
        yearsInput.removeAttribute('required');
    }

    checkFormCompletion();
}

/* ============================================================
   TYPE OF ID — "Others" na option, may kasamang text field na
   lalabas lang kapag pinili ito, para ma-specify ng borrower
   kung anong klaseng ID meron sila.
   ============================================================ */
function handleIdType() {
    const idType = document.getElementById('idType').value;
    const wrapper = document.getElementById('idTypeOthersWrapper');
    const input = document.getElementById('idTypeOthers');

    if (!wrapper || !input) return;

    if (idType === 'others') {
        wrapper.style.display = '';
        input.setAttribute('required', 'required');
    } else {
        wrapper.style.display = 'none';
        input.removeAttribute('required');
        input.value = '';
    }

    checkFormCompletion();
}

async function loadRegions() {
    try {
        const regions = await fetchWithFallback(`${PSGC_API_BASE}/regions/`);
        const regionSelect = document.getElementById('region');
        regions.sort((a, b) => a.name.localeCompare(b.name));
        regions.forEach(region => {
            const option = document.createElement('option');
            option.value = region.code;
            option.textContent = region.name;
            regionSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading regions:', error);
    }
}

async function loadProvinces(regionCode) {
    const provinceSelect = document.getElementById('province');
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    provinceSelect.disabled = true;
    document.getElementById('city').innerHTML = '<option value="">Select City/Municipality</option>';
    document.getElementById('city').disabled = true;
    document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
    document.getElementById('barangay').disabled = true;
    document.getElementById('zipcode').value = '';
    document.getElementById('zipcode').removeAttribute('readonly');

    if (regionCode === NCR_CODE) {
        provinceSelect.innerHTML = '<option value="NCR">N/A (NCR)</option>';
        provinceSelect.value = 'NCR';
        provinceSelect.disabled = true;
        provinceSelect.removeAttribute('required');
        await loadCitiesByRegion(NCR_CODE);
        return;
    }

    provinceSelect.setAttribute('required', 'required');
    try {
        const provinces = await fetchWithFallback(`${PSGC_API_BASE}/regions/${regionCode}/provinces/`);
        provinceSelect.innerHTML = '<option value="">Select Province</option>';
        if (provinces.length === 0) { await loadCitiesByRegion(regionCode); return; }
        provinces.sort((a, b) => a.name.localeCompare(b.name));
        provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.code;
            option.textContent = province.name;
            provinceSelect.appendChild(option);
        });
        provinceSelect.disabled = false;
    } catch (error) {
        console.error('Error loading provinces:', error);
    }
}

async function loadCitiesByRegion(regionCode) {
    try {
        const cities = await fetchWithFallback(`${PSGC_API_BASE}/regions/${regionCode}/cities-municipalities/`);
        const citySelect = document.getElementById('city');
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        cities.sort((a, b) => a.name.localeCompare(b.name));
        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.code;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
        citySelect.disabled = false;
    } catch (error) {
        console.error('Error loading cities for region:', error);
    }
}

async function loadCities(provinceCode) {
    document.getElementById('city').innerHTML = '<option value="">Select City/Municipality</option>';
    document.getElementById('city').disabled = true;
    document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
    document.getElementById('barangay').disabled = true;
    document.getElementById('zipcode').value = '';
    document.getElementById('zipcode').removeAttribute('readonly');
    try {
        const cities = await fetchWithFallback(`${PSGC_API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
        const citySelect = document.getElementById('city');
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        cities.sort((a, b) => a.name.localeCompare(b.name));
        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.code;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
        citySelect.disabled = false;
    } catch (error) {
        console.error('Error loading cities:', error);
    }
}

async function loadBarangays(cityCode) {
    document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
    document.getElementById('barangay').disabled = true;
    try {
        const barangays = await fetchWithFallback(`${PSGC_API_BASE}/cities-municipalities/${cityCode}/barangays/`);
        const barangaySelect = document.getElementById('barangay');
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangays.sort((a, b) => a.name.localeCompare(b.name));
        barangays.forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay.code;
            option.textContent = barangay.name;
            barangaySelect.appendChild(option);
        });
        barangaySelect.disabled = false;
    } catch (error) {
        console.error('Error loading barangays:', error);
    }
}

function setMaxBirthday() {
    document.getElementById('birthday').setAttribute('max', new Date().toISOString().split('T')[0]);
}

function calculateAge() {
    const birthday = document.getElementById('birthday').value;
    if (birthday) {
        const birthDate = new Date(birthday);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
        document.getElementById('age').value = age;
    } else {
        document.getElementById('age').value = '';
    }
}

function setupPhoneInput(id) {
    const input = document.getElementById(id);
    if (!input) return;

    const PREFIX = '+63 ';

    input.addEventListener('focus', function () {
        if (!this.value.startsWith(PREFIX)) this.value = PREFIX;
    });

    input.addEventListener('keydown', function (e) {
        const allowed = ['Tab', 'Escape', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Backspace', 'Delete'];
        if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
        if (!/^[0-9]$/.test(e.key)) { e.preventDefault(); return; }
        const digits = this.value.slice(PREFIX.length).replace(/[^0-9]/g, '');
        if (digits.length >= 10) e.preventDefault();
    });

    input.addEventListener('input', function () {
        if (!this.value.startsWith(PREFIX)) this.value = PREFIX;
        let digits = this.value.slice(PREFIX.length).replace(/[^0-9]/g, '');
        if (digits.length > 10) digits = digits.slice(0, 10);
        this.value = PREFIX + digits;
        checkFormCompletion();
    });

    input.addEventListener('blur', function () {
        input.setCustomValidity('');
        checkFormCompletion();
    });
}

function setupFilePreview(fileInputId, previewId) {
    const fileInput = document.getElementById(fileInputId);
    const preview = document.getElementById(previewId);
    fileInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) { preview.innerHTML = ''; return; }
        if (file.size > 5 * 1024 * 1024) {
            showErrorToast('File size must be less than 5MB');
            clearFileInput(fileInputId, previewId);
            return;
        }
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = `<div class="border rounded p-2 d-inline-block">
                    <img src="${e.target.result}" alt="ID Preview" style="max-width:200px;max-height:200px;display:block;">
                    <p class="mb-0 mt-2 text-success"><i class="feather-check-circle me-1"></i>${file.name}</p>
                    <button type="button" class="btn btn-sm btn-danger mt-2" onclick="clearFileInput('${fileInputId}','${previewId}')">
                        <i class="feather-x me-1"></i>Remove
                    </button></div>`;
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            preview.innerHTML = `<div class="border rounded p-2 d-inline-block">
                <i class="feather-file text-danger" style="font-size:48px;display:block;"></i>
                <p class="mb-0 mt-2 text-success"><i class="feather-check-circle me-1"></i>${file.name}</p>
                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="clearFileInput('${fileInputId}','${previewId}')">
                    <i class="feather-x me-1"></i>Remove
                </button></div>`;
        }
        checkFormCompletion();
    });
}

function clearFileInput(fileInputId, previewId) {
    document.getElementById(fileInputId).value = '';
    document.getElementById(previewId).innerHTML = '';
    checkFormCompletion();
}

function checkFormCompletion() {
    const form = document.getElementById('personalDetailsForm');
    const submitBtn = document.getElementById('submitBtn');
    if (!form || !submitBtn) return;
    let allFilled = true;
    form.querySelectorAll('[required]').forEach(input => {
        if (input.type === 'file') {
            if (!input.files || input.files.length === 0) allFilled = false;
        } else if (input.tagName === 'SELECT') {
            if (!input.value) allFilled = false;
        } else {
            if (!input.value.trim()) allFilled = false;
        }
    });
    submitBtn.disabled = !allFilled;
}

/* ============================================================
   PREFILL — para sa edit / resubmit (on hold o rejected).
   ------------------------------------------------------------
   Ang region/province/city/barangay ay naka-save bilang PANGALAN
   (text ng option), hindi code. Kaya pangalan ang ima-match natin
   sa options na galing sa PSGC, isa-isa bawat level.
   Ang data ay galing sa window.pdSaved (inilagay ng Blade).
   ============================================================ */
function selectByText(select, text) {
    if (!text) return false;
    const target = String(text).trim().toLowerCase();
    for (const opt of select.options) {
        if (opt.textContent.trim().toLowerCase() === target) {
            select.value = opt.value;
            return true;
        }
    }
    return false;
}

async function prefillSaved() {
    const s = window.pdSaved;
    if (!s) return;

    // ---- Work info (una, dahil nire-reset ng handleEmploymentStatus ang values) ----
    if (s.employmentStatus) {
        document.getElementById('employmentStatus').value = s.employmentStatus;
        handleEmploymentStatus();

        const status = s.employmentStatus;
        if (status === 'employed' || status === 'self_employed' || status === 'student') {
            document.getElementById('companyName').value = s.companyName || '';

            let position = s.position || '';
            if (status === 'student') {
                // Naka-save bilang "Course - Year Level"
                const idx = position.lastIndexOf(' - ');
                if (idx > -1) {
                    const level = position.slice(idx + 3);
                    const levelSel = document.getElementById('yearLevel');
                    if ([...levelSel.options].some(o => o.value === level)) {
                        levelSel.value = level;
                        position = position.slice(0, idx);
                    }
                }
            } else {
                const total = parseFloat(s.yearsInCompany) || 0;
                let years = Math.floor(total);
                let months = Math.round((total - years) * 12);
                if (months === 12) { years += 1; months = 0; }
                document.getElementById('yearsInCompany').value = years;
                document.getElementById('monthsInCompany').value = String(months);
            }
            document.getElementById('position').value = position;
        }
    }

    // ---- Address cascade: region -> province -> city -> barangay ----
    const regionSel   = document.getElementById('region');
    const provinceSel = document.getElementById('province');
    const citySel     = document.getElementById('city');
    const barangaySel = document.getElementById('barangay');

    if (s.region && selectByText(regionSel, s.region)) {
        await loadProvinces(regionSel.value);

        // NCR at mga region na walang province ay kusa nang nag-load ng cities
        if (regionSel.value !== NCR_CODE && !provinceSel.disabled) {
            if (s.province && selectByText(provinceSel, s.province)) {
                await loadCities(provinceSel.value);
            }
        }

        if (s.city && selectByText(citySel, s.city)) {
            await loadBarangays(citySel.value);
            if (s.barangay) selectByText(barangaySel, s.barangay);
        }
    }

    // Zipcode — huling i-set dahil binubura ito ng loadProvinces/loadCities
    if (s.zipcode) document.getElementById('zipcode').value = s.zipcode;

    checkFormCompletion();
}

/* ============================================================
   WIZARD — hakbang-hakbang na navigation
   ------------------------------------------------------------
   Hindi ito bagong form. Nasa iisang <form> pa rin lahat ng
   fields — nakatago lang (display:none) ang mga hindi active
   na step. Kaya pareho pa rin ang FormData na ipinapadala sa
   dulo, at walang kailangang baguhin sa backend.
   ============================================================ */
const PDWizard = (function () {
    let steps = [];
    let progressSteps = [];
    let connectors = [];
    let current = 0;

    function isVisible(el) {
        // Ginagamit para laktawan ang mga field na itinago ng
        // handleEmploymentStatus() (hal. Year Level kapag Employed).
        return el.offsetParent !== null;
    }

    function clearErrors(step) {
        step.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        const alertBox = step.querySelector('.pd-step-alert');
        if (alertBox) alertBox.classList.remove('is-shown');
    }

    function validateStep(step) {
        clearErrors(step);

        let firstBad = null;

        step.querySelectorAll('[required]').forEach(field => {
            if (field.disabled || !isVisible(field)) return;

            let empty;
            if (field.type === 'file') {
                empty = !field.files || field.files.length === 0;
            } else {
                empty = !String(field.value).trim();
            }

            if (empty) {
                field.classList.add('is-invalid');
                if (!firstBad) firstBad = field;
            }
        });

        // Phone number check — 10 digits, dapat nagsisimula sa 9
        step.querySelectorAll('input[type="tel"]').forEach(input => {
            if (input.disabled || !isVisible(input)) return;
            const digits = input.value.slice('+63 '.length).replace(/[^0-9]/g, '');
            if (digits.length !== 10 || !digits.startsWith('9')) {
                input.classList.add('is-invalid');
                if (!firstBad) firstBad = input;
            }
        });

        if (firstBad) {
            const alertBox = step.querySelector('.pd-step-alert');
            if (alertBox) {
                alertBox.innerHTML =
                    '<i class="feather-alert-circle me-2"></i>Some fields in this section are missing or invalid. Please complete them before continuing.';
                alertBox.classList.add('is-shown');
            }
            firstBad.focus();
            return false;
        }

        return true;
    }

    function render() {
        steps.forEach((step, i) => step.classList.toggle('is-active', i === current));

        progressSteps.forEach((item, i) => {
            item.classList.toggle('active', i === current);
            item.classList.toggle('done', i < current);
        });

        connectors.forEach((c, i) => c.classList.toggle('done', i < current));

        const isFirst = current === 0;
        const isLast = current === steps.length - 1;

        const btnBack = document.getElementById('pdBtnBack');
        const btnNext = document.getElementById('pdBtnNext');
        const btnSubmit = document.getElementById('submitBtn');

        if (btnBack) btnBack.style.visibility = isFirst ? 'hidden' : 'visible';
        if (btnNext) btnNext.style.display = isLast ? 'none' : '';
        if (btnSubmit) btnSubmit.style.display = isLast ? '' : 'none';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function goTo(index) {
        current = Math.max(0, Math.min(index, steps.length - 1));
        render();
    }

    // Tinatawag bago mag-submit — kung may step na kulang,
    // ibinabalik ang user doon.
    function validateAll() {
        for (let i = 0; i < steps.length; i++) {
            if (!validateStep(steps[i])) {
                goTo(i);
                return false;
            }
        }
        return true;
    }

    function init() {
        const form = document.getElementById('personalDetailsForm');
        if (!form) return;

        steps = Array.from(form.querySelectorAll('.pd-step'));
        if (steps.length === 0) return;

        progressSteps = Array.from(document.querySelectorAll('#pdProgress .progress-step'));
        connectors = Array.from(document.querySelectorAll('#pdProgress .progress-connector'));

        const btnNext = document.getElementById('pdBtnNext');
        const btnBack = document.getElementById('pdBtnBack');

        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (!validateStep(steps[current])) return;
                goTo(current + 1);
            });
        }

        if (btnBack) {
            btnBack.addEventListener('click', function () {
                goTo(current - 1);
            });
        }

        // Alisin ang pulang highlight pag nagsimula nang mag-ayos
        form.addEventListener('input', function (e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
            }
        });
        form.addEventListener('change', function (e) {
            if (e.target.classList && e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
            }
        });

        render();
    }

    return { init, validateAll };
})();

document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('companyNameWrapper').style.display = 'none';
    document.getElementById('positionWrapper').style.display = 'none';
    document.getElementById('yearLevelWrapper').style.display = 'none';
    document.getElementById('yearsWrapper').style.display = 'none';
    document.getElementById('monthsWrapper').style.display = 'none';

    // Iniimbak ang promise para ma-prefill ang address pagkatapos
    // mag-load ng lahat ng regions.
    const regionsReady = loadRegions();
    setMaxBirthday();
    setupFilePreview('validIdFront', 'idFrontPreview');
    setupFilePreview('validIdBack', 'idBackPreview');
    setupPhoneInput('cellphone');
   

    // Kung may prefilled birthday (from Blade), kalkulahin agad ang edad
    if (document.getElementById('birthday').value) calculateAge();

    const form = document.getElementById('personalDetailsForm');
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', checkFormCompletion);
        el.addEventListener('change', checkFormCompletion);
    });

    document.getElementById('birthday').addEventListener('change', calculateAge);
    document.getElementById('employmentStatus').addEventListener('change', handleEmploymentStatus);

    const idTypeSelect = document.getElementById('idType');
    if (idTypeSelect) {
        idTypeSelect.addEventListener('change', handleIdType);
        // Kung may prefilled value na (edit/resubmit case), ipakita
        // agad ang "Specify ID Type" field kung "others" ang laman.
        if (idTypeSelect.value === 'others') handleIdType();
    }

    document.getElementById('region').addEventListener('change', function () {
        if (this.value) loadProvinces(this.value);
    });
    document.getElementById('province').addEventListener('change', function () {
        if (this.value && this.value !== 'NCR') loadCities(this.value);
    });
    document.getElementById('city').addEventListener('change', function () {
        if (this.value) loadBarangays(this.value);
    });
    document.getElementById('zipcode').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
    });

    // I-simula ang wizard — dapat pagkatapos ma-attach lahat ng
    // listeners sa itaas, para tama na agad ang unang render.
    PDWizard.init();

    // Prefill ng saved data (address cascade + work info) pagkatapos
    // ma-load ang regions. Walang epekto kung first-time na submission.
    regionsReady.then(() => prefillSaved());

    document.getElementById('submitBtn').addEventListener('click', function () {

        // Isahang pass sa LAHAT ng steps. Kapag may kulang, awtomatikong
        // babalik ang view sa step na may problema.
        if (!PDWizard.validateAll()) return;

        const btn = this;
        btn.disabled = true;
        // Loading state: spinner icon na lang, walang "Submitting..." text
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const formData = new FormData();

        formData.append('lastName',      document.getElementById('lastName').value);
        formData.append('firstName',     document.getElementById('firstName').value);
        formData.append('middleName',    document.getElementById('middleName').value);
        formData.append('suffix',        document.getElementById('suffix').value);
        formData.append('birthday',      document.getElementById('birthday').value);
        formData.append('gender',        document.getElementById('gender').value);
        formData.append('civilStatus',   document.getElementById('civilStatus').value);
        formData.append('citizenship',   document.getElementById('citizenship').value);
        formData.append('birthCountry',  document.getElementById('birthCountry').value);
        formData.append('birthProvince', document.getElementById('birthProvince').value);
        formData.append('birthCity',     document.getElementById('birthCity').value);
        formData.append('cellphone',     document.getElementById('cellphone').value);
        formData.append('idType',        document.getElementById('idType').value);
        formData.append('idTypeOthers',  document.getElementById('idTypeOthers') ? document.getElementById('idTypeOthers').value : '');
        formData.append('idNumber',      document.getElementById('idNumber').value);
        formData.append('monthlyIncome', document.getElementById('monthlyIncome').value);
        formData.append('email',         document.getElementById('email').value);

        const regionSelect   = document.getElementById('region');
        const provinceSelect = document.getElementById('province');
        const citySelect     = document.getElementById('city');
        const barangaySelect = document.getElementById('barangay');

        formData.append('region',      regionSelect.options[regionSelect.selectedIndex].text);
        formData.append('province',    provinceSelect.options[provinceSelect.selectedIndex].text);
        formData.append('city',        citySelect.options[citySelect.selectedIndex].text);
        formData.append('barangay',    barangaySelect.options[barangaySelect.selectedIndex].text);
        formData.append('zipcode',     document.getElementById('zipcode').value);
        formData.append('homeAddress', document.getElementById('homeAddress').value);

        const status = document.getElementById('employmentStatus').value;
        const years  = parseInt(document.getElementById('yearsInCompany').value) || 0;
        const months = parseInt(document.getElementById('monthsInCompany').value) || 0;
        const yearsInCompany = (status === 'unemployed' || status === 'retired' || status === 'student')
            ? 0
            : parseFloat((years + (months / 12)).toFixed(2));

        // Student: pagsamahin ang Course at Year Level papunta sa "position" column
        // (e.g. "BSIT - 4th Year").
        let positionValue = document.getElementById('position').value;
        if (status === 'student') {
            const yearLevelValue = document.getElementById('yearLevel').value;
            positionValue = yearLevelValue ? `${positionValue} - ${yearLevelValue}` : positionValue;
        }

        formData.append('companyName',      document.getElementById('companyName').value);
        formData.append('position',         positionValue);
        formData.append('employmentStatus', status);
        formData.append('yearsInCompany',   yearsInCompany);


        // ID Front / Back — dalawang hiwalay na files
        const validIdFront = document.getElementById('validIdFront').files[0];
        const validIdBack  = document.getElementById('validIdBack').files[0];
        if (validIdFront) formData.append('validIdFront', validIdFront);
        if (validIdBack)  formData.append('validIdBack', validIdBack);

        // CSRF token — gamit ang shared getCsrfToken() helper mula sa
        // csrf.js (dapat naka-load na ito bago ang script na ito).
        fetch('/api/borrower/personal-details', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'   // para JSON (422) ang ibalik ni Laravel
                                                // at hindi buong HTML page
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const overlay = document.getElementById('submissionOverlay');
                overlay.style.cssText = 'display:flex !important; opacity:1 !important; position:fixed !important; inset:0 !important; z-index:99999 !important; background:rgba(0,0,0,0.55) !important; align-items:center !important; justify-content:center !important; padding:16px !important;';
                document.body.style.overflow = 'hidden';
            } else {
                // Validation error (422) ay may { errors: { field: [msg] } } na format
                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : (data.message || 'An error occurred.');
                showErrorToast(firstError);
                btn.disabled = false;
                btn.innerHTML = '<i class="feather-send me-2"></i>Submit for Approval';
            }
        })
        .catch(() => {
            showErrorToast('Something went wrong. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="feather-send me-2"></i>Submit for Approval';
        });
    });
});