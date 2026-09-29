// State management for pagination
const state = { acad: 1, non: 1 };

const maxPages = { acad: 2, non: 2 };
const DRAFT_TTL_MS = 5 * 60 * 1000;
const siblingDeleteIconUrl = document.currentScript?.src
    ? new URL('../images/delete.png', document.currentScript.src).href
    : '/images/delete.png';

function formatStudentIdValue(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 7);
    return digits.length > 2 ? `${digits.slice(0, 2)}-${digits.slice(2)}` : digits;
}

function getContactDigits(value) {
    let digits = String(value ?? '').replace(/\D/g, '');
    if (digits.startsWith('639')) digits = digits.slice(3);
    else if (digits.startsWith('09')) digits = digits.slice(2);
    return digits.slice(0, 9);
}

function calculateAgeValue(value) {
    const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) return '';

    const birthYear = Number(match[1]);
    const birthMonth = Number(match[2]) - 1;
    const birthDay = Number(match[3]);
    const today = new Date();
    let age = today.getFullYear() - birthYear;
    if (today.getMonth() < birthMonth || (today.getMonth() === birthMonth && today.getDate() < birthDay)) age--;
    return age >= 0 ? String(age) : '';
}

function refreshMobileFloatingField(input) {
    const wrapper = input?.closest('.mobile-floating-field');
    if (!wrapper) return;
    const hasValue = String(input.value ?? '').trim() !== '';
    wrapper.classList.toggle('is-floating', hasValue || input.matches(':focus') || input.readOnly || input.disabled);
    wrapper.classList.toggle('is-readonly', input.readOnly || input.disabled);
}

function initializeMobileFloatingLabels(form) {
    if (!form) return;
    const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([type="radio"]):not([type="checkbox"]), select, textarea');
    fields.forEach(input => {
        let wrapper = input.parentElement;
        let label = null;
        while (wrapper && wrapper !== form) {
            label = Array.from(wrapper.children).find(child => child.tagName === 'LABEL') || null;
            if (label) break;
            wrapper = wrapper.parentElement;
        }
        if (!wrapper || !label) return;

        wrapper.classList.add('mobile-floating-field');
        label.classList.add('mobile-floating-label');
        input.classList.add('mobile-floating-control');
        if (input.dataset.mobileFloatingBound !== 'true') {
            ['focus', 'blur', 'input', 'change'].forEach(eventName => {
                input.addEventListener(eventName, () => refreshMobileFloatingField(input));
            });
            input.dataset.mobileFloatingBound = 'true';
        }
        refreshMobileFloatingField(input);
    });
}

function initializeMobileUploadFilenames(form) {
    if (!form) return;
    form.querySelectorAll('input[type="file"]').forEach(input => {
        let filename = input.nextElementSibling;
        if (!filename?.classList.contains('non-academic-upload-filename')) {
            filename = document.createElement('span');
            filename.className = 'non-academic-upload-filename';
            filename.hidden = true;
            input.insertAdjacentElement('afterend', filename);
        }
        const updateFilename = () => {
            filename.textContent = Array.from(input.files || []).map(file => file.name).join(', ');
            filename.hidden = filename.textContent === '';
        };
        if (input.dataset.mobileFilenameBound !== 'true') {
            input.addEventListener('change', updateFilename);
            input.dataset.mobileFilenameBound = 'true';
        }
        updateFilename();
    });
}

function updateFamilyBackgroundAges(form) {
    [['mother_birthdate', 'mother_age'], ['father_birthdate', 'father_age']].forEach(([birthdateName, ageName]) => {
        const birthdate = form?.querySelector(`[name="family_background[${birthdateName}]"]`);
        const age = form?.querySelector(`[name="family_background[${ageName}]"]`);
        if (age) age.value = calculateAgeValue(birthdate?.value);
    });
}

function updateFamilyDisabilityVisibility(form) {
    const isDifferentlyAbled = form?.querySelector('[name="family_background[has_differently_abled_member]"]:checked')?.value === 'yes';
    const familyPanel = form?.querySelector('[data-non-academic-step="3"]');
    const details = familyPanel?.querySelector('#familyDisabilityDetails');
    const detailsInput = details?.querySelector('input');
    if (details) details.hidden = !isDifferentlyAbled;
    if (detailsInput) {
        const familyFieldsAreDisabled = familyPanel?.querySelector('[name="family_background[mother_name]"]')?.disabled === true;
        detailsInput.disabled = !isDifferentlyAbled || familyFieldsAreDisabled;
        if (!isDifferentlyAbled) detailsInput.value = '';
        refreshMobileFloatingField(detailsInput);
    }
}

function normalizeApplicationFormValues(form) {
    if (!form) return;
    const studentId = form.querySelector('[name="student_id_number"]');
    const contactNumber = form.querySelector('[name="contact_number"]');
    const birthdate = form.querySelector('[name="birthdate"]');
    const age = form.querySelector('[name="age"]');
    if (studentId) studentId.value = formatStudentIdValue(studentId.value);
    if (contactNumber) contactNumber.value = getContactDigits(contactNumber.value);
    if (age) age.value = calculateAgeValue(birthdate?.value);
}

function bindApplicationInputFormatting(form) {
    if (!form) return;
    const studentId = form.querySelector('[name="student_id_number"]');
    const contactNumber = form.querySelector('[name="contact_number"]');
    const birthdate = form.querySelector('[name="birthdate"]');
    const age = form.querySelector('[name="age"]');
    let contactNumberTouched = false;

    const blockNonDigits = event => {
        if (event.inputType === 'insertText' && event.data && /\D/.test(event.data)) event.preventDefault();
    };

    if (studentId) {
        studentId.addEventListener('beforeinput', blockNonDigits);
        studentId.addEventListener('input', () => {
            const cursor = studentId.selectionStart ?? studentId.value.length;
            const digitsBeforeCursor = studentId.value.slice(0, cursor).replace(/\D/g, '').length;
            studentId.value = formatStudentIdValue(studentId.value);
            const nextCursor = digitsBeforeCursor > 2 ? digitsBeforeCursor + 1 : digitsBeforeCursor;
            studentId.setSelectionRange(nextCursor, nextCursor);
        });
    }

    if (contactNumber) {
        const updateContactNumberIndicator = () => {
            if (!contactNumberTouched) return;
            const isComplete = getContactDigits(contactNumber.value).length === 9;
            contactNumber.classList.toggle('contact-number-invalid', !isComplete);
            contactNumber.setAttribute('aria-invalid', String(!isComplete));
        };

        contactNumber.addEventListener('beforeinput', blockNonDigits);
        contactNumber.addEventListener('input', () => {
            const cursor = contactNumber.selectionStart ?? contactNumber.value.length;
            const digitsBeforeCursor = contactNumber.value.slice(0, cursor).replace(/\D/g, '').length;
            contactNumber.value = getContactDigits(contactNumber.value);
            contactNumber.setSelectionRange(Math.min(digitsBeforeCursor, contactNumber.value.length), Math.min(digitsBeforeCursor, contactNumber.value.length));
            updateContactNumberIndicator();
        });
        contactNumber.addEventListener('blur', () => {
            contactNumberTouched = true;
            updateContactNumberIndicator();
        });
        form.addEventListener('reset', () => {
            contactNumberTouched = false;
            contactNumber.classList.remove('contact-number-invalid');
            contactNumber.removeAttribute('aria-invalid');
        });
    }

    if (birthdate && age) {
        const updateAge = () => { age.value = calculateAgeValue(birthdate.value); };
        birthdate.max = '2010-12-31';
        birthdate.addEventListener('input', updateAge);
        birthdate.addEventListener('change', updateAge);
        birthdate.addEventListener('blur', updateAge);
    }

    form.querySelectorAll('[name="family_background[mother_birthdate]"], [name="family_background[father_birthdate]"]').forEach(birthdateField => {
        const updateFamilyAge = () => updateFamilyBackgroundAges(form);
        birthdateField.addEventListener('input', updateFamilyAge);
        birthdateField.addEventListener('change', updateFamilyAge);
        birthdateField.addEventListener('blur', updateFamilyAge);
    });
    updateFamilyBackgroundAges(form);

    normalizeApplicationFormValues(form);
}

function getDraftKey(grantType, scholarshipId, scholarshipName) {
    const userId = window.currentUserId ?? 'guest';
    const rawId = scholarshipId || scholarshipName || 'new';
    const safeId = String(rawId).trim().replace(/[^a-zA-Z0-9_-]+/g, '_').replace(/^_+|_+$/g, '') || 'new';
    return `csu_gms_draft_${userId}_${grantType}_${safeId}`;
}

function readDraft(grantType, scholarshipId, scholarshipName) {
    if (typeof window === 'undefined' || !window.sessionStorage) return null;

    try {
        const key = getDraftKey(grantType, scholarshipId, scholarshipName);
        const payload = JSON.parse(window.sessionStorage.getItem(key) || 'null');
        if (!payload || !payload.timestamp || !payload.values) return null;

        const age = Date.now() - Number(payload.timestamp || 0);
        if (!Number.isFinite(age) || age > DRAFT_TTL_MS) {
            window.sessionStorage.removeItem(key);
            return null;
        }

        return payload;
    } catch (error) {
        return null;
    }
}

function clearDraft(grantType, scholarshipId, scholarshipName) {
    if (typeof window === 'undefined' || !window.sessionStorage) return;
    try {
        window.sessionStorage.removeItem(getDraftKey(grantType, scholarshipId, scholarshipName));
    } catch (error) {
        // ignore session storage failures
    }
}

