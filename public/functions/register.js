const PSGC_API_BASE = 'https://psgc.cloud/api';
const COUNTRIES_API = 'https://restcountries.com/v3.1/all?fields=name,demonyms';
const ZIP_API = '/api/zipcode';
const ZIP_PLACEHOLDER = 'e.g. 2000';
const NCR_CODE = '130000000';
const PH_NAME = 'Philippines';
const PH_DEMONYM = 'Filipino';
const MIN_AGE = 18;
const NEXT_MIN_LOADING_MS = 400;

/* Debug para sa zipcode. Lalabas ang pulang banner sa taas ng form. Gawing false kapag gumana na. */
const ZIP_DEBUG = false;

/* Hindi na lalabas sa listahan ang placeholder (Day, Year, Select...). Suffix lang ang may "None" na pwedeng piliin. */
const KEEP_EMPTY_OPTION = ['suffix'];

/* Hanggang ganito karaming options, ipapakita lahat nang walang scroll. Mas marami rito: 220px max at may scroll. */
const SHORT_LIST_MAX = 8;

/* Search box: sa mahahabang listahan lang. Tanggalin sa array kung ayaw mo sa isa. */
const SEARCHABLE_SELECTS = [];

const RULES = {
  length: pw => pw.length >= 8,
  upper:  pw => /[A-Z]/.test(pw),
  number: pw => /[0-9]/.test(pw),
};

const RULE_LABELS = {
  length: 'at least 8 characters',
  upper:  'at least 1 uppercase letter',
  number: 'at least 1 number',
};

const FIELD_STEP = {
  firstName: 0, middleName: 0, lastName: 0, email: 0, password: 0, confirmPassword: 0,
  birthMonth: 0, birthDay: 0, birthYear: 0, birthday: 0,
  suffix: 1, gender: 1, civilStatus: 1, citizenship: 1, cellphone: 1,
  birthCountry: 1, birthProvince: 1, birthCity: 1, monthlyIncome: 1, monthlyIncomeOther: 1,
  region: 2, province: 2, city: 2, barangay: 2, zipcode: 2, homeAddress: 2, agreeTerms: 2,
  houseNo: 2, street: 2, subdivision: 2, addressDetails: 2,
};

const FALLBACK_NATIONALITIES = [
  'American', 'Argentine', 'Australian', 'Bangladeshi', 'Belgian', 'Brazilian', 'British',
  'Bruneian', 'Burmese', 'Cambodian', 'Canadian', 'Chilean', 'Chinese', 'Colombian', 'Cuban',
  'Danish', 'Dutch', 'Egyptian', 'Emirati', 'Finnish', 'French', 'German', 'Greek',
  'Indian', 'Indonesian', 'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian', 'Japanese',
  'Jordanian', 'Kenyan', 'Korean', 'Kuwaiti', 'Laotian', 'Lebanese', 'Malaysian', 'Mexican',
  'Nepalese', 'New Zealander', 'Nigerian', 'Norwegian', 'Pakistani', 'Peruvian', 'Polish',
  'Portuguese', 'Qatari', 'Russian', 'Saudi', 'Singaporean', 'South African', 'Spanish',
  'Sri Lankan', 'Swedish', 'Swiss', 'Taiwanese', 'Thai', 'Turkish', 'Ukrainian', 'Vietnamese',
];

const BIRTH_IDS = ['birthMonth', 'birthDay', 'birthYear'];
const TEXT_SWAP = ['birthProvince', 'birthCity'];

let pendingRegisterEmail = '';
let lastInvalid = null;
let lastAvailableEmail = '';
let phProvincesCache = null;
let zipCache = {};

function validatePassword(pw) {
  return Object.entries(RULES)
    .filter(([key, test]) => !test(pw))
    .map(([key]) => RULE_LABELS[key]);
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isAtLeastMinAge(year, month, day) {
  const today  = new Date();
  const cutoff = new Date(today.getFullYear() - MIN_AGE, today.getMonth(), today.getDate());
  const birth  = new Date(year, month - 1, day);
  return birth <= cutoff;
}

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function val(id) {
  return document.getElementById(id).value.trim();
}

function togglePw(id, btn) {
  const inp  = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.classList.replace('bi-eye-slash', 'bi-eye');
  } else {
    inp.type = 'password';
    icon.classList.replace('bi-eye', 'bi-eye-slash');
  }
}

/* Banner: general errors lang (network, server, address list). */
function showError(msg) {
  const alert = document.getElementById('registerAlert');
  const text  = document.getElementById('registerAlertText');
  text.textContent = msg;
  alert.classList.add('is-visible');
  clearTimeout(alert._hideTimer);
  alert._hideTimer = setTimeout(() => alert.classList.remove('is-visible'), 6000);
}

function hideError() {
  document.getElementById('registerAlert').classList.remove('is-visible');
}

function zipDebug(msg) {
  if (ZIP_DEBUG) showError('DEBUG: ' + msg);
}

function showSuccessModal() {
  document.getElementById('successModal').classList.add('show');
}

