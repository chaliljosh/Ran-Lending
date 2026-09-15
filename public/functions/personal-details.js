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
        // FIX: Months in Company ay hindi kasama sa [required] validation
        // (walang `required` attribute yung <select id="monthsInCompany">), kaya
        // yung red asterisk dati ay nagpapakita ng maling impresyon na kailangan
        // itong sagutan. Optional talaga ito — tinanggal ang asterisk.
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

function setupMonthlyIncome() {
    const input = document.getElementById('monthlyIncome');
    if (!input) return;
    input.addEventListener('keydown', function (e) {
        const allowed = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
        if (allowed.includes(e.key) || e.ctrlKey || e.metaKey) return;
        if (!/^[0-9]$/.test(e.key)) { e.preventDefault(); return; }
        if (this.value.replace(/[^0-9]/g, '').length >= 6) e.preventDefault();
    });
    input.addEventListener('input', function () {
        let digits = this.value.replace(/[^0-9]/g, '');
        if (digits.length > 6) digits = digits.slice(0, 6);
        this.value = digits.replace(/^0+/, '');
        checkFormCompletion();
    });
    input.addEventListener('paste', function (e) {
        e.preventDefault();
        let digits = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        this.value = digits.replace(/^0+/, '');
        checkFormCompletion();
    });
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
            alert('File size must be less than 5MB');
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

document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('companyNameWrapper').style.display = 'none';
    document.getElementById('positionWrapper').style.display = 'none';
    document.getElementById('yearLevelWrapper').style.display = 'none';
    document.getElementById('yearsWrapper').style.display = 'none';
    document.getElementById('monthsWrapper').style.display = 'none';

    loadRegions();
    setMaxBirthday();
    setupMonthlyIncome();
    setupFilePreview('validIdFront', 'idFrontPreview');
    setupFilePreview('validIdBack', 'idBackPreview');
    setupPhoneInput('cellphone');
    setupPhoneInput('coMakerContact');

    // Kung may prefilled birthday (from Blade), kalkulahin agad ang edad
    if (document.getElementById('birthday').value) calculateAge();

    const form = document.getElementById('personalDetailsForm');
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', checkFormCompletion);
        el.addEventListener('change', checkFormCompletion);
    });

    document.getElementById('birthday').addEventListener('change', calculateAge);
    document.getElementById('employmentStatus').addEventListener('change', handleEmploymentStatus);

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

    document.getElementById('submitBtn').addEventListener('click', function () {
        const form = document.getElementById('personalDetailsForm');

        const phoneIds = ['cellphone', 'coMakerContact'];
        for (const id of phoneIds) {
            const input = document.getElementById(id);
            const digits = input.value.slice('+63 '.length).replace(/[^0-9]/g, '');
            if (digits.length !== 10) {
                alert('Please enter a complete 10-digit Philippine number (e.g. +63 9611111111).');
                input.focus();
                return;
            }
            if (!digits.startsWith('9')) {
                alert('Philippine mobile numbers must start with 9 (e.g. +63 9XXXXXXXXX).');
                input.focus();
                return;
            }
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const btn = this;
        btn.disabled = true;
        // ---- Loading state: spinner icon na lang, walang "Submitting..." text
        //      (kagaya ng ibang "black button" loading states sa app) ----
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
        // (walang bagong DB column na kailangan, e.g. "BSIT - 4th Year").
        let positionValue = document.getElementById('position').value;
        if (status === 'student') {
            const yearLevelValue = document.getElementById('yearLevel').value;
            positionValue = yearLevelValue ? `${positionValue} - ${yearLevelValue}` : positionValue;
        }

        formData.append('companyName',      document.getElementById('companyName').value);
        formData.append('position',         positionValue);
        formData.append('employmentStatus', status);
        formData.append('yearsInCompany',   yearsInCompany);

        formData.append('coMakerLastName',     document.getElementById('coMakerLastName').value);
        formData.append('coMakerFirstName',    document.getElementById('coMakerFirstName').value);
        formData.append('coMakerMiddleName',   document.getElementById('coMakerMiddleName').value);
        formData.append('coMakerRelationship', document.getElementById('coMakerRelationship').value);
        formData.append('coMakerContact',      document.getElementById('coMakerContact').value);

        // ID Front / Back — dalawang hiwalay na files na
        const validIdFront = document.getElementById('validIdFront').files[0];
        const validIdBack  = document.getElementById('validIdBack').files[0];
        if (validIdFront) formData.append('validIdFront', validIdFront);
        if (validIdBack)  formData.append('validIdBack', validIdBack);

        // CSRF token — gamit ang shared getCsrfToken() helper mula sa
        // csrf.js (dapat naka-load na ito bago ang script na ito).
        fetch('/api/borrower/personal-details', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-XSRF-TOKEN': getCsrfToken() },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const overlay = document.getElementById('submissionOverlay');
                overlay.style.cssText = 'display:flex !important; opacity:1 !important; position:fixed !important; inset:0 !important; z-index:99999 !important; background:rgba(0,0,0,0.55) !important; align-items:center !important; justify-content:center !important; padding:16px !important;';
                document.body.style.overflow = 'hidden';
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="feather-send me-2"></i>Submit for Approval';
            }
        })
        .catch(() => {
            alert('Something went wrong. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="feather-send me-2"></i>Submit for Approval';
        });
    });
});