function saveDraft(form, grantType, scholarshipId, scholarshipName) {
    if (!form || typeof window === 'undefined' || !window.sessionStorage) return;

    const values = {};
    const excludedNames = new Set(['email', 'application_id', 'student_profile_id', 'scholarship_name', 'scholarship_type', 'scholarship_id']);

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach(input => {
        if (input.type === 'file' || input.disabled || input.readOnly || !input.name || excludedNames.has(input.name)) {
            return;
        }

        values[input.name] = input.value ?? '';
    });

    if (!Object.keys(values).length) return;

    const draft = {
        timestamp: Date.now(),
        grantType,
        scholarshipId: scholarshipId || form.querySelector('[name="scholarship_id"]')?.value || '',
        scholarshipName: scholarshipName || form.querySelector('[name="scholarship_name"]')?.value || '',
        values,
    };

    try {
        window.sessionStorage.setItem(getDraftKey(grantType, draft.scholarshipId, draft.scholarshipName), JSON.stringify(draft));
    } catch (error) {
        // ignore session storage failures
    }
}

function applyDraftToForm(form, draft) {
    if (!form || !draft || !draft.values) return;

    Object.entries(draft.values).forEach(([fieldName, value]) => {
        const input = form.querySelector(`[name="${fieldName}"]`);
        if (!input || input.type === 'file' || input.readOnly) return;
        input.value = value ?? '';
        if (fieldName === 'course' && input.value) {
            updateCollegeDepartmentForForm(form);
        }
    });

    normalizeApplicationFormValues(form);
    if (form.id === 'applyForm' || form.id === 'academicApplyForm') initializeMobileFloatingLabels(form);
}

function getPrefillData(grantType, scholarshipId, scholarshipName) {
    const url = new URL('/student-application-prefill', window.location.origin);
    url.searchParams.set('grant_type', grantType);
    if (scholarshipId) url.searchParams.set('scholarship_id', scholarshipId);
    if (scholarshipName) url.searchParams.set('scholarship_name', scholarshipName);

    return fetch(url.toString(), { credentials: 'same-origin' })
        .then(response => response.ok ? response.json() : { data: null })
        .then(payload => payload && payload.data ? payload.data : null)
        .catch(() => null);
}

function applyFamilyBackgroundPrefill(form, familyBackground) {
    const familyPanel = form?.querySelector('[data-non-academic-step="3"]');
    if (!familyPanel || !familyBackground || typeof familyBackground !== 'object') return;

    Object.entries(familyBackground).forEach(([fieldName, value]) => {
        if (fieldName === 'siblings' || value === null || value === undefined) return;

        familyPanel.querySelectorAll(`[name="family_background[${fieldName}]"]`).forEach(input => {
            if (input.type === 'radio') {
                const normalizedValue = fieldName === 'has_differently_abled_member'
                    ? (value ? 'yes' : 'no')
                    : String(value);
                input.checked = input.value.toLowerCase() === normalizedValue.toLowerCase();
            } else {
                input.value = fieldName.endsWith('birthdate') ? String(value).split('T')[0] : value;
            }
        });
    });

    const siblingsBody = familyPanel.querySelector('#familySiblingsBody');
    const addSiblingButton = familyPanel.querySelector('#addFamilySibling');
    siblingsBody?.replaceChildren();
    if (addSiblingButton) addSiblingButton.disabled = false;
    (Array.isArray(familyBackground.siblings) ? familyBackground.siblings : []).slice(0, 10).forEach(sibling => {
        addSiblingButton?.click();
        const row = siblingsBody?.lastElementChild;
        ['name', 'age', 'marital_status', 'occupation'].forEach(fieldName => {
            const input = row?.querySelector(`[name$="[${fieldName}]"]`);
            if (input && sibling[fieldName] !== null && sibling[fieldName] !== undefined) {
                input.value = sibling[fieldName];
            }
        });
    });

    updateFamilyDisabilityVisibility(form);
    updateFamilyBackgroundAges(form);
    initializeMobileFloatingLabels(form);
}

function applyPrefillValueToForm(form, fieldName, value) {
    if (!fieldName || value === null || value === undefined) return;
    if (fieldName === 'family_background') {
        applyFamilyBackgroundPrefill(form, value);
        return;
    }
    const input = form.querySelector(`[name="${fieldName}"]`);
    if (!input || input.type === 'file') return;
    input.value = value;
    if (form.id === 'applyForm' || form.id === 'academicApplyForm') initializeMobileFloatingLabels(form);
    normalizeApplicationFormValues(form);
    if (fieldName === 'course' && input.value) {
        updateCollegeDepartmentForForm(form);
    }
}

function setFormHydrationBusy(form, isBusy) {
    if (!form) return;
    form.dataset.hydrating = isBusy ? 'true' : 'false';
    form.setAttribute('aria-busy', isBusy ? 'true' : 'false');

    const actionButtons = form.querySelectorAll('button, input[type="submit"], input[type="button"]');
    actionButtons.forEach(button => {
        if (button.type === 'hidden') return;
        if (isBusy) {
            button.dataset.wasDisabledBeforeHydration = String(button.disabled);
            button.disabled = true;
            return;
        }

        const previous = button.dataset.wasDisabledBeforeHydration;
        button.disabled = previous === 'true';
        delete button.dataset.wasDisabledBeforeHydration;
    });
}

async function hydrateFormOpenState(form, grantType, scholarshipId, scholarshipName) {
    if (!form) return;
    if (form.dataset.reapplicationPrepared === 'true') {
        return;
    }

    delete form.dataset.familyBackgroundOnlyReapplication;
    form.dataset.hydrating = 'true';
    setFormHydrationBusy(form, true);

    try {
        const selectedScholarshipName = scholarshipName || form.querySelector('[name="scholarship_name"]')?.value || '';
        const selectedScholarshipId = scholarshipId || form.querySelector('[name="scholarship_id"]')?.value || '';

        const draft = readDraft(grantType, selectedScholarshipId, selectedScholarshipName);
        if (draft) {
            applyDraftToForm(form, draft);
            return;
        }

        const prefill = await getPrefillData(grantType, selectedScholarshipId, selectedScholarshipName);
        if (!prefill) return;

        Object.entries(prefill).forEach(([fieldName, value]) => {
            applyPrefillValueToForm(form, fieldName, value);
        });
    } finally {
        setFormHydrationBusy(form, false);
        delete form.dataset.hydrating;
    }
}

function bindDraftAutosave(form, grantType) {
    if (!form) return;

    const saveDraftNow = () => {
        const scholarshipId = form.querySelector('[name="scholarship_id"]')?.value || '';
        const scholarshipName = form.querySelector('[name="scholarship_name"]')?.value || '';
        saveDraft(form, grantType, scholarshipId, scholarshipName);
    };

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach(input => {
        if (input.type === 'file' || input.readOnly || !input.name) return;
        input.addEventListener('input', saveDraftNow);
        input.addEventListener('change', saveDraftNow);
    });
}

function bindDraftClearOnSubmit(form, grantType) {
    if (!form) return;
    form.addEventListener('submit', function () {
        const scholarshipId = form.querySelector('[name="scholarship_id"]')?.value || '';
        const scholarshipName = form.querySelector('[name="scholarship_name"]')?.value || '';
        clearDraft(grantType, scholarshipId, scholarshipName);
    });
}

// Change page function
function changePage(sec, dir) {
    const np = state[sec] + dir;
    if (np < 1 || np > maxPages[sec]) return;
    goPage(sec, np);
}

// Go to specific page
function goPage(sec, page) {
    for (let i = 1; i <= maxPages[sec]; i++) {
        const el = document.getElementById(`${sec}-p${i}`);
        if (el) el.classList.remove('active');
        const pn = document.getElementById(`${sec}-pg${i}`);
        if (pn) pn.classList.remove('active');
    }
    const t = document.getElementById(`${sec}-p${page}`);
    if (t) t.classList.add('active');
    const pa = document.getElementById(`${sec}-pg${page}`);
    if (pa) pa.classList.add('active');
    state[sec] = page;
    document.getElementById(`${sec}-prev`).disabled = (page === 1);
    document.getElementById(`${sec}-next`).disabled = (page === maxPages[sec]);
}

// Filter cards based on search
function filterCards(query = '') {
    const q = (query || '').toLowerCase();
    document.querySelectorAll('.page-set').forEach(ps => { ps.style.display = q ? 'grid' : ''; });
    if (!q) {
        ['acad', 'non'].forEach(sec => {
            for (let i = 1; i <= maxPages[sec]; i++) {
                const el = document.getElementById(`${sec}-p${i}`);
                if (el) el.style.display = '';
                if (el) el.classList.toggle('active', i === state[sec]);
            }
        });
    }
    document.querySelectorAll('.s-card').forEach(card => {
        const name = (card.dataset.name || '').toLowerCase();
        const title = (card.querySelector('h3')?.textContent || '').toLowerCase();
        card.style.display = (!q || name.includes(q) || title.includes(q)) ? '' : 'none';
    });
}