/* ---------- Custom dropdown (laging pababa ang bukas, may search kapag mahaba) ---------- */

function closeAllDropdowns(except) {
  document.querySelectorAll('.dd.is-open').forEach(dd => {
    if (dd !== except && dd._close) dd._close();
  });
}

function enhanceSelect(sel) {
  if (sel._dd) return;

  const wrap = document.createElement('div');
  wrap.className = 'dd';
  wrap.innerHTML =
    '<button type="button" class="dd-trigger"><span class="dd-label"></span></button>' +
    '<div class="dd-panel">' +
      '<input type="text" class="dd-search" placeholder="Search..." autocomplete="off">' +
      '<ul class="dd-list" role="listbox"></ul>' +
    '</div>';

  sel.insertAdjacentElement('afterend', wrap);
  sel.classList.add('dd-native');
  sel._dd = wrap;

  const trigger = wrap.querySelector('.dd-trigger');
  const label   = wrap.querySelector('.dd-label');
  const search  = wrap.querySelector('.dd-search');
  const list    = wrap.querySelector('.dd-list');
  const panel   = wrap.querySelector('.dd-panel');

  sel._ddTrigger = trigger;

  let items = [];
  let activeIdx = -1;

  function sync() {
    const opt = sel.options[sel.selectedIndex];
    label.textContent = opt ? opt.text : '';
    trigger.classList.toggle('is-placeholder', !sel.value);
    trigger.disabled = sel.disabled;
    wrap.classList.toggle('is-disabled', sel.disabled);
    wrap.classList.toggle('is-off', sel.classList.contains('is-off'));
    trigger.classList.toggle('is-invalid', sel.classList.contains('is-invalid'));
  }

  function renderList(filter) {
    const q = (filter || '').toLowerCase();
    list.innerHTML = '';
    items = [];

    Array.from(sel.options).forEach(opt => {
      if (!opt.value && !KEEP_EMPTY_OPTION.includes(sel.id)) return;
      if (q && !opt.text.toLowerCase().includes(q)) return;
      const li = document.createElement('li');
      li.className = 'dd-item' + (opt.value === sel.value ? ' is-selected' : '') + (!opt.value ? ' is-placeholder' : '');
      li.textContent = opt.text;
      li.dataset.value = opt.value;
      li.addEventListener('mousedown', e => {
        e.preventDefault();
        choose(opt.value);
      });
      list.appendChild(li);
      items.push(li);
    });

    if (!items.length) {
      const li = document.createElement('li');
      li.className = 'dd-empty';
      li.textContent = 'No results found';
      list.appendChild(li);
    }
  }

  function setActive(i) {
    if (!items.length) { activeIdx = -1; return; }
    activeIdx = Math.max(0, Math.min(i, items.length - 1));
    items.forEach((li, idx) => li.classList.toggle('is-active', idx === activeIdx));
    items[activeIdx].scrollIntoView({ block: 'nearest' });
  }

  function choose(value) {
    sel.value = value;
    sel.dispatchEvent(new Event('change', { bubbles: true }));
    sync();
    close();
    trigger.focus();
  }

  function open() {
    if (sel.disabled || wrap.classList.contains('is-open')) return;
    closeAllDropdowns(wrap);

    search.value = '';
    renderList('');

    // Maikling listahan: ipakita lahat, walang scroll. Mahaba: 220px max (galing sa CSS) at may scroll.
    list.style.maxHeight = items.length <= SHORT_LIST_MAX ? 'none' : '';

    const useSearch = SEARCHABLE_SELECTS.includes(sel.id);
    search.style.display = useSearch ? '' : 'none';

    // Buksan muna para masukat ang tunay na taas ng panel, tapos saka pipiliin kung pababa o pataas.
    wrap.classList.remove('drop-up');
    wrap.classList.add('is-open');

    const rect  = trigger.getBoundingClientRect();
    const below = window.innerHeight - rect.bottom;
    const above = rect.top;
    const need  = panel.offsetHeight + 8;
    wrap.classList.toggle('drop-up', below < need && above > below);

    const selected = items.findIndex(li => li.classList.contains('is-selected'));
    setActive(selected >= 0 ? selected : 0);

    if (useSearch) search.focus();
  }

  function close() {
    wrap.classList.remove('is-open', 'drop-up');
  }

  wrap._close = close;

  trigger.addEventListener('click', () => {
    if (wrap.classList.contains('is-open')) close(); else open();
  });

  search.addEventListener('input', () => {
    renderList(search.value);
    setActive(0);
  });

  wrap.addEventListener('keydown', e => {
    const isOpen = wrap.classList.contains('is-open');

    if (e.key === 'Escape') {
      if (isOpen) { e.preventDefault(); close(); trigger.focus(); }
      return;
    }
    if (e.key === 'Tab') { close(); return; }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!isOpen) open(); else setActive(activeIdx + 1);
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (!isOpen) open(); else setActive(activeIdx - 1);
      return;
    }
    if (e.key === 'Enter' || (e.key === ' ' && e.target === trigger)) {
      e.preventDefault();
      if (!isOpen) open();
      else if (items[activeIdx]) choose(items[activeIdx].dataset.value);
      return;
    }

    if (e.target === trigger && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
      if (!isOpen) open();
      const k = e.key.toLowerCase();
      const idx = items.findIndex(li => li.textContent.toLowerCase().startsWith(k));
      if (idx >= 0) setActive(idx);
    }
  });

  new MutationObserver(sync).observe(sel, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['disabled', 'class']
  });

  sync();
}

