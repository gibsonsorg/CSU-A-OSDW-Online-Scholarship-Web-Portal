// State management for pagination
const state = { acad: 1, non: 1 };

const maxPages = { acad: 2, non: 2 };

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

function prepareReapplication(title, modalType) {
    const profile = (window.reapplicationProfiles || {})[title];
    if (!profile) return;

    const form = document.getElementById(modalType === 'academic' ? 'academicApplyForm' : 'applyForm');
    if (!form) return;

    form.querySelectorAll('[data-reapplication-preserved]').forEach(input => input.remove());

    const values = {
        first_name: profile.first_name,
        middle_name: profile.middle_name,
        last_name: profile.last_name,
        email: profile.email,
        student_id_number: profile.student_id_number,
        id_number: profile.id_number || profile.student_id_number,
        course: profile.course,
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

    const applicationIdInput = form.querySelector('[name="application_id"]');
    const studentProfileIdInput = form.querySelector('[name="student_profile_id"]');
    if (applicationIdInput) applicationIdInput.value = profile.application_id || '';
    if (studentProfileIdInput) studentProfileIdInput.value = profile.id || '';

    const studentFields = ['first_name', 'middle_name', 'last_name', 'email', 'student_id_number', 'id_number', 'course', 'year_level', 'program'];
    const personalFields = ['birthdate', 'birthplace', 'sex', 'status', 'religion', 'contact_number', 'home_address'];
    const sectionStatus = {
        student: profile.student_information_status,
        personal: profile.personal_information_status,
        requirements: profile.scholarship_requirements_status,
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

    const sectionPanels = form.querySelectorAll('.academic-step-panel');
    sectionPanels.forEach((panel, index) => {
        const status = [sectionStatus.student, sectionStatus.personal, sectionStatus.requirements][index];
        const visible = status === 'rejected';
        panel.dataset.reapplicationHidden = visible ? 'false' : 'true';
        panel.hidden = !visible;
        panel.querySelectorAll('input, select, textarea').forEach(input => {
            const preserveScholarshipValue = ['scholarship_name', 'scholarship_type'].includes(input.name)
                && sectionStatus.requirements !== 'rejected';
            if (input.type !== 'hidden' && !input.dataset.reapplicationPreserved && !preserveScholarshipValue) {
                input.disabled = !visible;
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

    const rejectedSection = [sectionStatus.student, sectionStatus.personal, sectionStatus.requirements]
        .findIndex(status => status === 'rejected');
    const firstRejectedSection = rejectedSection >= 0 ? rejectedSection + 1 : 1;
    form.dataset.reapplicationActive = 'true';
    form.querySelectorAll('.academic-progress-step').forEach((step, index) => {
        const visible = [sectionStatus.student, sectionStatus.personal, sectionStatus.requirements][index] === 'rejected';
        step.dataset.reapplicationHidden = visible ? 'false' : 'true';
        step.hidden = !visible;
    });
    if (modalType === 'academic') updateAcademicStepUI(firstRejectedSection);
    else updateNonAcademicStepUI(firstRejectedSection);
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

        modal.dataset.modalType = modalType;
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

    panels.forEach(panel => panel.classList.remove('active'));
    visiblePanels.forEach((panel, index) => {
        panel.classList.toggle('active', index + 1 === currentStep);
    });

    if (stepCounter) stepCounter.textContent = currentStep;

    progressSteps.forEach(step => step.classList.remove('active', 'completed'));
    visibleProgressSteps.forEach((step, index) => {
        const stepIndex = index + 1;
        step.classList.toggle('active', stepIndex === currentStep);
        step.classList.toggle('completed', stepIndex < currentStep);
    });

    if (prevBtn) {
        prevBtn.classList.toggle('is-hidden', currentStep === 1);
        prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        prevBtn.style.display = currentStep === 1 ? 'none' : '';
        prevBtn.setAttribute('aria-hidden', currentStep === 1 ? 'true' : 'false');
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

    panels.forEach(panel => panel.classList.remove('active'));
    visiblePanels.forEach((panel, index) => {
        panel.classList.toggle('active', index + 1 === currentStep);
    });

    if (stepCounter) stepCounter.textContent = currentStep;

    progressSteps.forEach(step => step.classList.remove('active', 'completed'));
    visibleProgressSteps.forEach((step, index) => {
        const stepIndex = index + 1;
        step.classList.toggle('active', stepIndex === currentStep);
        step.classList.toggle('completed', stepIndex < currentStep);
    });

    if (prevBtn) {
        prevBtn.classList.toggle('is-hidden', currentStep === 1);
        prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        prevBtn.style.display = currentStep === 1 ? 'none' : '';
        prevBtn.setAttribute('aria-hidden', currentStep === 1 ? 'true' : 'false');
    }

    if (nextBtn) {
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
    }

    if (submitBtn) {
        submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }
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

document.addEventListener('DOMContentLoaded', function(){
    const scholarshipIdMap = window.scholarshipIdMap || {};
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
        if (nameField) nameField.value = selectedName;
        if (typeField) typeField.value = selectedType;
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
        const currentStep = Number(academicForm.dataset.currentAcademicStep || 1);
        updateAcademicStepUI(currentStep);

        const prevBtn = academicModal.querySelector('[data-academic-step="prev"]');
        const nextBtn = academicModal.querySelector('[data-academic-step="next"]');

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                const current = Number(academicForm.dataset.currentAcademicStep || 1);
                if (current > 1) {
                    updateAcademicStepUI(current - 1);
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
        const currentStep = Number(nonAcademicForm.dataset.nonAcademicStep || 1);
        updateNonAcademicStepUI(currentStep);

        const prevBtn = nonAcademicModal.querySelector('[data-non-academic-step="prev"]');
        const nextBtn = nonAcademicModal.querySelector('[data-non-academic-step="next"]');

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                const current = Number(nonAcademicForm.dataset.nonAcademicStep || 1);
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

    // Handle academic scholarship cards
    document.querySelectorAll('#acad-p1 .btn-apply, #acad-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const description = card?.dataset.description || '';
            const nameInput = document.getElementById('academic_scholarship_name');
            const typeInput = document.getElementById('academic_scholarship_type');
            const scholarshipIdInput = document.getElementById('academic_scholarship_id');

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

            openScholarshipDetailsModal(title, description, 'academic', card);
        });
    });

    // Handle non-academic scholarship cards
    document.querySelectorAll('#non-p1 .btn-apply, #non-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const description = card?.dataset.description || '';
            const nameInput = document.getElementById('scholarship_name');
            const typeInput = document.getElementById('scholarship_type');
            const scholarshipIdInput = document.getElementById('scholarship_id');

            if (nameInput) nameInput.value = '';
            if (typeInput) typeInput.value = '';
            if (scholarshipIdInput) scholarshipIdInput.value = '';

            if (title) {
                setSelectedScholarship(title, nonAcademicScholarshipMap, scholarshipIdInput, nameInput, typeInput, card);
            }

            // Show ID label for Open Heart and Talamayan only
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

            openScholarshipDetailsModal(title, description, 'non', card);
        });
    });

    // Non-academic form close handlers
    function resetAndCloseNonAcademic(){
        const form = document.getElementById('applyForm');
        if (form) {
            form.querySelectorAll('[data-reapplication-preserved]').forEach(input => input.remove());
            form.querySelectorAll('.academic-step-panel, .academic-progress-step, [data-reapplication-document-group]').forEach(element => {
                element.hidden = false;
                delete element.dataset.reapplicationHidden;
            });
            delete form.dataset.reapplicationActive;
            form.reset();
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
                input.readOnly = false;
                if (input.type === 'file') input.required = false;
            });
        }
        const nameInput = document.getElementById('scholarship_name');
        const typeInput = document.getElementById('scholarship_type');
        const scholarshipIdInput = document.getElementById('scholarship_id');
        if (nameInput) nameInput.value = '';
        if (typeInput) typeInput.value = '';
        if (scholarshipIdInput) scholarshipIdInput.value = '';
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
    if (detailsApplyBtn) detailsApplyBtn.addEventListener('click', function(){
        closeScholarshipDetailsModal();
        const modal = document.getElementById('scholarshipDetailsModal');
        const modalType = modal?.dataset.modalType || 'non';
        const title = document.getElementById('detailsScholarshipTitle')?.textContent?.trim() || '';
        if (modalType === 'academic') {
            openAcademicApplyModal();
            prepareReapplication(title, 'academic');
        } else {
            openApplyModal();
            prepareReapplication(title, 'non');
        }
    });

    const closeBtn = document.getElementById('applyModalClose');
    if (closeBtn) closeBtn.addEventListener('click', resetAndCloseNonAcademic);
    const modal = document.getElementById('applyModal');
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) resetAndCloseNonAcademic(); });

    // Academic form close handlers
    function resetAndCloseAcademic(){
        const form = document.getElementById('academicApplyForm');
        if (form) {
            form.querySelectorAll('[data-reapplication-preserved]').forEach(input => input.remove());
            form.querySelectorAll('.academic-step-panel, .academic-progress-step, [data-reapplication-document-group]').forEach(element => {
                element.hidden = false;
                delete element.dataset.reapplicationHidden;
            });
            delete form.dataset.reapplicationActive;
            form.reset();
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
                input.readOnly = false;
                if (input.type === 'file') input.required = false;
            });
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