// Modal handling
function openApplyModal() {
    const m = document.getElementById('applyModal');
    if (m) { m.classList.add('open'); m.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden'; }
}

function closeApplyModal() {
    const m = document.getElementById('applyModal');
    if (m) { m.classList.remove('open'); m.setAttribute('aria-hidden','true'); document.body.style.overflow = ''; }
}

function openAcademicApplyModal() {
    const m = document.getElementById('academicApplyModal');
    const form = document.getElementById('academicApplyForm');
    if (form) {
        form.dataset.currentAcademicStep = '1';
        updateAcademicStepUI(1);
    }
    if (m) { m.classList.add('open'); m.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden'; }
}

function findAppliedScholarship(scholarshipId, grantType) {
    if (!scholarshipId) return null;
    return (window.appliedScholarships || []).find(application =>
        String(application.scholarship_id) === String(scholarshipId)
        && application.grant_type === grantType
    ) || null;
}

function prepareReapplication(title, modalType, scholarshipId) {
    const grantType = modalType === 'academic' ? 'academic' : 'non-academic';
    const profile = (window.reapplicationProfiles || {})[`${grantType}|${scholarshipId}`];
    if (!profile) return;

    const form = document.getElementById(modalType === 'academic' ? 'academicApplyForm' : 'applyForm');
    if (!form) return;

    form.dataset.reapplicationPrepared = 'true';
    form.querySelectorAll('[data-reapplication-preserved]').forEach(input => input.remove());

    const values = {
        first_name: profile.first_name,
        middle_name: profile.middle_name,
        last_name: profile.last_name,
        suffix: profile.suffix,
        email: profile.email,
        student_id_number: profile.student_id_number,
        id_number: profile.id_number || profile.student_id_number,
        course: profile.course,
        college_department: profile.college_department,
        year_level: profile.year_level,
        program: profile.program,
        birthdate: profile.birthdate ? String(profile.birthdate).split('T')[0] : '',
        birthplace: profile.birthplace,
        sex: profile.sex,
        status: profile.status,
        religion: profile.religion,
        contact_number: profile.contact_number,
        home_address: profile.home_address,
        scholarship_name: profile.scholarship_name,
        scholarship_type: profile.scholarship_type,
    };

    Object.entries(values).forEach(([name, value]) => {
        const input = form.querySelector(`[name="${name}"]`);
        if (!input || value === null || value === undefined) return;
        input.value = value;
    });

    normalizeApplicationFormValues(form);

    const courseField = form.querySelector('[name="course"]');
    if (courseField && courseField.value) {
        updateCollegeDepartmentForForm(form);
    }

    const applicationIdInput = form.querySelector('[name="application_id"]');
    const studentProfileIdInput = form.querySelector('[name="student_profile_id"]');
    if (applicationIdInput) applicationIdInput.value = profile.application_id || '';
    if (studentProfileIdInput) studentProfileIdInput.value = profile.id || '';

    const studentFields = ['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'student_id_number', 'id_number', 'course', 'college_department', 'year_level', 'program'];
    const personalFields = ['birthdate', 'birthplace', 'sex', 'status', 'religion', 'contact_number', 'home_address'];
    const sectionStatus = {
        student: profile.student_information_status,
        personal: profile.personal_information_status,
        requirements: profile.scholarship_requirements_status,
        family: profile.family_background_status,
    };
    const reviews = profile.document_review || profile.document_reviews || {};
    const rejectedGroups = new Set(Object.entries(reviews)
        .filter(([, review]) => review?.status === 'rejected')
        .map(([key]) => key.split('.')[0]));
    const preserveDisabledFields = (names, editable) => names.forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (!input) return;
        input.disabled = !editable;
        if (!editable && input.type !== 'file') {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = input.name;
            hidden.value = input.value;
            hidden.dataset.reapplicationPreserved = 'true';
            form.appendChild(hidden);
        }
    });
    preserveDisabledFields(studentFields, sectionStatus.student === 'rejected');
    preserveDisabledFields(personalFields, sectionStatus.personal === 'rejected');
    ['scholarship_name', 'scholarship_type', 'scholarship_id'].forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input && name !== 'scholarship_id') input.readOnly = true;
    });

    const isNonAcademic = modalType !== 'academic';
    const familyPanel = isNonAcademic ? form.querySelector('[data-non-academic-step="3"]') : null;
    const familyProgressStep = isNonAcademic
        ? document.querySelector('#applyModal .academic-progress-step[data-progress-step="3"]')
        : null;
    const familyBackgroundRejected = isNonAcademic && sectionStatus.family === 'rejected';
    const familyBackgroundOnlyReapplication = familyBackgroundRejected
        && sectionStatus.student === 'approved'
        && sectionStatus.personal === 'approved'
        && sectionStatus.requirements === 'approved';
    const familyBackground = profile.family_background || profile.familyBackground || {};
    form.dataset.familyBackgroundOnlyReapplication = familyBackgroundOnlyReapplication ? 'true' : 'false';
    if (familyPanel) {
        if (familyBackgroundRejected) familyPanel.dataset.familyBackgroundPage = '1';
        familyPanel.dataset.reapplicationHidden = familyBackgroundRejected ? 'false' : 'true';
        familyPanel.hidden = !familyBackgroundRejected;
        familyPanel.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = !familyBackgroundRejected;
        });
        Object.entries(familyBackground).forEach(([name, value]) => {
            if (name === 'siblings' || value === null || value === undefined) return;
            const inputs = familyPanel.querySelectorAll(`[name="family_background[${name}]"]`);
            inputs.forEach(input => {
                if (input.type === 'radio') {
                    const normalizedValue = name === 'has_differently_abled_member'
                        ? (value ? 'yes' : 'no')
                        : String(value);
                    input.checked = input.value.toLowerCase() === normalizedValue.toLowerCase();
                } else {
                    input.value = name.endsWith('birthdate') ? String(value).split('T')[0] : value;
                }
            });
        });
        const siblingsBody = familyPanel.querySelector('#familySiblingsBody');
        const addSiblingButton = familyPanel.querySelector('#addFamilySibling');
        (Array.isArray(familyBackground.siblings) ? familyBackground.siblings : []).forEach(sibling => {
            addSiblingButton?.click();
            const row = siblingsBody?.lastElementChild;
            ['name', 'age', 'marital_status', 'occupation'].forEach(name => {
                const input = row?.querySelector(`[name$="[${name}]"]`);
                if (input && sibling[name] !== null && sibling[name] !== undefined) input.value = sibling[name];
            });
        });
        updateFamilyDisabilityVisibility(form);
        updateFamilyBackgroundAges(form);
    }
    if (familyProgressStep) {
        familyProgressStep.dataset.reapplicationHidden = familyBackgroundRejected ? 'false' : 'true';
        familyProgressStep.hidden = !familyBackgroundRejected;
    }

    const sectionPanels = Array.from(form.querySelectorAll('.academic-step-panel'))
        .filter(panel => !isNonAcademic || panel.dataset.nonAcademicStep !== '3');
    sectionPanels.forEach((panel, index) => {
        const status = [sectionStatus.student, sectionStatus.personal, sectionStatus.requirements][index];
        const isLockedRequirementsAfterFamily = isNonAcademic
            && panel.dataset.nonAcademicStep === '4'
            && familyBackgroundRejected
            && !familyBackgroundOnlyReapplication
            && status === 'approved';
        const visible = status === 'rejected' || isLockedRequirementsAfterFamily;
        panel.dataset.reapplicationHidden = visible ? 'false' : 'true';
        panel.hidden = !visible;
        panel.querySelectorAll('input, select, textarea').forEach(input => {
            const preserveScholarshipValue = ['scholarship_name', 'scholarship_type'].includes(input.name)
                && sectionStatus.requirements !== 'rejected';
            if (input.type !== 'hidden' && !input.dataset.reapplicationPreserved && !preserveScholarshipValue) {
                input.disabled = status !== 'rejected';
            }
        });
    });
    form.querySelectorAll('[data-reapplication-document-group]').forEach(wrapper => {
        const group = wrapper.dataset.reapplicationDocumentGroup;
        const aliases = group === 'uploads'
            ? ['uploads', 'grades', 'certificate_of_grades', 'certificate_of_sports_coordinator']
            : group === 'certificate_of_enrollment'
                ? ['certificate_of_enrollment', 'enrollment']
                : [group];
        const visible = sectionStatus.requirements === 'rejected' && aliases.some(alias => rejectedGroups.has(alias));
        wrapper.dataset.reapplicationHidden = visible ? 'false' : 'true';
        wrapper.hidden = !visible;
        wrapper.querySelectorAll('input[type="file"]').forEach(input => {
            input.disabled = !visible;
            input.required = visible;
        });
    });

    form.querySelectorAll('input[type="file"]').forEach(input => input.required = false);
    if (rejectedGroups.has('certificate_of_grades') || rejectedGroups.has('grades') || rejectedGroups.has('uploads')) {
        const input = form.querySelector(modalType === 'academic'
            ? '#academicFileInput'
            : '#nonAcademicUploadsInput');
        if (input) input.required = true;
    }
    if (rejectedGroups.has('certificate_of_sports_coordinator')) {
        const input = form.querySelector('.athletic-only-fields [name="uploads[]"]');
        if (input) input.required = true;
    }
    if (rejectedGroups.has('certificate_of_enrollment') || rejectedGroups.has('enrollment')) {
        const input = form.querySelector('[name="certificate_of_enrollment"]');
        if (input) input.required = true;
    }
    if (rejectedGroups.has('certificate_of_adviser')) {
        const input = form.querySelector('[name="certificate_of_adviser"]');
        if (input) input.required = true;
    }

    form.dataset.reapplicationActive = 'true';
    initializeMobileFloatingLabels(form);
    const sectionProgressSteps = Array.from(form.querySelectorAll('.academic-progress-step'));
    sectionProgressSteps.forEach(step => {
        const progressStep = Number(step.dataset.progressStep);
        const progressStatus = isNonAcademic
            ? ({ 1: sectionStatus.student, 2: sectionStatus.personal, 3: sectionStatus.family, 4: sectionStatus.requirements })[progressStep]
            : ({ 1: sectionStatus.student, 2: sectionStatus.personal, 3: sectionStatus.requirements })[progressStep];
        const visible = progressStatus === 'rejected'
            || (isNonAcademic && familyBackgroundRejected && !familyBackgroundOnlyReapplication && progressStep === 4 && progressStatus === 'approved');
        step.dataset.reapplicationHidden = visible ? 'false' : 'true';
        step.hidden = !visible;
    });
    const visibleStepPanels = Array.from(form.querySelectorAll('.academic-step-panel'))
        .filter(panel => panel.dataset.reapplicationHidden !== 'true');
    const firstRejectedStep = visibleStepPanels.findIndex(panel => {
        const step = Number(isNonAcademic ? panel.dataset.nonAcademicStep : panel.dataset.academicStep);
        const statuses = isNonAcademic
            ? ({ 1: sectionStatus.student, 2: sectionStatus.personal, 3: sectionStatus.family, 4: sectionStatus.requirements })
            : ({ 1: sectionStatus.student, 2: sectionStatus.personal, 3: sectionStatus.requirements });
        return statuses[step] === 'rejected';
    });
    const firstVisibleRejectedIndex = firstRejectedStep >= 0 ? firstRejectedStep + 1 : 1;
    if (modalType === 'academic') updateAcademicStepUI(firstVisibleRejectedIndex);
    else updateNonAcademicStepUI(firstVisibleRejectedIndex);
}