function enhanceAllSelects() {
  // Sa touch devices, mas maganda ang native picker.
  if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) return;
  document.querySelectorAll('#regForm select').forEach(enhanceSelect);

  document.addEventListener('mousedown', e => {
    document.querySelectorAll('.dd.is-open').forEach(dd => {
      if (!dd.contains(e.target) && dd._close) dd._close();
    });
  });
}

/* ---------- Inline field errors ---------- */

/* Kapag foreign ang birth country, text input ang ginagamit sa province/city. */
function activeId(id) {
  if (TEXT_SWAP.includes(id)) {
    const sel = document.getElementById(id);
    if (sel && sel.classList.contains('is-off')) return id + 'Text';
  }
  return id;
}

function errorKey(id) {
  if (BIRTH_IDS.includes(id)) return 'birthday';
  if (id.endsWith('Text')) return id.slice(0, -4);
  return id;
}

function errorAnchor(id) {
  if (errorKey(id) === 'birthday') return document.querySelector('.birthday-row');
  if (id === 'agreeTerms') return document.querySelector('.consent-check');
  const el = document.getElementById(id);
  return el ? (el.closest('.input-wrap') || el) : null;
}

function birthdayHint() {
  return document.querySelector('.birthday-row').parentNode.querySelector('.field-hint');
}

function clearInvalid() {
  document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('.field-error').forEach(el => el.remove());
  const hint = birthdayHint();
  if (hint) hint.classList.remove('is-hidden');
  lastInvalid = null;
}

function markInvalid(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.add('is-invalid');
  if (!lastInvalid) lastInvalid = el;
}

function showFieldError(id, msg) {
  const realId = activeId(id);
  const key    = errorKey(realId);
  const anchor = errorAnchor(realId);
  if (!anchor) return false;

  if (key === 'birthday') {
    BIRTH_IDS.forEach(markInvalid);
    const hint = birthdayHint();
    if (hint) hint.classList.add('is-hidden');
  } else {
    markInvalid(realId);
  }

  let box = document.querySelector('.field-error[data-for="' + key + '"]');
  if (!box) {
    box = document.createElement('p');
    box.className = 'field-error';
    box.dataset.for = key;
    anchor.insertAdjacentElement('afterend', box);
  }
  box.textContent = msg;
  return true;
}

function clearFieldError(id) {
  const key = errorKey(id);
  document.querySelectorAll('.field-error[data-for="' + key + '"]').forEach(el => el.remove());
  if (key === 'birthday') {
    BIRTH_IDS.forEach(b => document.getElementById(b).classList.remove('is-invalid'));
    const hint = birthdayHint();
    if (hint) hint.classList.remove('is-hidden');
  }
}

async function checkEmailAvailable() {
  const email = val('email');
  if (email === lastAvailableEmail) return true;

  const formData = new FormData();
  formData.append('email', email);

  try {
    const res  = await fetch('/api/register/check-email', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      lastAvailableEmail = email;
      return true;
    }

    showFieldError('email', data.message || 'Email is already registered.');
    focusInvalid();
    return false;
  } catch (error) {
    showError('Unable to reach the server. Please check your connection and try again.');
    return false;
  }
}

function focusInvalid() {
  if (!lastInvalid) return;
  (lastInvalid._ddTrigger || lastInvalid).focus();
}

function fail(id, msg) {
  showFieldError(id, msg);
  return false;
}

function checkRequired(ids) {
  let missing = false;
  ids.forEach(id => {
    const el = document.getElementById(activeId(id));
    if (!el) return;
    if (!String(el.value).trim()) {
      const msg = BIRTH_IDS.includes(id)
        ? 'Please select your complete birthday.'
        : 'This field is required.';
      showFieldError(id, msg);
      missing = true;
    }
  });
  return !missing;
}

function evaluatePasswordStrength(pw) {
  const hasUpper  = /[A-Z]/.test(pw);
  const hasNumber = /[0-9]/.test(pw);
  const hasLength = pw.length >= 8;
  const metCount  = [hasUpper, hasNumber, hasLength].filter(Boolean).length;
  return { hasUpper, hasNumber, hasLength, metCount };
}

function setReqState(id, met) {
  const el = document.getElementById(id);
  if (!el) return;
  const icon = el.querySelector('i');
  if (met) {
    el.classList.add('is-met');
    icon.className = 'bi bi-check-circle';
  } else {
    el.classList.remove('is-met');
    icon.className = 'bi bi-x-circle';
  }
}

function updatePasswordStrengthUI() {
  const passwordInput = document.getElementById('password');
  const strengthWrap  = document.getElementById('pwStrength');
  if (!passwordInput || !strengthWrap) return;

  const pw     = passwordInput.value;
  const result = evaluatePasswordStrength(pw);

  setReqState('pwReqUpper', result.hasUpper);
  setReqState('pwReqNumber', result.hasNumber);
  setReqState('pwReqLength', result.hasLength);

  const segs  = strengthWrap.querySelectorAll('.pw-strength-seg');
  const label = document.getElementById('pwStrengthLabel');

  segs.forEach(seg => { seg.className = 'pw-strength-seg'; });

  if (pw.length === 0) {
    if (label) label.textContent = 'Password strength. Must contain;';
    return;
  }

  const level = result.metCount;
  const levelClass = level <= 1 ? 'is-weak' : (level === 2 ? 'is-medium' : 'is-strong');
  const levelLabel = level <= 1 ? 'Weak password. Must contain;' : (level === 2 ? 'Medium password. Must contain;' : 'Strong password.');

  for (let i = 0; i < level; i++) {
    segs[i].classList.add(levelClass);
  }
  if (label) label.textContent = levelLabel;
}

function updateAge() {
  const month = parseInt(document.getElementById('birthMonth').value, 10);
  const day   = parseInt(document.getElementById('birthDay').value, 10);
  const year  = parseInt(document.getElementById('birthYear').value, 10);
  const ageInput = document.getElementById('age');

  if (!month || !day || !year) {
    ageInput.value = '';
    return;
  }

  const today = new Date();
  let age = today.getFullYear() - year;
  const diff = today.getMonth() + 1 - month;
  if (diff < 0 || (diff === 0 && today.getDate() < day)) age--;
  ageInput.value = age;
}

function populateBirthdaySelects() {
  const yearSelect  = document.getElementById('birthYear');
  const monthSelect = document.getElementById('birthMonth');
  const daySelect   = document.getElementById('birthDay');
  if (!yearSelect || !monthSelect || !daySelect) return;

  const currentYear = new Date().getFullYear();

  for (let y = currentYear; y >= currentYear - 100; y--) {
    const opt = document.createElement('option');
    opt.value = y;
    opt.textContent = y;
    yearSelect.appendChild(opt);
  }

  function refreshDays() {
    const month = parseInt(monthSelect.value, 10);
    const year  = parseInt(yearSelect.value, 10) || currentYear;
    const daysInMonth = month ? new Date(year, month, 0).getDate() : 31;
    const prevValue = daySelect.value;

    daySelect.innerHTML = '<option value="">Day</option>';
    for (let d = 1; d <= daysInMonth; d++) {
      const opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      daySelect.appendChild(opt);
    }

    if (prevValue && parseInt(prevValue, 10) <= daysInMonth) {
      daySelect.value = prevValue;
    }
  }

  monthSelect.addEventListener('change', () => { refreshDays(); updateAge(); });
  yearSelect.addEventListener('change', () => { refreshDays(); updateAge(); });
  daySelect.addEventListener('change', updateAge);
  refreshDays();
}

async function fetchWithFallback(url) {
  const fallbackBase = 'https://psgc.gitlab.io/api';
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error('HTTP ' + response.status);
    return JSON.parse(await response.text());
  } catch (error) {
    const fallbackResponse = await fetch(url.replace(PSGC_API_BASE, fallbackBase));
    if (!fallbackResponse.ok) throw new Error('Fallback also failed: HTTP ' + fallbackResponse.status);
    return JSON.parse(await fallbackResponse.text());
  }
}

function resetSelect(id, placeholder) {
  const sel = document.getElementById(id);
  sel.innerHTML = '<option value="">' + placeholder + '</option>';
  sel.disabled = true;
}

function fillSelect(id, items, placeholder) {
  const sel = document.getElementById(id);
  sel.innerHTML = '<option value="">' + placeholder + '</option>';
  items.sort((a, b) => a.name.localeCompare(b.name));
  items.forEach(item => {
    const opt = document.createElement('option');
    opt.value = item.code;
    opt.textContent = item.name;
    sel.appendChild(opt);
  });
  sel.disabled = false;
}

function setNotApplicableProvince(value, label) {
  const sel = document.getElementById('province');
  sel.innerHTML = '<option value="' + value + '">' + label + '</option>';
  sel.value = value;
  sel.disabled = true;
}

/* ---------- Address (Step 3) ---------- */

async function loadRegions() {
  try {
    const regions = await fetchWithFallback(PSGC_API_BASE + '/regions/');
    fillSelect('region', regions, 'Select Region');
  } catch (error) {
    showError('Unable to load the address list. Please refresh the page.');
  }
}

async function loadCitiesByRegion(regionCode) {
  try {
    const cities = await fetchWithFallback(PSGC_API_BASE + '/regions/' + regionCode + '/cities-municipalities/');
    fillSelect('city', cities, 'Select City/Municipality');
  } catch (error) {
    showError('Unable to load cities. Please try again.');
  }
}