function closeAcademicApplyModal() {
    const m = document.getElementById('academicApplyModal');
    if (m) { m.classList.remove('open'); m.setAttribute('aria-hidden','true'); document.body.style.overflow = ''; }
}

function formatDescriptionText(text = '') {
    return String(text || '').replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
}

function parseScholarshipDetails(description = '') {
    const text = (description || '').replace(/\r/g, '').trim();
    if (!text) {
        return { aboutText: 'No additional description is available for this scholarship.', requirements: [] };
    }

    const lines = text.split(/\n+/).map(line => line.trim()).filter(Boolean);
    const aboutLines = [];
    const requirements = [];
    let collectingRequirements = false;

    lines.forEach(line => {
        const normalized = line.toLowerCase();
        if (/^(eligibility requirements|requirements|qualifications|what you need|eligibility)$/.test(normalized)) {
            collectingRequirements = true;
            return;
        }

        const cleaned = line.replace(/^[\*\-\d.]+\s*/, '').trim();
        if (!cleaned) return;

        if (collectingRequirements) {
            requirements.push(cleaned);
        } else if (/^[\*\-]\s*/.test(line)) {
            requirements.push(cleaned);
        } else {
            aboutLines.push(cleaned);
        }
    });

    if (requirements.length === 0) {
        lines.filter(line => /^[\*\-]\s+/.test(line)).forEach(line => {
            requirements.push(line.replace(/^[\*\-]\s+/, '').trim());
        });
    }

    const aboutText = aboutLines.join('<br>').trim() || 'No additional description is available for this scholarship.';
    return { aboutText: formatDescriptionText(aboutText), requirements };
}

function openScholarshipDetailsModal(title, description, modalType = 'non', card = null) {
    const modal = document.getElementById('scholarshipDetailsModal');
    const titleEl = document.getElementById('detailsScholarshipTitle');
    const subtitleEl = document.getElementById('detailsScholarshipSubtitle');
    const tagsEl = document.getElementById('detailsScholarshipTags');
    const imageEl = document.getElementById('detailsScholarshipImage');
    const descEl = document.getElementById('detailsScholarshipDescription');
    const requirementsEl = document.getElementById('detailsScholarshipRequirements');
    const documentsEl = document.getElementById('detailsScholarshipDocuments');

    if (modal && titleEl && descEl) {
        const details = parseScholarshipDetails(description);
        const subtitle = card?.querySelector('.card-names .prog')?.textContent?.trim() || '';
        const typeTag = card?.querySelector('.type-tag')?.textContent?.trim() || '';
        const metaTag = card?.querySelector('.meta-chip')?.textContent?.trim() || '';
        const cardImage = card?.querySelector('.card-icon img');
        const cardImageSrc = card?.dataset.image || cardImage?.getAttribute('src') || '';

        titleEl.textContent = title || 'Scholarship Details';
        descEl.innerHTML = details.aboutText;

        if (requirementsEl) {
            requirementsEl.innerHTML = '';
            if (details.requirements.length > 0) {
                details.requirements.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    requirementsEl.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = 'No specific requirements are listed for this scholarship yet.';
                requirementsEl.appendChild(li);
            }
        }

        if (documentsEl) {
            let documents = [];
            try { documents = JSON.parse(card?.dataset.requiredDocuments || '[]'); } catch (error) { documents = []; }
            documentsEl.innerHTML = '';
            (documents.length ? documents : ['No specific documents are listed for this scholarship yet.']).forEach(item => {
                const li = document.createElement('li');
                li.textContent = item;
                documentsEl.appendChild(li);
            });
        }

        if (subtitleEl) {
            subtitleEl.textContent = subtitle || 'Program details';
        }

        if (tagsEl) {
            tagsEl.innerHTML = '';
            [typeTag, metaTag].filter(Boolean).forEach(tagText => {
                const chip = document.createElement('span');
                chip.className = 'detail-chip';
                if (tagText.toLowerCase().includes('academic') || tagText.toLowerCase().includes('funded')) {
                    chip.classList.add('detail-chip-accent');
                }
                chip.textContent = tagText;
                tagsEl.appendChild(chip);
            });

            if (!tagsEl.children.length) {
                const chip = document.createElement('span');
                chip.className = 'detail-chip';
                chip.textContent = 'Scholarship';
                tagsEl.appendChild(chip);
            }
        }

        if (imageEl) {
            if (cardImageSrc) {
                imageEl.src = cardImageSrc;
                imageEl.alt = title || 'Scholarship logo';
                imageEl.style.display = 'block';
            } else {
                imageEl.removeAttribute('src');
                imageEl.alt = title || 'Scholarship logo';
                imageEl.style.display = 'none';
            }
        }

        const grantType = modalType === 'academic' ? 'academic' : 'non-academic';
        const scholarshipId = card?.dataset.scholarshipId
            || (modalType === 'academic' ? window.academicScholarshipIdMap : window.nonAcademicScholarshipIdMap)?.[title]
            || '';
        const application = findAppliedScholarship(scholarshipId, grantType);
        const applyButton = document.getElementById('detailsApplyBtn');
        modal.dataset.modalType = modalType;
        modal.dataset.scholarshipId = scholarshipId;
        modal.dataset.grantType = grantType;
        if (applyButton) {
            const isLocked = Boolean(application?.locked);
            applyButton.disabled = isLocked;
            applyButton.textContent = isLocked ? 'UNAVAILABLE' : 'APPLY NOW';
            if (isLocked) applyButton.setAttribute('aria-disabled', 'true');
            else applyButton.removeAttribute('aria-disabled');
        }
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

function closeScholarshipDetailsModal() {
    const modal = document.getElementById('scholarshipDetailsModal');
    if (modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}

// Academic form popup handlers
function closeAcademicErrorPopup() {
    document.getElementById('academicErrorPopup').style.display = 'none';
}

function closeAcademicSuccessPopup() {
    document.getElementById('academicSuccessPopup').style.display = 'none';
}

function validateRequiredDocumentsForForm(form) {
    if (!form || form.dataset.reapplicationActive) return true;

    let groups = [];
    let configuredDocuments = [];
    try { groups = JSON.parse(form.dataset.requiredDocumentGroups || '[]'); } catch (error) { groups = []; }
    try { configuredDocuments = JSON.parse(form.dataset.requiredDocuments || '[]'); } catch (error) { configuredDocuments = []; }

    const filesForSelector = (selector) => {
        if (!selector) return [];
        return Array.from(form.querySelectorAll(selector))
            .reduce((files, input) => files.concat(Array.from(input.files || [])), []);
    };

    const athleticSportsCoordinatorSelector = '.athletic-only-fields [name="uploads[]"]';
    const genericUploadsSelector = '#nonAcademicUploadsInput';
    const groupInputs = {
        certificate_of_grades: '#academicFileInput',
        certificate_of_enrollment: '[name="certificate_of_enrollment"]',
        certificate_of_adviser: '[name="certificate_of_adviser"]',
        certificate_of_sports_coordinator: athleticSportsCoordinatorSelector,
        uploads: genericUploadsSelector,
    };
    const labels = {
        certificate_of_grades: 'Certificate of Grades',
        certificate_of_enrollment: 'Certificate of Enrollment',
        certificate_of_adviser: 'Certificate of Adviser',
        certificate_of_sports_coordinator: 'Certificate from Sports Coordinator',
        uploads: 'Required scholarship upload',
    };

    const missing = groups.filter(group => {
        const selector = groupInputs[group] || `[name="${group}"]`;
        return filesForSelector(selector).length === 0;
    });
    const allFiles = Array.from(form.querySelectorAll('input[type="file"]'))
        .reduce((files, input) => files.concat(Array.from(input.files || [])), []);

    if (missing.length > 0 || (groups.length === 0 && configuredDocuments.length > allFiles.length)) {
        const message = missing.length > 0
            ? `Please upload the required documents: ${missing.map(group => labels[group] || group).join(', ')}.`
            : `Please upload all required documents before submitting. ${allFiles.length}/${configuredDocuments.length} provided.`;
        Swal.fire({ icon: 'error', title: 'Required documents missing', text: message, confirmButtonColor: '#dc2626' });
        return false;
    }

    return true;
}

function submitAcademicForm() {
    const form = document.getElementById('academicApplyForm');
    if (form) form.requestSubmit();
}

function getAcademicStepControls() {
    const form = document.getElementById('academicApplyForm');
    const modal = document.getElementById('academicApplyModal');
    if (!form || !modal) return null;

    return {
        form,
        panels: Array.from(form.querySelectorAll('.academic-step-panel')),
        stepCounter: modal.querySelector('#academicStepCounter'),
        prevBtn: modal.querySelector('[data-academic-step="prev"]'),
        nextBtn: modal.querySelector('[data-academic-step="next"]'),
        submitBtn: modal.querySelector('[data-academic-step="submit"]'),
        progressSteps: Array.from(modal.querySelectorAll('.academic-progress-step'))
    };
}

function updateAcademicStepUI(stepNumber = 1) {
    const controls = getAcademicStepControls();
    if (!controls) return;

    const { form, panels, stepCounter, prevBtn, nextBtn, submitBtn, progressSteps } = controls;
    const visiblePanels = panels.filter(panel => panel.dataset.reapplicationHidden !== 'true');
    const visibleProgressSteps = progressSteps.filter(step => step.dataset.reapplicationHidden !== 'true');
    const totalSteps = visiblePanels.length || 3;
    const currentStep = Math.min(Math.max(Number(stepNumber) || 1, 1), totalSteps);
    form.dataset.currentAcademicStep = String(currentStep);
    const currentPanel = visiblePanels[currentStep - 1];
    const actualStep = Number(currentPanel?.dataset.academicStep) || currentStep;

    panels.forEach(panel => panel.classList.remove('active'));
    visiblePanels.forEach((panel, index) => {
        panel.classList.toggle('active', index + 1 === currentStep);
    });

    if (stepCounter) stepCounter.textContent = actualStep;
    const activeLabel = document.querySelector(`#academicApplyModal .academic-progress-step[data-progress-step="${actualStep}"] .academic-progress-label`);
    const mobileStepLabel = document.getElementById('academicStepLabel');
    const mobileProgress = document.querySelector('#academicApplyModal .academic-mobile-progress');
    if (mobileStepLabel) mobileStepLabel.textContent = activeLabel ? `· ${activeLabel.textContent.trim()}` : '';
    if (mobileProgress) mobileProgress.style.setProperty('--step-progress', `${actualStep * 100 / 3}%`);
    if (currentPanel) initializeMobileFloatingLabels(currentPanel);

    progressSteps.forEach(step => step.classList.remove('active', 'completed'));
    visibleProgressSteps.forEach(step => {
        const stepIndex = Number(step.dataset.progressStep);
        step.classList.toggle('active', stepIndex === actualStep);
        step.classList.toggle('completed', stepIndex < actualStep);
    });

    if (prevBtn) {
        const hidePrevious = currentStep === 1 && !window.matchMedia('(max-width: 768px)').matches;
        prevBtn.classList.toggle('is-hidden', hidePrevious);
        prevBtn.style.visibility = hidePrevious ? 'hidden' : 'visible';
        prevBtn.style.display = hidePrevious ? 'none' : '';
        prevBtn.setAttribute('aria-hidden', hidePrevious ? 'true' : 'false');
    }

    if (nextBtn) {
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
    }

    if (submitBtn) {
        submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }
}

function getNonAcademicStepControls() {
    const form = document.getElementById('applyForm');
    const modal = document.getElementById('applyModal');
    if (!form || !modal) return null;

    return {
        form,
        panels: Array.from(form.querySelectorAll('.academic-step-panel')),
        stepCounter: modal.querySelector('#nonAcademicStepCounter'),
        prevBtn: modal.querySelector('[data-non-academic-step="prev"]'),
        nextBtn: modal.querySelector('[data-non-academic-step="next"]'),
        submitBtn: modal.querySelector('[data-non-academic-step="submit"]'),
        progressSteps: Array.from(modal.querySelectorAll('.academic-progress-step'))
    };
}

function updateNonAcademicStepUI(stepNumber = 1) {
    const controls = getNonAcademicStepControls();
    if (!controls) return;

    const { form, panels, stepCounter, prevBtn, nextBtn, submitBtn, progressSteps } = controls;
    const visiblePanels = panels.filter(panel => panel.dataset.reapplicationHidden !== 'true');
    const visibleProgressSteps = progressSteps.filter(step => step.dataset.reapplicationHidden !== 'true');
    const totalSteps = visiblePanels.length || 3;
    const currentStep = Math.min(Math.max(Number(stepNumber) || 1, 1), totalSteps);
    form.dataset.nonAcademicStep = String(currentStep);
    const currentPanel = visiblePanels[currentStep - 1];
    const actualStep = Number(currentPanel?.dataset.nonAcademicStep) || currentStep;

    panels.forEach(panel => panel.classList.remove('active'));
    visiblePanels.forEach((panel, index) => {
        panel.classList.toggle('active', index + 1 === currentStep);
    });

    if (stepCounter) stepCounter.textContent = actualStep;
    const activeLabel = document.querySelector(`#applyModal .academic-progress-step[data-progress-step="${actualStep}"] .academic-progress-label`);
    const mobileStepLabel = document.getElementById('nonAcademicStepLabel');
    const mobileProgress = document.querySelector('#applyModal .non-academic-mobile-progress');
    if (mobileStepLabel) mobileStepLabel.textContent = activeLabel ? `· ${activeLabel.textContent.trim()}` : '';
    if (mobileProgress) mobileProgress.style.setProperty('--step-progress', `${actualStep * 25}%`);

    const familyPanel = form.querySelector('[data-non-academic-step="3"]');
    const familyPagination = form.querySelector('#familyBackgroundPagination');
    const familyBackgroundNext = familyPanel?.querySelector('#familyBackgroundNext');
    if (familyPanel) {
        updateFamilyBackgroundPage(form, Number(familyPanel.dataset.familyBackgroundPage || 1));
    }
    const isFamilyStep = visiblePanels[currentStep - 1] === familyPanel;
    const familyPage = Number(familyPanel?.dataset.familyBackgroundPage || 1);
    const submitsFamilyOnlyReapplication = form.dataset.familyBackgroundOnlyReapplication === 'true'
        && isFamilyStep
        && familyPage === 3;
    if (familyPagination) familyPagination.hidden = !isFamilyStep || submitsFamilyOnlyReapplication;
    if (familyBackgroundNext) {
        familyBackgroundNext.hidden = submitsFamilyOnlyReapplication;
        familyBackgroundNext.textContent = 'Next →';
    }

    progressSteps.forEach(step => step.classList.remove('active', 'completed'));
    visibleProgressSteps.forEach(step => {
        const stepIndex = Number(step.dataset.progressStep);
        step.classList.toggle('active', stepIndex === actualStep);
        step.classList.toggle('completed', stepIndex < actualStep);
    });

    if (prevBtn) {
        const hidePrevious = currentStep === 1 && (!isFamilyStep || familyPage === 1);
        prevBtn.classList.toggle('is-hidden', hidePrevious);
        prevBtn.style.visibility = hidePrevious ? 'hidden' : 'visible';
        prevBtn.style.display = hidePrevious ? 'none' : '';
        prevBtn.setAttribute('aria-hidden', hidePrevious ? 'true' : 'false');
    }

    if (nextBtn) {
        nextBtn.style.display = currentStep === totalSteps || isFamilyStep ? 'none' : 'inline-flex';
    }

    if (submitBtn) {
        const isRequirementsStep = currentPanel?.dataset.nonAcademicStep === '4';
        submitBtn.style.display = isRequirementsStep || submitsFamilyOnlyReapplication ? 'inline-flex' : 'none';
    }
}

function updateFamilyBackgroundPage(form, pageNumber) {
    const panel = form?.querySelector('[data-non-academic-step="3"]');
    if (!panel) return;

    const pages = Array.from(panel.querySelectorAll('[data-family-background-page]'));
    const page = Math.min(Math.max(Number(pageNumber) || 1, 1), pages.length || 3);
    panel.dataset.familyBackgroundPage = String(page);
    pages.forEach((subpage, index) => {
        const isActive = index + 1 === page;
        subpage.classList.toggle('active', isActive);
        subpage.hidden = !isActive;
    });

    const next = panel.querySelector('#familyBackgroundNext');
    if (next) next.textContent = 'Next →';
}

function validateAcademicCurrentStep() {
    const form = document.getElementById('academicApplyForm');
    if (!form) return true;

    const currentStep = Number(form.dataset.currentAcademicStep || 1);
    const panel = Array.from(form.querySelectorAll('.academic-step-panel'))
        .filter(candidate => candidate.dataset.reapplicationHidden !== 'true')[currentStep - 1];
    if (!panel) return true;

    const requiredFields = panel.querySelectorAll('input[required], select[required], textarea[required]');

    for (const field of requiredFields) {
        if (!field.checkValidity()) {
            field.reportValidity();
            field.focus();
            return false;
        }
    }

    return true;
}

function validateNonAcademicCurrentStep() {
    const form = document.getElementById('applyForm');
    if (!form) return true;

    const currentStep = Number(form.dataset.nonAcademicStep || 1);
    const panel = Array.from(form.querySelectorAll('.academic-step-panel'))
        .filter(candidate => candidate.dataset.reapplicationHidden !== 'true')[currentStep - 1];
    if (!panel) return true;

    const requiredFields = panel.querySelectorAll('input:not([disabled])[required], select:not([disabled])[required], textarea:not([disabled])[required]');

    for (const field of requiredFields) {
        if (!field.checkValidity()) {
            field.reportValidity();
            field.focus();
            return false;
        }
    }

    return true;
}

function validateFilesForSubmission(form, fileValidationRules) {
    const files = Array.from(form.querySelectorAll('input[type="file"]'))
        .reduce((allFiles, input) => allFiles.concat(Array.from(input.files || [])), []);

    if (files.length === 0) return true;

    const errors = [];
    const successFiles = [];

    files.forEach(file => {
        const extension = file.name.split('.').pop().toLowerCase();
        const category = fileValidationRules.document.extensions.includes(extension)
            ? 'document'
            : fileValidationRules.image.extensions.includes(extension)
                ? 'image'
                : null;

        if (!category) {
            errors.push(`"${file.name}" - Unsupported format. Allowed: PDF, DOCX, JPG, PNG`);
            return;
        }

        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        if (file.size > fileValidationRules[category].maxSize) {
            errors.push(`"${file.name}" - Size: ${fileSizeMB}MB exceeds limit of ${fileValidationRules[category].displaySize}`);
            return;
        }

        successFiles.push(`"${file.name}" - ${fileSizeMB}MB (${category})`);
    });

    if (errors.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'File Upload Error',
            text: errors.join('\n'),
            confirmButtonColor: '#dc2626'
        });
        return false;
    }

    if (successFiles.length > 0) {
        Swal.fire({
            icon: 'success',
            title: 'Files Validated Successfully',
            text: 'All files validated successfully and are ready to upload.\n' + successFiles.join('\n'),
            showCancelButton: true,
            confirmButtonText: 'Submit Application',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280'
        }).then(result => {
            if (result.isConfirmed) {
                form.dataset.confirmedSubmit = 'true';
                form.requestSubmit();
            }
        });
        return false;
    }

    return true;
}

const collegeDepartmentMap = {
    'BACHELOR OF SCIENCE IN CRIMINOLOGY': 'College of Criminal Justice Education',
    'BACHELOR OF SCIENCE IN FISHERIES': 'College of Fisheries and Aquatic Sciences',
    'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY': 'College of Information and Computing Science',
    'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT': 'College of Hospitality Management',
    'BACHELOR OF SECONDARY EDUCATION': 'College of Teacher Education',
    'BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM': 'College of Business, Entrepreneurship and Accountancy',
    'BACHELOR OF ELEMENTARY EDUCATION': 'College of Teacher Education',
};

function updateCollegeDepartmentForForm(form) {
    if (!form) return;
    const courseField = form.querySelector('[name="course"]');
    const collegeField = form.querySelector('[name="college_department"]');
    if (!courseField || !collegeField) return;
    collegeField.value = collegeDepartmentMap[courseField.value] || '';
    collegeField.readOnly = true;
}

document.addEventListener('DOMContentLoaded', function(){
    const academicScholarshipIdMap = window.academicScholarshipIdMap || {};
    const nonAcademicScholarshipIdMap = window.nonAcademicScholarshipIdMap || {};
    const setSelectedScholarship = (title, map, hiddenIdField, nameField, typeField, card) => {
        const selected = title && (map[title] || {
            name: title,
            type: card?.dataset.scholarshipType || card?.querySelector('.card-names .prog')?.textContent?.trim() || '',
        });

        if (!selected) {
            if (hiddenIdField) hiddenIdField.value = '';
            return;
        }

        const selectedName = selected.name;
        const selectedType = selected.type;
        const scholarshipIdMap = map === academicScholarshipMap
            ? academicScholarshipIdMap
            : nonAcademicScholarshipIdMap;
        if (nameField) nameField.value = selectedName;
        if (typeField) typeField.value = selectedType;
        if (map === nonAcademicScholarshipMap) updateNonAcademicRequirementsNote(selectedName);
        if (hiddenIdField) {
            hiddenIdField.value = card?.dataset.scholarshipId || scholarshipIdMap[selectedName] || '';
        }

        const form = hiddenIdField?.form;
        if (form) form.dataset.requiredDocuments = card?.dataset.requiredDocuments || '[]';
        if (form) form.dataset.requiredDocumentGroups = JSON.stringify(
            (map === academicScholarshipMap && selectedName === 'University Based Scholarship')
                ? ['certificate_of_grades', 'certificate_of_enrollment']
                : (map === academicScholarshipMap
                    ? ['certificate_of_grades']
                    : (selectedName === 'Athletic Scholarship'
                                ? ['certificate_of_sports_coordinator', 'certificate_of_adviser']
                                : (['Socio-Cultural Scholarship', 'Differently Abled Persons', 'Open Heart Scholarship', 'Talamayan Scholarship', 'Campus Student Publication', 'CSU Aparri Alumni Association'].includes(selectedName)
                                    ? ['uploads']
                                    : [])))
        );

        const athleticFieldGroup = document.querySelector('.athletic-only-fields');
        const genericUploadGroup = document.getElementById('nonAcademicGenericUploadGroup');
        if (athleticFieldGroup) {
            athleticFieldGroup.style.display = selectedName === 'Athletic Scholarship' ? 'flex' : 'none';
        }
        if (genericUploadGroup) {
            genericUploadGroup.style.display = selectedName === 'Athletic Scholarship' ? 'none' : 'block';
        }
    };

    const academicForm = document.getElementById('academicApplyForm');
    const academicModal = document.getElementById('academicApplyModal');
    if (academicForm && academicModal) {
        bindApplicationInputFormatting(academicForm);
        initializeMobileFloatingLabels(academicForm);
        bindDraftAutosave(academicForm, 'academic');
        bindDraftClearOnSubmit(academicForm, 'academic');
        const courseField = academicForm.querySelector('[name="course"]');
        if (courseField) {
            courseField.addEventListener('change', () => updateCollegeDepartmentForForm(academicForm));
        }
        updateCollegeDepartmentForForm(academicForm);
        const currentStep = Number(academicForm.dataset.currentAcademicStep || 1);
        updateAcademicStepUI(currentStep);

        const prevBtn = academicModal.querySelector('[data-academic-step="prev"]');
        const nextBtn = academicModal.querySelector('[data-academic-step="next"]');

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                const current = Number(academicForm.dataset.currentAcademicStep || 1);
                if (current > 1) {
                    updateAcademicStepUI(current - 1);
                } else if (window.matchMedia('(max-width: 768px)').matches) {
                    document.getElementById('academicApplyModalClose')?.click();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const current = Number(academicForm.dataset.currentAcademicStep || 1);
                if (validateAcademicCurrentStep()) {
                    updateAcademicStepUI(current + 1);
                }
            });
        }

        academicForm.addEventListener('submit', function(e) {
            if (academicForm.dataset.confirmedSubmit === 'true') {
                delete academicForm.dataset.confirmedSubmit;
                return;
            }

            const current = Number(academicForm.dataset.currentAcademicStep || 1);
            const totalSteps = academicForm.querySelectorAll('.academic-step-panel:not([data-reapplication-hidden="true"])').length || 3;
            if (current < totalSteps) {
                e.preventDefault();
                updateAcademicStepUI(totalSteps);
                return;
            }

            if (!validateAcademicCurrentStep()) {
                e.preventDefault();
                return;
            }

            if (!validateRequiredDocumentsForForm(academicForm)) {
                e.preventDefault();
                return;
            }

            if (!validateFilesForSubmission(academicForm, fileValidationRules)) {
                e.preventDefault();
            }
        });
    }

    const nonAcademicForm = document.getElementById('applyForm');
    const nonAcademicModal = document.getElementById('applyModal');
    if (nonAcademicForm && nonAcademicModal) {
        bindApplicationInputFormatting(nonAcademicForm);
        initializeMobileFloatingLabels(nonAcademicForm);
        initializeMobileUploadFilenames(nonAcademicForm);
        bindDraftAutosave(nonAcademicForm, 'non-academic');
        bindDraftClearOnSubmit(nonAcademicForm, 'non-academic');

        const familySiblingsBody = nonAcademicForm.querySelector('#familySiblingsBody');
        const addFamilySiblingButton = nonAcademicForm.querySelector('#addFamilySibling');
        let familySiblingIndex = 0;
        const updateFamilySiblingButton = () => {
            if (addFamilySiblingButton && familySiblingsBody) {
                addFamilySiblingButton.disabled = familySiblingsBody.rows.length >= 10;
            }
        };

        addFamilySiblingButton?.addEventListener('click', () => {
            if (!familySiblingsBody || familySiblingsBody.rows.length >= 10) return;
            const rowIndex = familySiblingIndex++;
            const row = document.createElement('tr');
            row.innerHTML = `<td><label class="mobile-sibling-field-label" hidden>Name</label><input class="input" type="text" name="family_background[siblings][${rowIndex}][name]" placeholder="Full Name"></td>
                <td><label class="mobile-sibling-field-label" hidden>Age</label><input class="input" type="number" min="0" name="family_background[siblings][${rowIndex}][age]" placeholder="Age"></td>
                <td><label class="mobile-sibling-field-label" hidden>Marital Status</label><select class="input" name="family_background[siblings][${rowIndex}][marital_status]"><option value="">Select...</option><option>Single</option><option>Married</option><option>Widowed</option><option>Divorced</option></select></td>
                <td><label class="mobile-sibling-field-label" hidden>Employment/Occupation</label><input class="input" type="text" name="family_background[siblings][${rowIndex}][occupation]" placeholder="Job or 'Student'"></td>
                <td><button class="family-sibling-remove" type="button" aria-label="Remove sibling" title="Remove sibling"><img src="${siblingDeleteIconUrl}" alt="" width="18" height="18"></button></td>`;
            row.querySelector('.family-sibling-remove')?.addEventListener('click', () => {
                row.remove();
                updateFamilySiblingButton();
            });
            familySiblingsBody.appendChild(row);
            initializeMobileFloatingLabels(row);
            updateFamilySiblingButton();
        });
        updateFamilySiblingButton();

        const disabilityRadios = nonAcademicForm.querySelectorAll('[name="family_background[has_differently_abled_member]"]');
        disabilityRadios.forEach(radio => radio.addEventListener('change', () => updateFamilyDisabilityVisibility(nonAcademicForm)));
        updateFamilyDisabilityVisibility(nonAcademicForm);

        const courseField = nonAcademicForm.querySelector('[name="course"]');
        if (courseField) {
            courseField.addEventListener('change', () => updateCollegeDepartmentForForm(nonAcademicForm));
        }
        updateCollegeDepartmentForForm(nonAcademicForm);
        const currentStep = Number(nonAcademicForm.dataset.nonAcademicStep || 1);
        updateNonAcademicStepUI(currentStep);

        const prevBtn = nonAcademicModal.querySelector('[data-non-academic-step="prev"]');
        const nextBtn = nonAcademicModal.querySelector('[data-non-academic-step="next"]');
        const familyBackgroundNext = nonAcademicForm.querySelector('#familyBackgroundNext');

        familyBackgroundNext?.addEventListener('click', () => {
            const familyPage = Number(nonAcademicForm.querySelector('[data-non-academic-step="3"]')?.dataset.familyBackgroundPage || 1);
            if (familyPage >= 3) {
                if (nonAcademicForm.dataset.familyBackgroundOnlyReapplication === 'true') return;
                updateNonAcademicStepUI(Number(nonAcademicForm.dataset.nonAcademicStep || 1) + 1);
                return;
            }
            updateFamilyBackgroundPage(nonAcademicForm, familyPage + 1);
            updateNonAcademicStepUI(Number(nonAcademicForm.dataset.nonAcademicStep || 1));
        });

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                const current = Number(nonAcademicForm.dataset.nonAcademicStep || 1);
                const familyPanel = nonAcademicForm.querySelector('[data-non-academic-step="3"]');
                const visiblePanels = Array.from(nonAcademicForm.querySelectorAll('.academic-step-panel'))
                    .filter(panel => panel.dataset.reapplicationHidden !== 'true');
                const isFamilyStep = visiblePanels[current - 1] === familyPanel;
                if (isFamilyStep) {
                    const familyPage = Number(familyPanel.dataset.familyBackgroundPage || 1);
                    if (familyPage > 1) {
                        updateFamilyBackgroundPage(nonAcademicForm, familyPage - 1);
                        updateNonAcademicStepUI(current);
                    }
                    else if (current > 1) updateNonAcademicStepUI(current - 1);
                    return;
                }
                if (current > 1) {
                    updateNonAcademicStepUI(current - 1);
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const current = Number(nonAcademicForm.dataset.nonAcademicStep || 1);
                const totalSteps = nonAcademicForm.querySelectorAll('.academic-step-panel:not([data-reapplication-hidden="true"])').length || 3;
                const familyPanel = nonAcademicForm.querySelector('[data-non-academic-step="3"]');
                const visiblePanels = Array.from(nonAcademicForm.querySelectorAll('.academic-step-panel'))
                    .filter(panel => panel.dataset.reapplicationHidden !== 'true');
                if (visiblePanels[current - 1] === familyPanel) {
                    const familyPage = Number(nonAcademicForm.querySelector('[data-non-academic-step="3"]')?.dataset.familyBackgroundPage || 1);
                    if (familyPage >= 3) updateNonAcademicStepUI(current + 1);
                    else updateFamilyBackgroundPage(nonAcademicForm, familyPage + 1);
                    return;
                }
                if (current < totalSteps && validateNonAcademicCurrentStep()) {
                    updateNonAcademicStepUI(current + 1);
                }
            });
        }

        nonAcademicForm.addEventListener('submit', function(e) {
            if (nonAcademicForm.dataset.confirmedSubmit === 'true') {
                delete nonAcademicForm.dataset.confirmedSubmit;
                return;
            }

            const current = Number(nonAcademicForm.dataset.nonAcademicStep || 1);
            const totalSteps = nonAcademicForm.querySelectorAll('.academic-step-panel:not([data-reapplication-hidden="true"])').length || 3;
            if (current < totalSteps) {
                e.preventDefault();
                updateNonAcademicStepUI(totalSteps);
                return;
            }

            if (!validateNonAcademicCurrentStep()) {
                e.preventDefault();
                return;
            }

            if (!validateRequiredDocumentsForForm(nonAcademicForm)) {
                e.preventDefault();
                return;
            }

            if (!validateFilesForSubmission(nonAcademicForm, fileValidationRules)) {
                e.preventDefault();
            }
        });
    }

    // Academic scholarships mapping
    const academicScholarshipMap = {
        'Cagayan Valley Assoc. of Hawaii': { name: 'Cagayan Valley Assoc. of Hawaii', type: 'International Educational Grant' },
        'University Based Scholarship': { name: 'University Based Scholarship', type: 'Academic Excellence – In-House' },
        'ACEF – GIAHEP': { name: 'ACEF – GIAHEP', type: 'Agricultural Credit & Education Fund' },
        'DOST SEI Project Strand': { name: 'DOST SEI Project Strand', type: 'Graduate School Scholarship' },
        'Tulong Dunong Program': { name: 'Tulong Dunong Program', type: 'Kuya Win Scholarship Program' },
        'LGU Aparri': { name: 'LGU Aparri', type: 'Local Government Unit Scholarship' },
        'Natalged A Lasam': { name: 'Natalged A Lasam', type: 'Educational Assistance Grant' },
        'Andres Tamayo Scholarship': { name: 'Andres Tamayo Scholarship', type: 'Private Foundation Grant' },
        'Gokongwei Brothers Foundation': { name: 'Gokongwei Brothers Foundation', type: 'Foundation Scholarship Program' }
    };

    // Non-academic scholarships mapping
    const nonAcademicScholarshipMap = {
        'Athletic Scholarship': { name: 'Athletic Scholarship', type: 'Sports Excellence Program In-House' },
        "Employees' Grant": { name: "Employees' Grant", type: "Employees' Grant In-House" },
        'Socio-Cultural Scholarship': { name: 'Socio-Cultural Scholarship', type: 'Arts & Culture Program In-House' },
        'Campus Student Publication': { name: 'Campus Student Publication', type: 'University Based Scholarship In-House' },
        'Differently Abled Persons': { name: 'Differently Abled Persons', type: 'University Based Scholarship In-House' },
        'Student Council Federation Grant': { name: 'Student Council Federation Grant', type: 'University Based Scholarship In-House' },
        'CSU Aparri Alumni Association': { name: 'CSU Aparri Alumni Association', type: 'Alumni Grant Program In-House' },
        'Tertiary Education Subsidy': { name: 'Tertiary Education Subsidy', type: 'UniFAST – TES Program Government Based' },
        'Agkaykaysa': { name: 'Agkaykaysa', type: 'Regional Solidarity Grant Government Based' },
        'Open Heart Scholarship': { name: 'Open Heart Scholarship', type: 'Private Foundation Grant Private Based' },
        'Talamayan Scholarship': { name: 'Talamayan Scholarship', type: 'Community Support Grant Private Based' }
    };

    // CHANGE: Display-only requirements for selected non-academic scholarships.
    const nonAcademicRequirementsMap = {
        'Campus Student Publication': ['Certificate from the adviser'],
        'Student Council Federation Grant': ['COE (Present semester)'],
        'Differently Abled Persons': ['Student ID (front and back), PDF format'],
        'Socio-Cultural Scholarship': ['COE (Present semester)'],
        "Employees' Grant": ['COE (Present semester)'],
        'Tulong Dunong Program': ['COE (Present semester)'],
        'Tertiary Education Subsidy': [
            'COE (Present semester)',
            'Student ID (front and back)',
            'ATM',
            '3x specimen signature',
            'Contact number',
            'All documents must be in PDF format'
        ],
        'CSU Aparri Alumni Association': ['COE (Present semester)'],
        'Agkaykaysa': ['COE (Present semester)']
    };

    function updateNonAcademicRequirementsNote(scholarshipName) {
        const note = document.getElementById('nonAcademicRequirementsNote');
        const requirements = nonAcademicRequirementsMap[scholarshipName];
        if (!note) return;

        if (!requirements) {
            note.hidden = true;
            note.style.display = 'none';
            note.innerHTML = '';
            return;
        }

        note.innerHTML = `<strong>Required documents:</strong><ul style="margin:4px 0 0;padding-left:20px;">${requirements.map(item => `<li>${item}</li>`).join('')}</ul>`;
        note.hidden = false;
        note.style.display = 'block';
    }

    // Handle academic scholarship cards
    document.querySelectorAll('#acad-p1 .btn-apply, #acad-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', async function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const description = card?.dataset.description || '';
            const selectedId = card?.dataset.scholarshipId || window.academicScholarshipIdMap?.[title] || '';
            const application = findAppliedScholarship(selectedId, 'academic');
            if (application?.locked) return;
            const nameInput = document.getElementById('academic_scholarship_name');
            const typeInput = document.getElementById('academic_scholarship_type');
            const scholarshipIdInput = document.getElementById('academic_scholarship_id');
            const profile = application?.reopen ? application.profile : null;

            if (nameInput) nameInput.value = '';
            if (typeInput) typeInput.value = '';
            if (scholarshipIdInput) scholarshipIdInput.value = '';

            if (title) {
                setSelectedScholarship(title, academicScholarshipMap, scholarshipIdInput, nameInput, typeInput, card);
            }

            const enrollmentSection = document.getElementById('academicEnrollmentSection');
            if (enrollmentSection) {
                enrollmentSection.style.display = title === 'University Based Scholarship' ? 'block' : 'none';
            }

            if (title) {
                const form = document.getElementById('academicApplyForm');
                const selectedName = nameInput?.value || title;

                if (profile) {
                    openAcademicApplyModal();
                    prepareReapplication(title, 'academic', selectedId);
                    return;
                }

                await hydrateFormOpenState(form, 'academic', scholarshipIdInput?.value || selectedId, selectedName);
            }

            openScholarshipDetailsModal(title, description, 'academic', card);
        });
    });

    // Handle non-academic scholarship cards
    document.querySelectorAll('#non-p1 .btn-apply, #non-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', async function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const description = card?.dataset.description || '';
            const selectedId = card?.dataset.scholarshipId || window.nonAcademicScholarshipIdMap?.[title] || '';
            const application = findAppliedScholarship(selectedId, 'non-academic');
            if (application?.locked) return;
            const nameInput = document.getElementById('scholarship_name');
            const typeInput = document.getElementById('scholarship_type');
            const scholarshipIdInput = document.getElementById('scholarship_id');
            const profile = application?.reopen ? application.profile : null;

            if (nameInput) nameInput.value = '';
            if (typeInput) typeInput.value = '';
            if (scholarshipIdInput) scholarshipIdInput.value = '';

            if (title) {
                setSelectedScholarship(title, nonAcademicScholarshipMap, scholarshipIdInput, nameInput, typeInput, card);
            }

            const nonAcademicIDLabel = document.getElementById('nonAcademicIDLabel');
            const nonAcademicUploadsInput = document.getElementById('nonAcademicUploadsInput');
            if (nonAcademicIDLabel) {
                if (title === 'Open Heart Scholarship' || title === 'Talamayan Scholarship') {
                    nonAcademicIDLabel.style.display = 'block';
                    if (nonAcademicUploadsInput) nonAcademicUploadsInput.accept = '.pdf,.doc,.docx';
                } else {
                    nonAcademicIDLabel.style.display = 'none';
                    if (nonAcademicUploadsInput) nonAcademicUploadsInput.accept = '.pdf,.docx,.doc,.jpg,.jpeg,.png';
                }
            }

            if (title) {
                const form = document.getElementById('applyForm');
                const selectedName = nameInput?.value || title;

                if (profile) {
                    openApplyModal();
                    prepareReapplication(title, 'non', selectedId);
                    return;
                }

                await hydrateFormOpenState(form, 'non-academic', scholarshipIdInput?.value || selectedId, selectedName);
            }

            openScholarshipDetailsModal(title, description, 'non', card);
        });
    });

    // Non-academic form close handlers
    function resetAndCloseNonAcademic(){
        const form = document.getElementById('applyForm');
        if (form) {
            delete form.dataset.reapplicationPrepared;
            const scholarshipId = form.querySelector('[name="scholarship_id"]')?.value || '';
            const scholarshipName = form.querySelector('[name="scholarship_name"]')?.value || '';
            saveDraft(form, 'non-academic', scholarshipId, scholarshipName);
            form.querySelectorAll('[data-reapplication-preserved]').forEach(input => input.remove());
            form.querySelectorAll('.academic-step-panel, .academic-progress-step, [data-reapplication-document-group]').forEach(element => {
                element.hidden = false;
                delete element.dataset.reapplicationHidden;
            });
            delete form.dataset.reapplicationActive;
            delete form.dataset.familyBackgroundOnlyReapplication;
            form.reset();
            form.querySelector('#familySiblingsBody')?.replaceChildren();
            updateFamilyBackgroundPage(form, 1);
            const addFamilySiblingButton = form.querySelector('#addFamilySibling');
            if (addFamilySiblingButton) addFamilySiblingButton.disabled = false;
            form.dataset.nonAcademicStep = '1';
            updateNonAcademicStepUI(1);
            const collegeField = form.querySelector('[name="college_department"]');
            if (collegeField) {
                collegeField.value = '';
                collegeField.readOnly = true;
            }
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
                if (input.name !== 'age') input.readOnly = false;
                if (input.type === 'file') input.required = false;
            });
            const resetCollegeField = form.querySelector('[name="college_department"]');
            if (resetCollegeField) {
                resetCollegeField.value = '';
                resetCollegeField.readOnly = true;
            }
            form.querySelectorAll('[name="family_background[has_differently_abled_member]"]').forEach(radio => {
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
        const nameInput = document.getElementById('scholarship_name');
        const typeInput = document.getElementById('scholarship_type');
        const scholarshipIdInput = document.getElementById('scholarship_id');
        if (nameInput) nameInput.value = '';
        if (typeInput) typeInput.value = '';
        if (scholarshipIdInput) scholarshipIdInput.value = '';
        updateNonAcademicRequirementsNote('');
        const nonAcademicIDLabel = document.getElementById('nonAcademicIDLabel');
        const nonAcademicUploadsInput = document.getElementById('nonAcademicUploadsInput');
        if (nonAcademicIDLabel) nonAcademicIDLabel.style.display = 'none';
        if (nonAcademicUploadsInput) nonAcademicUploadsInput.accept = '.pdf,.docx,.doc,.jpg,.jpeg,.png';
        closeApplyModal();
    }

    const detailsBackBtn = document.getElementById('scholarshipDetailsModalBack');
    if (detailsBackBtn) detailsBackBtn.addEventListener('click', closeScholarshipDetailsModal);
    const detailsCloseBtn = document.getElementById('scholarshipDetailsModalClose');
    if (detailsCloseBtn) detailsCloseBtn.addEventListener('click', closeScholarshipDetailsModal);
    const detailsModal = document.getElementById('scholarshipDetailsModal');
    if (detailsModal) detailsModal.addEventListener('click', function(e){ if (e.target === detailsModal) closeScholarshipDetailsModal(); });

    const detailsApplyBtn = document.getElementById('detailsApplyBtn');
    if (detailsApplyBtn) detailsApplyBtn.addEventListener('click', async function(){
        const modal = document.getElementById('scholarshipDetailsModal');
        const modalType = modal?.dataset.modalType || 'non';
        const title = document.getElementById('detailsScholarshipTitle')?.textContent?.trim() || '';
        const scholarshipId = modal?.dataset.scholarshipId || '';
        const grantType = modal?.dataset.grantType || (modalType === 'academic' ? 'academic' : 'non-academic');
        const application = findAppliedScholarship(scholarshipId, grantType);
        if (application?.locked) return;
        const profile = application?.reopen ? application.profile : null;
        closeScholarshipDetailsModal();

        if (modalType === 'academic') {
            const form = document.getElementById('academicApplyForm');
            const scholarshipNameInput = document.getElementById('academic_scholarship_name');
            const scholarshipIdInput = document.getElementById('academic_scholarship_id');

            if (profile) {
                openAcademicApplyModal();
                prepareReapplication(title, 'academic', scholarshipId);
                return;
            }

            openAcademicApplyModal();
            await hydrateFormOpenState(form, 'academic', scholarshipIdInput?.value || '', scholarshipNameInput?.value || title);
            return;
        }

        const form = document.getElementById('applyForm');
        const scholarshipNameInput = document.getElementById('scholarship_name');
        const scholarshipIdInput = document.getElementById('scholarship_id');

        if (profile) {
            openApplyModal();
            prepareReapplication(title, 'non', scholarshipId);
            return;
        }

        openApplyModal();
        await hydrateFormOpenState(form, 'non-academic', scholarshipIdInput?.value || '', scholarshipNameInput?.value || title);
    });

    const closeBtn = document.getElementById('applyModalClose');
    if (closeBtn) closeBtn.addEventListener('click', resetAndCloseNonAcademic);
    const modal = document.getElementById('applyModal');
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) resetAndCloseNonAcademic(); });

    // Academic form close handlers
    function resetAndCloseAcademic(){
        const form = document.getElementById('academicApplyForm');
        if (form) {
            delete form.dataset.reapplicationPrepared;
            const scholarshipId = form.querySelector('[name="scholarship_id"]')?.value || '';
            const scholarshipName = form.querySelector('[name="scholarship_name"]')?.value || '';
            saveDraft(form, 'academic', scholarshipId, scholarshipName);
            form.querySelectorAll('[data-reapplication-preserved]').forEach(input => input.remove());
            form.querySelectorAll('.academic-step-panel, .academic-progress-step, [data-reapplication-document-group]').forEach(element => {
                element.hidden = false;
                delete element.dataset.reapplicationHidden;
            });
            delete form.dataset.reapplicationActive;
            form.reset();
            const collegeField = form.querySelector('[name="college_department"]');
            if (collegeField) {
                collegeField.value = '';
                collegeField.readOnly = true;
            }
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
                input.readOnly = false;
                if (input.type === 'file') input.required = false;
            });
            const resetCollegeField = form.querySelector('[name="college_department"]');
            if (resetCollegeField) {
                resetCollegeField.value = '';
                resetCollegeField.readOnly = true;
            }
            form.dataset.currentAcademicStep = '1';
            updateAcademicStepUI(1);
        }
        const nameInput = document.getElementById('academic_scholarship_name');
        const typeInput = document.getElementById('academic_scholarship_type');
        const scholarshipIdInput = document.getElementById('academic_scholarship_id');
        const enrollmentSection = document.getElementById('academicEnrollmentSection');
        if (nameInput) nameInput.value = '';
        if (typeInput) typeInput.value = '';
        if (scholarshipIdInput) scholarshipIdInput.value = '';
        if (enrollmentSection) { enrollmentSection.style.display = 'none'; }
        closeAcademicApplyModal();
    }

    const academicCloseBtn = document.getElementById('academicApplyModalClose');
    if (academicCloseBtn) academicCloseBtn.addEventListener('click', resetAndCloseAcademic);
    if (academicModal) academicModal.addEventListener('click', function(e){ if (e.target === academicModal) resetAndCloseAcademic(); });

    // File validation for academic form
    const fileValidationRules = {
        document: {
            extensions: ['pdf', 'docx', 'doc'],
            maxSize: 2 * 1024 * 1024,
            displaySize: '2MB'
        },
        image: {
            extensions: ['jpg', 'jpeg', 'png'],
            maxSize: 1 * 1024 * 1024,
            displaySize: '1MB'
        }
    };

    function getFileCategory(extension) {
        const ext = extension.toLowerCase();
        if (fileValidationRules.document.extensions.includes(ext)) {
            return 'document';
        }
        if (fileValidationRules.image.extensions.includes(ext)) {
            return 'image';
        }
        return null;
    }

    // Close popup handlers for academic error popup
    const academicErrorPopup = document.getElementById('academicErrorPopup');
    if (academicErrorPopup) {
        academicErrorPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAcademicErrorPopup();
            }
        });
    }

    // Close popup handlers for academic success popup
    const academicSuccessPopup = document.getElementById('academicSuccessPopup');
    if (academicSuccessPopup) {
        academicSuccessPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAcademicSuccessPopup();
            }
        });
    }
});

const confirmYesBtn = document.getElementById('confirmYesBtn');
const confirmCancelBtn = document.getElementById('confirmCancelBtn');

if (confirmYesBtn) {
    confirmYesBtn.addEventListener('click', function () {
        if (typeof confirmResolve === 'function') confirmResolve(true);
        if (typeof closeConfirm === 'function') closeConfirm();
    });
}

if (confirmCancelBtn) {
    confirmCancelBtn.addEventListener('click', function () {
        if (typeof confirmResolve === 'function') confirmResolve(false);
        if (typeof closeConfirm === 'function') closeConfirm();
    });
}