async function loadProvinces(regionCode) {
  resetSelect('province', 'Select Province');
  resetSelect('city', 'Select City/Municipality');
  resetSelect('barangay', 'Select Barangay');

  if (regionCode === NCR_CODE) {
    setNotApplicableProvince('NCR', 'N/A (NCR)');
    await loadCitiesByRegion(NCR_CODE);
    return;
  }

  try {
    const provinces = await fetchWithFallback(PSGC_API_BASE + '/regions/' + regionCode + '/provinces/');
    if (provinces.length === 0) {
      setNotApplicableProvince('N/A', 'N/A');
      await loadCitiesByRegion(regionCode);
      return;
    }
    fillSelect('province', provinces, 'Select Province');
  } catch (error) {
    showError('Unable to load provinces. Please try again.');
  }
}

async function loadCities(provinceCode) {
  resetSelect('city', 'Select City/Municipality');
  resetSelect('barangay', 'Select Barangay');
  try {
    const cities = await fetchWithFallback(PSGC_API_BASE + '/provinces/' + provinceCode + '/cities-municipalities/');
    fillSelect('city', cities, 'Select City/Municipality');
  } catch (error) {
    showError('Unable to load cities. Please try again.');
  }
}

async function loadBarangays(cityCode) {
  resetSelect('barangay', 'Select Barangay');
  try {
    const barangays = await fetchWithFallback(PSGC_API_BASE + '/cities-municipalities/' + cityCode + '/barangays/');
    fillSelect('barangay', barangays, 'Select Barangay');
  } catch (error) {
    showError('Unable to load barangays. Please try again.');
  }
}

function selectedText(id) {
  const sel = document.getElementById(id);
  return sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : '';
}

/* ---------- Zipcode auto-fill (Step 3) ---------- */

/* Kinukuha sa Laravel (GET /api/zipcode?city=<PSGC city code>). City-level ang data:
   - iisa ang zipcode ng city: auto-fill
   - maraming zipcode (hal. Manila): hindi mag-a-auto-fill, ipapakita ang mga posibleng zipcode sa placeholder
   - walang nakita o may error: manual ang zipcode */
async function fetchZip(cityCode) {
  if (Object.prototype.hasOwnProperty.call(zipCache, cityCode)) return zipCache[cityCode];

  try {
    const res = await fetch(ZIP_API + '?city=' + encodeURIComponent(cityCode), {
      headers: { 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    const result = { zip: data.zip || null, zips: Array.isArray(data.zips) ? data.zips : [] };
    zipCache[cityCode] = result;
    return result;
  } catch (error) {
    zipDebug('fetch error: ' + error.message + ' (city=' + cityCode + ')');
    return { zip: null, zips: [] };
  }
}

function clearAutoZip() {
  const zip = document.getElementById('zipcode');
  if (zip.dataset.auto === '1') {
    zip.value = '';
    delete zip.dataset.auto;
  }
  zip.placeholder = ZIP_PLACEHOLDER;
}

async function applyAutoZip() {
  const zip = document.getElementById('zipcode');
  const cityCode = document.getElementById('city').value;
  zipDebug('applyAutoZip tumakbo, city=' + cityCode);
  if (!cityCode) return;

  const result = await fetchZip(cityCode);
  zipDebug('city=' + cityCode + ' zip=' + result.zip + ' zips=' + result.zips.join(','));

  // Nagbago na ba ang city/barangay habang naghihintay?
  if (document.getElementById('city').value !== cityCode || !document.getElementById('barangay').value) return;

  // Huwag palitan kung manu-manong tinype ng user.
  if (zip.value && zip.dataset.auto !== '1') return;

  if (result.zip) {
    zip.value = result.zip;
    zip.dataset.auto = '1';
    zip.placeholder = ZIP_PLACEHOLDER;
    clearFieldError('zipcode');
    return;
  }

  clearAutoZip();
  if (result.zips.length > 1) {
    zip.placeholder = 'e.g. ' + result.zips.slice(0, 3).join(' / ');
  }
}

/* ---------- Countries, citizenship, birth place (Step 2) ---------- */

function countriesFromIntl() {
  const skip = ['EU', 'UN', 'EZ', 'ZZ', 'XA', 'XB', 'QO'];
  const names = [];
  try {
    const dn = new Intl.DisplayNames(['en'], { type: 'region' });
    const A = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for (let i = 0; i < 26; i++) {
      for (let j = 0; j < 26; j++) {
        const code = A[i] + A[j];
        if (skip.includes(code)) continue;
        let name;
        try { name = dn.of(code); } catch (e) { continue; }
        if (name && name !== code && name !== 'Unknown Region') names.push(name);
      }
    }
  } catch (e) {
    names.push(PH_NAME);
  }
  return names;
}

/* Inilalagay muna ang default (hal. Philippines), tapos alphabetical ang iba. */
function fillPlainSelect(id, list, first, placeholder) {
  const rest = Array.from(new Set(list.map(n => String(n).trim()).filter(Boolean)))
    .filter(n => n !== first)
    .sort((a, b) => a.localeCompare(b));

  const sel = document.getElementById(id);
  sel.innerHTML = '<option value="">' + placeholder + '</option>';

  [first].concat(rest).forEach(name => {
    const opt = document.createElement('option');
    opt.value = name;
    opt.textContent = name;
    sel.appendChild(opt);
  });

  sel.value = first;
  sel.disabled = false;
}

async function loadCountries() {
  let countries = [];
  let nationalities = [];

  try {
    const res = await fetch(COUNTRIES_API);
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    countries = data.map(c => c.name && c.name.common).filter(Boolean);
    nationalities = data.map(c => c.demonyms && c.demonyms.eng && c.demonyms.eng.m).filter(Boolean);
    if (!countries.length || !nationalities.length) throw new Error('Empty list');
  } catch (error) {
    countries = countriesFromIntl();
    nationalities = FALLBACK_NATIONALITIES.slice();
  }

  fillPlainSelect('birthCountry', countries, PH_NAME, 'Select Country');
  fillPlainSelect('citizenship', nationalities, PH_DEMONYM, 'Select citizenship');

  setBirthMode(true);
  loadBirthProvinces();
}

/* isPH = true: dropdowns (PSGC). false: text inputs para sa ibang bansa. */
function setBirthMode(isPH) {
  TEXT_SWAP.forEach(base => {
    const sel = document.getElementById(base);
    const txt = document.getElementById(base + 'Text');
    sel.classList.toggle('is-off', !isPH);
    txt.classList.toggle('is-off', isPH);
    if (isPH) {
      txt.value = '';
    } else {
      sel.disabled = true;
    }
  });
}

async function loadBirthProvinces() {
  resetSelect('birthProvince', 'Select Province');
  resetSelect('birthCity', 'Select City/Municipality');

  try {
    if (!phProvincesCache) {
      const list = await fetchWithFallback(PSGC_API_BASE + '/provinces/');
      phProvincesCache = list
        .map(p => ({ code: p.code, name: p.name }))
        .filter(p => !/metro manila|national capital/i.test(p.name));
      phProvincesCache.push({ code: 'NCR', name: 'Metro Manila' });
    }
    fillSelect('birthProvince', phProvincesCache.slice(), 'Select Province');
  } catch (error) {
    showError('Unable to load provinces. Please try again.');
  }
}

async function loadBirthCities(provinceCode) {
  resetSelect('birthCity', 'Select City/Municipality');

  const url = provinceCode === 'NCR'
    ? PSGC_API_BASE + '/regions/' + NCR_CODE + '/cities-municipalities/'
    : PSGC_API_BASE + '/provinces/' + provinceCode + '/cities-municipalities/';

  try {
    const cities = await fetchWithFallback(url);
    fillSelect('birthCity', cities, 'Select City/Municipality');
  } catch (error) {
    showError('Unable to load cities. Please try again.');
  }
}

function birthPlace(base) {
  const sel = document.getElementById(base);
  return sel.classList.contains('is-off') ? val(base + 'Text') : selectedText(base);
}

function incomeValue() {
  return val('monthlyIncome') === 'Others' ? val('monthlyIncomeOther') : val('monthlyIncome');
}

/* Pinagsasama ang apat na optional na address field sa iisang homeAddress,
   para hindi na kailangang baguhin ang backend o database. */
function buildHomeAddress() {
  return ['houseNo', 'street', 'subdivision', 'addressDetails']
    .map(id => val(id))
    .filter(Boolean)
    .join(', ');
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
  });
}

/* ---------- Validators ---------- */

function validateAccount() {
  clearInvalid();
  hideError();

  if (!checkRequired(['firstName', 'lastName', 'email', 'birthMonth', 'birthDay', 'birthYear', 'password', 'confirmPassword'])) {
    return false;
  }

  const middleName = val('middleName');
  if (middleName && !/^[A-Za-z]$/.test(middleName)) {
    showFieldError('middleName', 'Middle name must be a single letter.');
    return false;
  }

  if (!isValidEmail(val('email'))) {
    showFieldError('email', 'Please enter a valid email address (e.g. name@example.com).');
    return false;
  }

  const year  = parseInt(val('birthYear'), 10);
  const month = parseInt(val('birthMonth'), 10);
  const day   = parseInt(val('birthDay'), 10);
  if (!isAtLeastMinAge(year, month, day)) {
    showFieldError('birthday', 'You must be 18 years old or above to register.');
    return false;
  }

  const password = document.getElementById('password').value;
  const failing = validatePassword(password);
  if (failing.length > 0) {
    return fail('password', 'Password must include: ' + failing.join(', ') + '.');
  }

  if (password !== document.getElementById('confirmPassword').value) {
    return fail('confirmPassword', 'Passwords do not match.');
  }

  return true;
}

function validatePersonal() {
  clearInvalid();
  hideError();

  const required = ['gender', 'civilStatus', 'citizenship', 'cellphone', 'birthCountry', 'birthProvince', 'birthCity', 'monthlyIncome'];
  if (val('monthlyIncome') === 'Others') required.push('monthlyIncomeOther');

  if (!checkRequired(required)) {
    return false;
  }

  const digits = document.getElementById('cellphone').value.slice(4).replace(/[^0-9]/g, '');
  if (digits.length !== 10 || !digits.startsWith('9')) {
    return fail('cellphone', 'Please enter a valid mobile number (e.g. +63 912 345 6789).');
  }

  return true;
}

function validateAddress() {
  clearInvalid();
  hideError();

  if (!checkRequired(['region', 'province', 'city', 'barangay'])) {
    return false;
  }

  const zip = val('zipcode');
  if (zip && !/^[0-9]{4}$/.test(zip)) {
    return fail('zipcode', 'Zipcode must be 4 digits.');
  }

  return true;
}

/* ---------- Wizard ---------- */

const RegWizard = (function () {
  const validators = [validateAccount, validatePersonal, validateAddress];
  let steps = [];
  let items = [];
  let connectors = [];
  let current = 0;

  function render() {
    steps.forEach((step, i) => step.classList.toggle('is-active', i === current));

    items.forEach((item, i) => {
      item.classList.toggle('active', i === current);
      item.classList.toggle('done', i < current);
      item.querySelector('.reg-progress-circle').innerHTML =
        i < current ? '<i class="bi bi-check-lg"></i>' : String(i + 1);
    });

    connectors.forEach((c, i) => c.classList.toggle('done', i < current));

    const isFirst = current === 0;
    const isLast  = current === steps.length - 1;

    document.getElementById('regBtnBack').style.display = isFirst ? 'none' : '';
    document.getElementById('regBtnNext').style.display    = isLast ? 'none' : '';
    document.getElementById('signupBtn').style.display     = isLast ? '' : 'none';

    closeAllDropdowns();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function goTo(index) {
    current = Math.max(0, Math.min(index, steps.length - 1));
    render();
  }

  function validateAll() {
    for (let i = 0; i < validators.length; i++) {
      if (!validators[i]()) {
        goTo(i);
        focusInvalid();
        return false;
      }
    }
    return true;
  }

  function init() {
    steps      = Array.from(document.querySelectorAll('#regForm .reg-step'));
    items      = Array.from(document.querySelectorAll('#regProgress .reg-progress-step'));
    connectors = Array.from(document.querySelectorAll('#regProgress .reg-progress-connector'));

    const nextBtn = document.getElementById('regBtnNext');

    function setNextLoading(on) {
      nextBtn.disabled = on;
      nextBtn.classList.toggle('is-loading', on);
    }

    nextBtn.addEventListener('click', async function () {
      if (nextBtn.disabled) return;

      if (!validators[current]()) {
        focusInvalid();
        return;
      }

      setNextLoading(true);
      const started = Date.now();

      let ok = true;
      if (current === 0) ok = await checkEmailAvailable();

      const wait = NEXT_MIN_LOADING_MS - (Date.now() - started);
      if (wait > 0) await new Promise(resolve => setTimeout(resolve, wait));

      setNextLoading(false);
      if (!ok) return;

      goTo(current + 1);
    });

    document.getElementById('regBtnBack').addEventListener('click', function () {
      hideError();
      goTo(current - 1);
    });

    render();
  }

  return { init, goTo, validateAll };
})();

document.addEventListener('DOMContentLoaded', function () {

  populateBirthdaySelects();
  enhanceAllSelects();
  setupPhoneInput('cellphone');
  loadRegions();
  loadCountries();

  const passwordInputEl = document.getElementById('password');
  if (passwordInputEl) {
    passwordInputEl.addEventListener('input', updatePasswordStrengthUI);
    updatePasswordStrengthUI();
  }

  /* Zipcode: burahin ang auto-filled kapag nagbago ang address, i-fill kapag may barangay na */
  ['region', 'province', 'city'].forEach(id => {
    document.getElementById(id).addEventListener('change', clearAutoZip);
  });
  document.getElementById('barangay').addEventListener('change', function () {
    if (this.value) applyAutoZip(); else clearAutoZip();
  });

  /* Address cascade */
  document.getElementById('region').addEventListener('change', function () {
    if (this.value) {
      loadProvinces(this.value);
    } else {
      resetSelect('province', 'Select Province');
      resetSelect('city', 'Select City/Municipality');
      resetSelect('barangay', 'Select Barangay');
    }
  });

  document.getElementById('province').addEventListener('change', function () {
    if (this.value) {
      loadCities(this.value);
    } else {
      resetSelect('city', 'Select City/Municipality');
      resetSelect('barangay', 'Select Barangay');
    }
  });

  document.getElementById('city').addEventListener('change', function () {
    if (this.value) {
      loadBarangays(this.value);
    } else {
      resetSelect('barangay', 'Select Barangay');
    }
  });

  /* Birth place cascade */
  document.getElementById('birthCountry').addEventListener('change', function () {
    clearFieldError('birthProvince');
    clearFieldError('birthCity');

    if (!this.value) {
      setBirthMode(true);
      resetSelect('birthProvince', 'Select Province');
      resetSelect('birthCity', 'Select City/Municipality');
      return;
    }

    if (this.value === PH_NAME) {
      setBirthMode(true);
      loadBirthProvinces();
    } else {
      setBirthMode(false);
    }
  });

  document.getElementById('birthProvince').addEventListener('change', function () {
    clearFieldError('birthCity');
    if (this.value) {
      loadBirthCities(this.value);
    } else {
      resetSelect('birthCity', 'Select City/Municipality');
    }
  });

  /* Monthly income: Others */
  document.getElementById('monthlyIncome').addEventListener('change', function () {
    const isOther = this.value === 'Others';
    document.getElementById('monthlyIncomeOtherWrap').classList.toggle('is-off', !isOther);
    if (isOther) {
      document.getElementById('monthlyIncomeOther').focus();
    } else {
      document.getElementById('monthlyIncomeOther').value = '';
      clearFieldError('monthlyIncomeOther');
    }
  });

  document.getElementById('zipcode').addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
    delete this.dataset.auto; // manual edit na ito
  });

  const regForm = document.getElementById('regForm');
  function clearOnEdit(e) {
    if (!e.target.classList) return;
    e.target.classList.remove('is-invalid');
    if (e.target.id) clearFieldError(e.target.id);
  }
  regForm.addEventListener('input', clearOnEdit);
  regForm.addEventListener('change', clearOnEdit);

  RegWizard.init();

  const signupBtn = document.getElementById('signupBtn');

  const agreeEl = document.getElementById('agreeTerms');
  let signupBusy = false;

  /* Disabled ang Create Account hangga't hindi naka-check ang Terms. */
  function updateSignupState() {
    signupBtn.disabled = signupBusy || !agreeEl.checked;
  }

  function showLoading() {
    signupBusy = true;
    signupBtn.disabled = true;
    signupBtn.classList.add('is-loading');
  }

  function hideLoading() {
    signupBusy = false;
    signupBtn.classList.remove('is-loading');
    updateSignupState();
  }

  agreeEl.addEventListener('change', updateSignupState);
  updateSignupState();

  signupBtn.addEventListener('click', function () {
    if (signupBtn.disabled) return;

    hideError();

    if (!RegWizard.validateAll()) return;

    if (!document.getElementById('agreeTerms').checked) {
      showFieldError('agreeTerms', 'Please agree to the Terms of Service and Privacy Policy.');
      return;
    }

    showLoading();

    const fields = {
      firstName:       val('firstName'),
      middleName:      val('middleName'),
      lastName:        val('lastName'),
      email:           val('email'),
      password:        document.getElementById('password').value,
      confirmPassword: document.getElementById('confirmPassword').value,
      birthMonth:      val('birthMonth'),
      birthDay:        val('birthDay'),
      birthYear:       val('birthYear'),
      suffix:          val('suffix'),
      gender:          val('gender'),
      civilStatus:     val('civilStatus'),
      citizenship:     val('citizenship'),
      cellphone:       val('cellphone'),
      birthCountry:    val('birthCountry'),
      birthProvince:   birthPlace('birthProvince'),
      birthCity:       birthPlace('birthCity'),
      monthlyIncome:   incomeValue(),
      region:          selectedText('region'),
      province:        selectedText('province'),
      city:            selectedText('city'),
      barangay:        selectedText('barangay'),
      zipcode:         val('zipcode'),
      homeAddress:     buildHomeAddress(),
    };

    const formData = new FormData();
    Object.entries(fields).forEach(([key, value]) => formData.append(key, value));

    fetch('/api/register', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      hideLoading();

      if (data.success) {
        pendingRegisterEmail = data.email;
        openRegisterOtpModal(data.email);
        return;
      }

      if (data.errors) {
        const fieldNames = Object.keys(data.errors);
        if (fieldNames[0] in FIELD_STEP) RegWizard.goTo(FIELD_STEP[fieldNames[0]]);
        clearInvalid();

        let unmapped = null;
        fieldNames.forEach(field => {
          // Ang error sa homeAddress ay ipapakita sa House / Building No.
          const target = field === 'homeAddress' ? 'houseNo' : field;
          const shown = showFieldError(target, data.errors[field][0]);
          if (!shown && !unmapped) unmapped = data.errors[field][0];
        });

        focusInvalid();
        if (unmapped) showError(unmapped);
      } else {
        showError(data.message || 'Registration failed. Please try again.');
      }
    })
    .catch(() => {
      hideLoading();
      showError('Unable to reach the server. Please check your connection and try again.');
    });
  });
});

function openRegisterOtpModal(email) {
  OtpModal.open({
    maskedEmail: email,
    onVerify: function (code) {
      const formData = new FormData();
      formData.append('email', pendingRegisterEmail);
      formData.append('otp',   code);

      return fetch('/api/register/verify-otp', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) throw new Error(data.message || 'Invalid or expired code.');
        return data;
      });
    },
    onResend: function () {
      const formData = new FormData();
      formData.append('email', pendingRegisterEmail);

      return fetch('/api/register/resend-otp', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) throw new Error(data.message || 'Could not resend code.');
        return data;
      });
    },
    onSuccess: function () {
      OtpModal.close();
      showSuccessModal();
    }
  });
}