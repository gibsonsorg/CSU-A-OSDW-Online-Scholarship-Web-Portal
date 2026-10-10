(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let currentAppId = null;
    let currentStep = 1;
    let currentFamilyPage = 1;
    let profileStepCount = 3;
    let currentApplication = null;
    let oldGrantRecordsReturnFocus = null;
    let oldGrantRecordsPreviousOverflow = '';

    const escapeHtml = value => String(value ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    function postJson(url, data = {}) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify(data)
        }).then(async response => {
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.message || 'Request failed');
            return result;
        });
    }

    function field(label, value, name) {
        return `<div class="academic-review-field"><label>${label}</label><input class="input" data-field="${name}" value="${escapeHtml(value || 'N/A')}" readonly></div>`;
    }

    function calculateAge(birthdate) {
        const match = String(birthdate ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return '';
        const year = Number(match[1]);
        const month = Number(match[2]) - 1;
        const day = Number(match[3]);
        const today = new Date();
        let age = today.getFullYear() - year;
        if (today.getMonth() < month || (today.getMonth() === month && today.getDate() < day)) age--;
        return age >= 0 ? age : '';
    }

    function documentGroup(label, files, group, reviews, showLabel = true) {
        if (!Array.isArray(files) || !files.length) return '';
        return files.map((file, index) => {
            const url = `/admin/applications/${encodeURIComponent(currentAppId)}/document/${encodeURIComponent(group)}/${index}`;
            const fileName = String(file || '').split('/').pop();
            const isPdf = /\.pdf$/i.test(fileName || '');
            const linkLabel = isPdf ? 'View File' : 'Download to View';
            const labelMarkup = showLabel
                ? `<label>${escapeHtml(label)}${files.length > 1 ? ` ${index + 1}` : ''}</label>`
                : '';
            return `<div class="document-review full">
                ${labelMarkup}
                <div class="document-actions">
                    <a class="document-view" href="${url}" data-document-type="${isPdf ? 'pdf' : 'other'}" ${isPdf ? '' : 'target="_blank" rel="noopener noreferrer"'}>${linkLabel}</a>
                </div>
            </div>`;
        }).join('');
    }

    function openDocumentPreviewModal(url, title = 'Document Preview') {
        const modal = document.getElementById('documentPreviewModal');
        if (!modal) return;
        const frame = document.getElementById('documentPreviewFrame');
        const titleNode = document.getElementById('documentPreviewTitle');
        if (frame) frame.src = url;
        if (titleNode) titleNode.textContent = title;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDocumentPreviewModal() {
        const modal = document.getElementById('documentPreviewModal');
        if (!modal) return;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        const frame = document.getElementById('documentPreviewFrame');
        if (frame) frame.src = '';
        document.body.style.overflow = '';
    }

    function openOldGrantRecordsModal(trigger) {
        const modal = document.getElementById('oldGrantRecordsModal');
        if (!modal) return;

        oldGrantRecordsReturnFocus = trigger;
        oldGrantRecordsPreviousOverflow = document.body.style.overflow;
        modal.hidden = false;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-old-grant-modal-close]')?.focus();
    }

    function closeOldGrantRecordsModal() {
        const modal = document.getElementById('oldGrantRecordsModal');
        if (!modal || modal.hidden) return;

        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        document.body.style.overflow = oldGrantRecordsPreviousOverflow;
        if (oldGrantRecordsReturnFocus?.isConnected) oldGrantRecordsReturnFocus.focus();
        oldGrantRecordsReturnFocus = null;
    }

    document.addEventListener('click', function (event) {
        const openButton = event.target.closest('[data-old-grant-modal-open]');
        if (openButton) {
            openOldGrantRecordsModal(openButton);
            return;
        }

        const closeButton = event.target.closest('[data-old-grant-modal-close]');
        if (closeButton) {
            closeOldGrantRecordsModal();
            return;
        }

        const modal = document.getElementById('oldGrantRecordsModal');
        if (modal && event.target === modal) closeOldGrantRecordsModal();
    });

    document.addEventListener('click', function (event) {
        const closeButton = event.target.closest('#closeDocumentPreviewModal');
        if (closeButton) {
            event.preventDefault();
            closeDocumentPreviewModal();
            return;
        }

        const trigger = event.target.closest('.document-view');
        if (!trigger) {
            const modal = document.getElementById('documentPreviewModal');
            if (modal && event.target === modal) {
                closeDocumentPreviewModal();
            }
            return;
        }

        const isPdf = trigger.dataset.documentType === 'pdf';
        if (!isPdf) return;
        event.preventDefault();
        const linkLabel = trigger.textContent.trim() || 'Document';
        openDocumentPreviewModal(trigger.getAttribute('href'), linkLabel);
    });

    document.addEventListener('keydown', function (event) {
        const oldGrantModal = document.getElementById('oldGrantRecordsModal');
        if (oldGrantModal && !oldGrantModal.hidden) {
            if (event.key === 'Escape') {
                closeOldGrantRecordsModal();
                return;
            }

            if (event.key === 'Tab') {
                const focusable = Array.from(oldGrantModal.querySelectorAll('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'));
                if (focusable.length) {
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            }
        }

        if (event.key === 'Escape') {
            const modal = document.getElementById('documentPreviewModal');
            if (modal && modal.style.display === 'flex') {
                closeDocumentPreviewModal();
            }
        }
    });

    function renderProfile(app) {
        const body = document.getElementById('profileBody');
        currentApplication = app;
        const oldGrantMatches = Array.isArray(app.old_grant_matches) ? app.old_grant_matches : [];
        const oldGrantGroups = Array.from(oldGrantMatches.reduce((groups, record) => {
            const scholarshipName = String(record.scholarship_name || 'Unnamed scholarship').trim();
            const type = record.scholarship_type === 'academic' ? 'Academic' : 'Non-Academic';
            const key = `${type.toLowerCase()}|${scholarshipName.toUpperCase()}`;
            const group = groups.get(key) || { record, scholarshipName, type, count: 0 };
            group.count += 1;
            groups.set(key, group);
            return groups;
        }, new Map()).values());
        const oldGrantWarning = oldGrantMatches.length ? `
            <section class="old-grant-match-warning" role="status" aria-label="Possible Old Grants match">
                <div class="old-grant-match-heading">
                    <span class="old-grant-match-icon" aria-hidden="true">!</span>
                    <p>This student appears in the Old Grants records. Possible match, please verify.</p>
                    <button class="old-grant-match-open" type="button" data-old-grant-modal-open aria-haspopup="dialog" aria-controls="oldGrantRecordsModal">View</button>
                </div>
            </section>` : '';
        const oldGrantList = oldGrantGroups.map(group => {
                    const record = group.record;
                    const route = record.scholarship_type === 'academic' ? '/admin/old-academic' : '/admin/old-non-academic';
                    const search = `${record.last_name || ''} ${record.first_name || ''}`.trim();
                    const url = `${route}?search=${encodeURIComponent(search)}`;
                    return `<li class="old-grant-record-row"><span class="old-grant-record-name">${escapeHtml(group.scholarshipName)}</span><span class="old-grant-record-type">${escapeHtml(group.type)}</span><span class="old-grant-record-count" aria-label="Count: ${group.count}">${group.count}</span><a class="old-grant-record-view" href="${escapeHtml(url)}" target="_blank" rel="noopener">View</a></li>`;
                }).join('');
        const canDecideApplication = app.application_status === 'pending' && !app.locked_at;
        const decisionDisabledAttribute = canDecideApplication ? '' : 'disabled aria-disabled="true"';
        const isNonAcademic = app.grant_type === 'non-academic';
        const familyProgress = document.querySelector('[data-profile-family-progress]');
        const familyProgressLine = document.querySelector('[data-profile-family-progress-line]');
        const finalProgress = document.querySelector('[data-profile-final-progress]');
        profileStepCount = isNonAcademic ? 4 : 3;
        currentFamilyPage = 1;
        if (familyProgress) {
            familyProgress.hidden = !isNonAcademic;
            if (isNonAcademic) familyProgress.dataset.profileProgressStep = '3';
            else familyProgress.removeAttribute('data-profile-progress-step');
        }
        if (familyProgressLine) familyProgressLine.hidden = !isNonAcademic;
        if (finalProgress) {
            finalProgress.dataset.profileProgressStep = String(profileStepCount);
            const dot = finalProgress.querySelector('.academic-progress-dot');
            if (dot) dot.textContent = profileStepCount;
        }
        document.getElementById('profileStepTotal').textContent = profileStepCount;
        let uploads = app.uploads || {};
        if (Array.isArray(uploads)) uploads = { uploads };
        const step1Fields = [
            field('First name', app.first_name, 'first_name'), field('Middle name', app.middle_name, 'middle_name'),
            field('Last name', app.last_name, 'last_name'), field('Suffix', app.suffix, 'suffix'), field('Email', app.email, 'email'),
            field('Student ID Number', app.student_id_number || app.id_number, 'student_id_number'), field('Course', app.course, 'course'),
            field('College or Department', app.college_department, 'college_department'), field('Year Level', app.year_level, 'year_level')
        ];
        if (!isNonAcademic) step1Fields.push(field('Program', app.program, 'program'));
        const step1 = step1Fields.join('');
        const step2Fields = isNonAcademic ? [
            field('Sex', app.sex, 'sex'), field('Status', app.status, 'status'),
            field('Contact Number', app.contact_number, 'contact_number'),
            field('Birthday', app.birthdate ? String(app.birthdate).split('T')[0] : '', 'birthdate'),
            field('Birthplace', app.birthplace, 'birthplace'), field('Age', calculateAge(app.birthdate), 'age'),
            field('Home Address', app.home_address, 'home_address')
        ] : [
                field('Birthday', app.birthdate ? String(app.birthdate).split('T')[0] : '', 'birthdate'),
                field('Birthplace', app.birthplace, 'birthplace'), field('Age', calculateAge(app.birthdate), 'age'), field('Sex', app.sex, 'sex'),
                field('Status', app.status, 'status'), field('Religion', app.religion, 'religion'),
                field('Contact Number', app.contact_number, 'contact_number'), field('Home Address', app.home_address, 'home_address')
            ];
        const step2 = step2Fields.join('');
        const documentReviews = app.document_review || app.document_reviews;
        let documents;
        if (isNonAcademic) {
            if (Array.isArray(uploads.supporting_document) && uploads.supporting_document.length) {
                documents = documentGroup('Supporting Document', uploads.supporting_document, 'supporting_document', documentReviews, false);
            } else {
                documents = [
                    documentGroup('Supporting Document', uploads.supporting_document, 'supporting_document', documentReviews),
                    documentGroup('Certificate of Grades', uploads.certificate_of_grades || uploads.grades, uploads.certificate_of_grades ? 'certificate_of_grades' : 'grades', documentReviews),
                    documentGroup('Certificate of Enrollment', uploads.certificate_of_enrollment || uploads.enrollment, uploads.certificate_of_enrollment ? 'certificate_of_enrollment' : 'enrollment', documentReviews),
                    documentGroup('Certificate from Sports Coordinator', uploads.certificate_of_sports_coordinator || uploads.uploads, 'certificate_of_sports_coordinator', documentReviews),
                    documentGroup('Uploaded Files', uploads.uploads, 'uploads', documentReviews),
                    documentGroup('Certificate of Adviser', uploads.certificate_of_adviser, 'certificate_of_adviser', documentReviews)
                ].join('');
            }
        } else if (Array.isArray(uploads.supporting_document) && uploads.supporting_document.length) {
            documents = documentGroup(
                'Certificate of Grades / Certificate of Enrollment',
                uploads.supporting_document,
                'supporting_document',
                documentReviews,
                false
            );
        } else {
            const gradesGroup = Array.isArray(uploads.certificate_of_grades) && uploads.certificate_of_grades.length
                ? 'certificate_of_grades'
                : Array.isArray(uploads.grades) && uploads.grades.length
                    ? 'grades'
                    : 'uploads';
            const gradesFiles = gradesGroup === 'certificate_of_grades'
                ? uploads.certificate_of_grades
                : gradesGroup === 'grades'
                    ? uploads.grades
                    : uploads.uploads;
            const enrollmentGroup = Array.isArray(uploads.certificate_of_enrollment) && uploads.certificate_of_enrollment.length
                ? 'certificate_of_enrollment'
                : 'enrollment';
            const enrollmentFiles = enrollmentGroup === 'certificate_of_enrollment'
                ? uploads.certificate_of_enrollment
                : uploads.enrollment;
            documents = [
                documentGroup('Certificate of Grades', gradesFiles, gradesGroup, documentReviews),
                documentGroup('Certificate of Enrollment', enrollmentFiles, enrollmentGroup, documentReviews)
            ].join('');
        }
        const scholarshipStep = field('Name of Scholarship / Sponsor', app.scholarship_name, 'scholarship_name') +
            field('Types of Scholarship', app.scholarship_type, 'scholarship_type') +
            `<div class="full"><label>Uploaded documents</label>${documents || '<div class="input">No files uploaded.</div>'}</div>` +
            `<div class="review-actions application-decision-actions full">
                <button type="button" class="review-approve" data-action="approve-app" ${decisionDisabledAttribute}>Approve Application</button>
                <button type="button" class="review-reject" data-action="reject-app" ${decisionDisabledAttribute}>Reject Application</button>
            </div>`;
        const family = app.family_background || {};
        const familyValue = value => value === null || value === undefined || String(value).trim() === '' ? 'N/A' : value;
        const dateValue = value => value ? String(value).split('T')[0] : '';
        const pairedFields = (leftLabel, leftValue, leftName, rightLabel, rightValue, rightName) => `
            <div class="family-background-field-row">
                ${field(leftLabel, leftValue, leftName)}
                ${field(rightLabel, rightValue, rightName)}
            </div>`;
        const motherFields = [
            pairedFields('Mother Status', familyValue(family.mother_status), 'mother_status', 'Mother Full Name', familyValue(family.mother_name), 'mother_name'),
            pairedFields('Mother Age', familyValue(family.mother_age), 'mother_age', 'Mother Birth Date', dateValue(family.mother_birthdate) || 'N/A', 'mother_birthdate'),
            pairedFields('Mother Present Address', familyValue(family.mother_address), 'mother_address', 'Mother Occupation', familyValue(family.mother_occupation), 'mother_occupation'),
            pairedFields('Mother Office Address', familyValue(family.mother_office_address), 'mother_office_address', 'Mother Highest Educational Attainment', familyValue(family.mother_education), 'mother_education')
        ].join('');
        const fatherFields = [
            pairedFields('Father Status', familyValue(family.father_status), 'father_status', 'Father Full Name', familyValue(family.father_name), 'father_name'),
            pairedFields('Father Age', familyValue(family.father_age), 'father_age', 'Father Birth Date', dateValue(family.father_birthdate) || 'N/A', 'father_birthdate'),
            pairedFields('Father Present Address', familyValue(family.father_address), 'father_address', 'Father Occupation', familyValue(family.father_occupation), 'father_occupation'),
            pairedFields('Father Office Address', familyValue(family.father_office_address), 'father_office_address', 'Father Highest Educational Attainment', familyValue(family.father_education), 'father_education')
        ].join('');
        const shouldShowDisabilityDetails = String(family.has_differently_abled_member ?? '').toLowerCase() === 'yes' || family.has_differently_abled_member === true;
        const disabilityField = shouldShowDisabilityDetails
            ? `<div class="family-background-single-row full">${field('Kind of Disability', familyValue(family.disability_details), 'disability_details')}</div>`
            : '';
        const familySharedFields = [
            pairedFields('Estimated Monthly/Annual Family Income', familyValue(family.monthly_income), 'monthly_income', 'Differently Abled Family Member', family.has_differently_abled_member === null || family.has_differently_abled_member === undefined ? 'N/A' : (family.has_differently_abled_member ? 'Yes' : 'No'), 'has_differently_abled_member'),
            disabilityField,
        ].join('');
        const siblings = Array.isArray(family.siblings) ? family.siblings : [];
        const siblingsTable = siblings.length
            ? `<div class="family-background-modal-siblings full"><label>Siblings</label><div class="family-background-modal-table-wrap"><table><thead><tr><th>Name</th><th>Age</th><th>Marital Status</th><th>Employment/Occupation</th></tr></thead><tbody>${siblings.map(sibling => `<tr><td>${escapeHtml(familyValue(sibling.name))}</td><td>${escapeHtml(familyValue(sibling.age))}</td><td>${escapeHtml(familyValue(sibling.marital_status))}</td><td>${escapeHtml(familyValue(sibling.occupation))}</td></tr>`).join('')}</tbody></table></div></div>`
            : '<div class="family-background-modal-siblings full"><label>Siblings</label><div class="input">N/A</div></div>';
        const familySubpage1 = `
            <div class="academic-review-substep" data-profile-family-page="1" ${currentFamilyPage === 1 ? 'data-profile-family-active="true"' : 'hidden'}>
                <div class="family-background-substep-shell">
                    <div class="family-background-substep-title">Mother</div>
                    <div class="family-background-review-grid">${motherFields}</div>
                </div>
            </div>`;
        const familySubpage2 = `
            <div class="academic-review-substep" data-profile-family-page="2" ${currentFamilyPage === 2 ? 'data-profile-family-active="true"' : 'hidden'}>
                <div class="family-background-substep-shell">
                    <div class="family-background-substep-title">Father</div>
                    <div class="family-background-review-grid">${fatherFields}</div>
                </div>
            </div>`;
        const familySubpage3 = `
            <div class="academic-review-substep" data-profile-family-page="3" ${currentFamilyPage === 3 ? 'data-profile-family-active="true"' : 'hidden'}>
                <div class="family-background-substep-shell">
                    <div class="family-background-substep-title">Parents / Guardian & Family Details</div>
                    <div class="family-background-review-grid">${familySharedFields}${siblingsTable}</div>
                </div>
            </div>`;
        const familyStep = isNonAcademic ? `
            <div class="academic-review-step" data-profile-step="3">${familySubpage1}${familySubpage2}${familySubpage3}</div>
            <div class="academic-review-step" data-profile-step="4">${scholarshipStep}</div>`
            : `<div class="academic-review-step" data-profile-step="1">${step1}</div><div class="academic-review-step" data-profile-step="2">${step2}</div><div class="academic-review-step" data-profile-step="3">${scholarshipStep}</div>`;
        body.innerHTML = isNonAcademic
            ? `${oldGrantWarning}<div class="academic-review-step" data-profile-step="1">${step1}</div><div class="academic-review-step" data-profile-step="2">${step2}</div>${familyStep}`
            : `${oldGrantWarning}<div class="academic-review-step" data-profile-step="1">${step1}</div><div class="academic-review-step" data-profile-step="2">${step2}</div><div class="academic-review-step" data-profile-step="3">${scholarshipStep}</div>`;
        const oldGrantListElement = document.getElementById('oldGrantRecordsList');
        if (oldGrantListElement) oldGrantListElement.innerHTML = oldGrantList;
        updateStepUI();
    }

    function updateStepUI() {
        document.querySelectorAll('#profileModal [data-profile-step]').forEach(panel => panel.classList.toggle('active', Number(panel.dataset.profileStep) === currentStep));
        document.querySelectorAll('#profileModal [data-profile-progress-step]').forEach(step => {
            const value = Number(step.dataset.profileProgressStep);
            step.classList.toggle('active', value === currentStep);
            step.classList.toggle('completed', value < currentStep);
        });
        document.querySelectorAll('#profileModal [data-profile-family-page]').forEach(page => {
            const isVisible = currentStep === 3 && Number(page.dataset.profileFamilyPage) === currentFamilyPage;
            page.hidden = !isVisible;
            page.dataset.profileFamilyActive = isVisible ? 'true' : 'false';
        });
        document.getElementById('profileStepCounter').textContent = currentStep;
        document.getElementById('profilePageCounter').textContent = `${currentStep} / ${profileStepCount}`;
        document.getElementById('profilePrevious').disabled = currentStep === 1;
        document.getElementById('profileNext').disabled = currentStep === profileStepCount;
        document.getElementById('profilePrevious').style.visibility = 'visible';
        document.getElementById('profilePreviousTop').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        document.getElementById('profileNext').style.visibility = 'visible';
    }

    async function loadProfile(id) {
        const response = await fetch(`/admin/applications/${encodeURIComponent(id)}`);
        if (!response.ok) throw new Error('Unable to load profile');
        renderProfile(await response.json());
    }

    async function finishApplicationAction(title, text) {
        const modal = document.getElementById('profileModal');
        modal?.classList.remove('open');
        modal?.setAttribute('aria-hidden', 'true');
        await Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title,
            text,
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
        window.location.reload();
    }

    async function rejectApplication() {
        const reasons = currentApplication?.renewal_required || currentApplication?.renewal_resubmitted
            ? [
                'Does not meet the eligibility requirements',
                'Wrong Uploaded Documents',
            ]
            : [
                'Does not meet the eligibility requirements',
                'Wrong Uploaded Documents',
                'Wrong Form',
            ];
        const result = await Swal.fire({
            title: 'Reject Application',
            input: 'select',
            inputOptions: Object.fromEntries(reasons.map(reason => [reason, reason])),
            inputPlaceholder: 'Select a reason',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            confirmButtonColor: '#b91c1c',
            inputValidator: value => reasons.includes(value) ? undefined : 'Select a rejection reason.',
        });
        if (!result.isConfirmed) return;

        let rejectionRemark = '';
        if (result.value !== 'Does not meet the eligibility requirements') {
            const remarkResult = await Swal.fire({
                title: 'What needs to be corrected?',
                input: 'textarea',
                inputPlaceholder: 'Describe the document or form correction required.',
                inputAttributes: { maxlength: '2000' },
                showCancelButton: true,
                confirmButtonText: 'Continue',
                inputValidator: value => value?.trim() ? undefined : 'Enter a remark describing what needs to be corrected.',
            });
            if (!remarkResult.isConfirmed) return;
            rejectionRemark = remarkResult.value.trim();
        }

        setDecisionButtonsDisabled(true);
        await postJson(`/admin/applications/${currentAppId}/reject`, {
            reason: result.value,
            rejection_remark: rejectionRemark,
        });
        await finishApplicationAction('Rejected successfully', 'The application was rejected successfully.');
    }

    function setDecisionButtonsDisabled(disabled) {
        document.querySelectorAll('#profileBody [data-action="approve-app"], #profileBody [data-action="reject-app"]')
            .forEach(button => {
                button.disabled = disabled;
                button.setAttribute('aria-disabled', String(disabled));
            });
    }

    // CHANGE: Open the approved-applicant bulk amount editor from the filter bar.
    async function addAmountToFilteredApplicants() {
        const amountButton = document.querySelector('[data-action="add-amount"]');
        const isCombinedScope = amountButton?.dataset.grantType === 'all';
        const result = await Swal.fire({
            title: 'Add Amount',
            input: 'number',
            inputAttributes: { min: '0', step: '0.01', placeholder: 'Enter amount' },
            showCancelButton: true,
            confirmButtonText: 'Save to All',
            cancelButtonText: 'Cancel',
            inputValidator: value => value === '' || Number(value) < 0 || !Number.isFinite(Number(value)) ? 'Enter a valid amount of 0 or more.' : undefined,
        });
        if (!result.isConfirmed) return;

        const confirmation = await Swal.fire({
            title: 'Apply amount to all approved applicants?',
            text: isCombinedScope
                ? 'This will update approved applicants matching the current Summary of Reports filters.'
                : 'This will update every applicant matching the current Academic Grants filters.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, save to all',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#4a0a12',
        });
        if (!confirmation.isConfirmed) return;

        try {
            const params = new URLSearchParams(window.location.search);
            const data = {
                grant_type: amountButton?.dataset.grantType || '',
                date_from: params.get('date_from') || '',
                date_to: params.get('date_to') || '',
                scholarship_name: params.get('scholarship_name') || '',
                status: isCombinedScope ? 'approved' : (params.get('status') || ''),
                amount: result.value,
            };
            await postJson('/admin/applications/update-scholarship-amounts', data);
            await Swal.fire({ icon: 'success', title: 'Saved', text: 'Amount saved to all matching approved applicants.', timer: 1800, showConfirmButton: false });
            window.location.reload();
        } catch (error) {
            await Swal.fire({ icon: 'error', title: 'Unable to save amount', text: error.message });
        }
    }

    document.querySelectorAll('.view-profile-btn').forEach(button => button.addEventListener('click', async () => {
        currentAppId = button.dataset.id;
        currentStep = 1;
        document.getElementById('profileModal').classList.add('open');
        try { await loadProfile(currentAppId); } catch (error) { document.getElementById('profileBody').textContent = error.message; }
    }));

    const needsAttentionPanel = document.getElementById('needsAttentionPanel');
    let needsAttentionLoading = false;

    function setNeedsAttentionUrl(page, replace = false) {
        const url = new URL(window.location.href);
        url.searchParams.set('na_page', String(page));
        window.history[replace ? 'replaceState' : 'pushState']({ naPage: page }, '', url);
    }

    async function loadNeedsAttentionPage(requestedPage, replaceUrl = false) {
        if (!needsAttentionPanel || needsAttentionLoading) return;

        needsAttentionLoading = true;
        needsAttentionPanel.setAttribute('aria-busy', 'true');
        needsAttentionPanel.classList.add('is-loading');
        needsAttentionPanel.querySelector('.needs-attention-fetch-error')?.remove();
        let loadingMessage = needsAttentionPanel.querySelector('.needs-attention-loading');
        if (!loadingMessage) {
            loadingMessage = document.createElement('p');
            loadingMessage.className = 'needs-attention-loading';
            loadingMessage.setAttribute('role', 'status');
            needsAttentionPanel.appendChild(loadingMessage);
        }
        loadingMessage.textContent = 'Loading Needs Attention items...';
        needsAttentionPanel.querySelectorAll('[data-needs-attention-page]').forEach(button => {
            button.disabled = true;
        });

        try {
            const endpoint = new URL(needsAttentionPanel.dataset.endpoint, window.location.href);
            endpoint.searchParams.set('na_page', String(requestedPage));
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Request failed');

            const result = await response.json();
            if (typeof result.html !== 'string' || !Number.isInteger(Number(result.page))) {
                throw new Error('Unexpected response');
            }

            needsAttentionPanel.innerHTML = result.html;
            needsAttentionPanel.dataset.page = String(result.page);
            needsAttentionPanel.dataset.totalPages = String(result.totalPages);
            setNeedsAttentionUrl(result.page, replaceUrl);
        } catch (error) {
            loadingMessage.className = 'needs-attention-fetch-error';
            loadingMessage.setAttribute('role', 'alert');
            loadingMessage.textContent = 'Unable to load Needs Attention items. Please try again.';
            needsAttentionPanel.querySelectorAll('[data-needs-attention-page]').forEach(button => {
                button.disabled = false;
            });
        } finally {
            needsAttentionLoading = false;
            needsAttentionPanel.setAttribute('aria-busy', 'false');
            needsAttentionPanel.classList.remove('is-loading');
        }
    }

    if (needsAttentionPanel) {
        const requestedPage = new URL(window.location.href).searchParams.get('na_page');
        if (requestedPage !== null && requestedPage !== needsAttentionPanel.dataset.page) {
            setNeedsAttentionUrl(needsAttentionPanel.dataset.page, true);
        }
    }

    document.addEventListener('click', async event => {
        const pageButton = event.target.closest('[data-needs-attention-page]');
        if (pageButton && needsAttentionPanel) {
            event.preventDefault();
            if (!pageButton.disabled) loadNeedsAttentionPage(pageButton.dataset.needsAttentionPage);
            return;
        }

        const reviewButton = event.target.closest('.needs-attention-review');
        if (!reviewButton) return;

        currentAppId = reviewButton.dataset.id;
        currentStep = 1;
        document.getElementById('profileModal').classList.add('open');
        try { await loadProfile(currentAppId); } catch (error) { document.getElementById('profileBody').textContent = error.message; }
    });

    window.addEventListener('popstate', () => {
        if (!needsAttentionPanel) return;
        const page = new URL(window.location.href).searchParams.get('na_page') || '1';
        loadNeedsAttentionPage(page, true);
    });

    const handleProfilePrevious = () => {
        if (currentStep === 4) {
            currentStep = 3;
            currentFamilyPage = 3;
            updateStepUI();
            return;
        }
        if (currentStep === 3) {
            if (currentFamilyPage > 1) {
                currentFamilyPage -= 1;
                updateStepUI();
                return;
            }
            currentStep = 2;
            currentFamilyPage = 1;
            updateStepUI();
            return;
        }
        if (currentStep > 1) {
            currentStep -= 1;
            if (currentStep === 3) currentFamilyPage = 3;
            updateStepUI();
        }
    };

    const handleProfileNext = () => {
        if (currentStep === 3) {
            if (currentFamilyPage < 3) {
                currentFamilyPage += 1;
                updateStepUI();
                return;
            }
            currentStep = 4;
            currentFamilyPage = 1;
            updateStepUI();
            return;
        }
        if (currentStep < profileStepCount) {
            currentStep += 1;
            if (currentStep === 3) currentFamilyPage = 1;
            updateStepUI();
        }
    };

    document.getElementById('profilePrevious')?.addEventListener('click', handleProfilePrevious);
    document.getElementById('profilePreviousTop')?.addEventListener('click', handleProfilePrevious);
    document.getElementById('profileNext')?.addEventListener('click', handleProfileNext);
    document.getElementById('closeProfile')?.addEventListener('click', () => {
        const modal = document.getElementById('profileModal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    });

    document.getElementById('profileBody')?.addEventListener('click', async event => {
        const actionTarget = event.target.closest('[data-action]');
        if (actionTarget?.disabled) return;
        try {
            if (actionTarget && actionTarget.dataset.action === 'approve-app') {
            setDecisionButtonsDisabled(true);
                await postJson(`/admin/applications/${currentAppId}/approve`);
                await finishApplicationAction('Approved successfully', 'The application was approved successfully.');
                return;
            }
            if (actionTarget && actionTarget.dataset.action === 'reject-app') {
                await rejectApplication();
                return;
            }
            if (actionTarget && actionTarget.dataset.action === 'remove-app') {
                const result = await Swal.fire({
                    title: 'Remove application?',
                    text: 'This application will be permanently removed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#b91c1c',
                    confirmButtonText: 'Remove Application',
                });
                if (!result.isConfirmed) return;
                await postJson(`/admin/applications/${currentAppId}/delete`);
                await finishApplicationAction('Deleted successfully', 'The application was deleted successfully.');
            }
        } catch (error) {
            try {
                await loadProfile(currentAppId);
            } catch (_) {
                setDecisionButtonsDisabled(true);
            }
            Swal.fire({ icon: 'error', title: 'Action failed', text: error.message });
        }
    });

    // CHANGE: Bind the approved-filter-only Add Amount button.
    document.querySelector('[data-action="add-amount"]')?.addEventListener('click', addAmountToFilteredApplicants);

    async function removeRestrictionsFromFilteredApplicants() {
        const button = document.querySelector('[data-action="remove-restriction"]');
        const params = new URLSearchParams(window.location.search);
        const confirmation = await Swal.fire({
            title: 'Remove restriction for all approved applicants?',
            text: 'This will reopen Scholarship & Requirements for all approved applications matching the current Summary of Reports filters.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove restriction',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
        });
        if (!confirmation.isConfirmed) return;

        try {
            const data = {
                grant_type: button?.dataset.grantType || '',
                date_from: params.get('date_from') || '',
                date_to: params.get('date_to') || '',
                scholarship_name: params.get('scholarship_name') || '',
            };
            const result = await postJson('/admin/applications/remove-restrictions', data);
            const affected = Number(result.updated ?? 0);
            const warnings = Array.isArray(result.email_failures) ? result.email_failures : [];
            await Swal.fire({
                icon: warnings.length ? 'warning' : 'success',
                title: warnings.length ? 'Restrictions removed with email warnings' : 'Restrictions removed',
                text: warnings.length
                    ? `Removed restrictions for ${affected} approved applicants. ${warnings.length} email(s) could not be queued; the affected applications were still reopened.`
                    : `Removed restrictions for ${affected} approved applicants.`,
                timer: warnings.length ? 5000 : 2500,
                showConfirmButton: !warnings.length,
            });
            if (warnings.length) {
                console.warn('Renewal emails failed:', warnings);
            }
            window.location.reload();
        } catch (error) {
            await Swal.fire({ icon: 'error', title: 'Unable to remove restrictions', text: error.message });
        }
    }

    document.querySelector('[data-action="remove-restriction"]')?.addEventListener('click', removeRestrictionsFromFilteredApplicants);

    // CHANGE: Inline create/edit/delete controls for manually logged applicants.
    const manualApplicantFields = ['last_name', 'first_name', 'middle_name', 'sex', 'course', 'year_level', 'scholarship_name', 'date', 'application_status'];
    const structuredManualRequiredFields = ['last_name', 'first_name', 'sex', 'course', 'year_level', 'scholarship_name', 'date', 'application_status'];
    const academicManualCourses = [
        'BACHELOR OF SCIENCE IN CRIMINOLOGY',
        'BACHELOR OF SCIENCE IN FISHERIES',
        'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
        'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT',
        'BACHELOR OF SECONDARY EDUCATION',
        'BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM',
        'BACHELOR OF ELEMENTARY EDUCATION',
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics',
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive',
    ];
    let openManualRow = null;
    let openManualEmptyRow = null;

    function parseManualFullName(fullName) {
        const normalized = String(fullName ?? '').trim();
        if (!normalized || normalized === '—') {
            return { last_name: '', first_name: '', middle_name: '', suffix: '' };
        }

        const parts = normalized.split(',').map(part => part.trim()).filter(Boolean);
        if (parts.length >= 4) {
            return {
                last_name: parts[0] || '',
                middle_name: parts[1] || '',
                first_name: parts[2] || '',
                suffix: parts[3] || '',
            };
        }
        if (parts.length === 3) {
            return {
                last_name: parts[0] || '',
                middle_name: parts[1] || '',
                first_name: parts[2] || '',
                suffix: '',
            };
        }
        if (parts.length === 2) {
            return {
                last_name: parts[0] || '',
                first_name: parts[1] || '',
                middle_name: '',
                suffix: '',
            };
        }
        return {
            last_name: '',
            first_name: normalized,
            middle_name: '',
            suffix: '',
        };
    }

    function manualScholarshipOptions(selectedValue, grantType, table) {
        const grantTypeValues = grantType === 'all' ? ['academic', 'non-academic'] : [grantType];
        const canonicalOptions = [];
        const fallbackMap = new Map();

        grantTypeValues.forEach(type => {
            const catalogKey = type === 'academic' ? 'academicScholarships' : 'nonAcademicScholarships';
            try {
                JSON.parse(table?.dataset[catalogKey] || '[]').forEach(item => {
                    if (item && item.name) {
                        canonicalOptions.push(item.name);
                        fallbackMap.set(item.name, item.funding_type || '');
                    }
                });
            } catch (error) {
                // Ignore catalog parse issues and fall back to the form options below.
            }
        });

        const grantFilterSelect = document.getElementById('grant-scholarship-name');
        if (grantFilterSelect) {
            Array.from(grantFilterSelect.options).forEach(option => {
                if (option.value) canonicalOptions.push(option.value);
            });
        }

        if (!canonicalOptions.length) {
            const summarySelect = document.getElementById('summary-scholarship-name');
            if (summarySelect) {
                Array.from(summarySelect.options).forEach(option => {
                    if (option.value) canonicalOptions.push(option.value);
                });
            }
        }

        if (!canonicalOptions.length) {
            canonicalOptions.push(selectedValue || '');
        }

        const uniqueOptions = [...new Set(canonicalOptions.filter(Boolean))];
        const optionMarkup = uniqueOptions
            .map(option => `<option value="${escapeHtml(option)}" data-funding-type="${escapeHtml(fallbackMap.get(option) || '')}" ${selectedValue === option ? 'selected' : ''}>${escapeHtml(option)}</option>`)
            .join('');

        return `<select class="manual-row-input" data-field="scholarship_name" required><option value="">Select</option>${optionMarkup}</select>`;
    }

    function manualField(value, fieldName, type = 'text', grantType = '') {
        if (fieldName === 'sex') {
            return `<select class="manual-row-input" data-field="sex"><option value="">Select</option><option value="Male" ${value === 'Male' ? 'selected' : ''}>Male</option><option value="Female" ${value === 'Female' ? 'selected' : ''}>Female</option></select>`;
        }
        if (fieldName === 'year_level') {
            return `<select class="manual-row-input" data-field="year_level"><option value="">Select</option>${['1st Year', '2nd Year', '3rd Year', '4th Year'].map(option => `<option value="${option}" ${value === option ? 'selected' : ''}>${option}</option>`).join('')}</select>`;
        }
        if (fieldName === 'application_status') {
            return `<select class="manual-row-input" data-field="application_status"><option value="pending" ${value === 'pending' ? 'selected' : ''}>Pending</option><option value="approved" ${value === 'approved' ? 'selected' : ''}>Approved</option><option value="rejected" ${value === 'rejected' ? 'selected' : ''}>Rejected</option></select>`;
        }
        if (fieldName === 'scholarship_name') {
            return manualScholarshipOptions(value || '', grantType);
        }
        return `<input class="manual-row-input" data-field="${fieldName}" type="${type}" value="${escapeHtml(value)}"${fieldName === 'date' ? ' required' : ''}>`;
    }

    function summaryManualRowHtml(values, table, showAmount) {
        const typeValue = values.grant_type || 'academic';
        const catalogKey = typeValue === 'academic' ? 'academicScholarships' : 'nonAcademicScholarships';
        const textField = (name, value, required = false) => `<input class="manual-row-input" data-field="${name}" type="text" value="${escapeHtml(value || '')}"${required ? ' required' : ''}>`;
        const selectField = (name, value, options, required = false) => `<select class="manual-row-input" data-field="${name}"${required ? ' required' : ''}>${options.map(([optionValue, label]) => `<option value="${escapeHtml(optionValue)}" ${value === optionValue ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}</select>`;
        const scholarshipOptions = manualScholarshipOptions(values.scholarship_name || '', typeValue, table);
        const selectedScholarship = (() => {
            try {
                return JSON.parse(table.dataset[catalogKey] || '[]').find(item => item.name === values.scholarship_name)?.funding_type || '—';
            } catch {
                return '—';
            }
        })();

        return `<tr class="manual-applicant-row" data-grant-type="all" data-id="${values.id || ''}">
            <td>${textField('last_name', values.last_name, true)}</td>
            <td>${textField('first_name', values.first_name, true)}</td>
            <td>${textField('middle_name', values.middle_name)}</td>
            <td>${textField('suffix', values.suffix)}</td>
            <td>${selectField('sex', values.sex || 'Male', [['Male', 'Male'], ['Female', 'Female']], true)}</td>
            <td>${selectField('course', values.course || '', academicManualCourses.map(course => [course, course]), true)}</td>
            <td><input class="manual-row-input" data-derived-field="college_department" type="text" value="${escapeHtml(values.college_department || '')}" readonly></td>
            <td>${selectField('year_level', values.year_level || '1st Year', [['1st Year', '1st Year'], ['2nd Year', '2nd Year'], ['3rd Year', '3rd Year'], ['4th Year', '4th Year']], true)}</td>
            <td>${selectField('grant_type', typeValue, [['academic', 'Academic'], ['non-academic', 'Non-Academic']], true)}</td>
            <td>${scholarshipOptions}</td>
            <td><input class="manual-row-input" data-derived-field="funding" type="text" value="${escapeHtml(selectedScholarship || '—')}" readonly></td>
            <td><input class="manual-row-input" type="text" value="Aparri" readonly></td>
            <td><input class="manual-row-input" data-field="date" type="date" value="${escapeHtml(values.date || table.dataset.today || '')}" required></td>
            ${showAmount ? '<td>—</td>' : ''}
            <td><select class="manual-row-input" data-field="application_status" required><option value="approved" selected>Approved</option></select></td>
            <td><button class="btn small manual-save-btn" type="button">Save</button><button class="btn small manual-cancel-btn" type="button">Cancel</button></td>
        </tr>`;
    }

    function structuredManualRowHtml(values, grantType, showAmount, table) {
        const typeLabel = grantType === 'academic' ? 'Academic' : 'Non-Academic';
        const catalogKey = grantType === 'academic' ? 'academicScholarships' : 'nonAcademicScholarships';
        const textField = (name, value, required = false) => `<input class="manual-row-input" data-field="${name}" type="text" value="${escapeHtml(value || '')}"${required ? ' required' : ''}>`;
        const selectField = (name, value, options, required = false) => `<select class="manual-row-input" data-field="${name}"${required ? ' required' : ''}><option value="">Select</option>${options.map(option => `<option value="${escapeHtml(option)}" ${value === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}</select>`;
        const statusSelect = `<select class="manual-row-input" data-field="application_status" required>${[['pending', 'Pending'], ['approved', 'Approved'], ['rejected', 'Rejected']].map(([value, label]) => `<option value="${value}" ${(values.application_status || 'pending') === value ? 'selected' : ''}>${label}</option>`).join('')}</select>`;
        const scholarshipOptions = manualScholarshipOptions(values.scholarship_name || '', grantType, table);
        const selectedScholarship = (() => {
            try {
                return JSON.parse(table.dataset[catalogKey] || '[]').find(item => item.name === values.scholarship_name)?.funding_type || '—';
            } catch {
                return '—';
            }
        })();

        return `<tr class="manual-applicant-row" data-grant-type="${grantType}" data-id="${values.id || ''}">
            <td>${textField('last_name', values.last_name, true)}</td>
            <td>${textField('first_name', values.first_name, true)}</td>
            <td>${textField('middle_name', values.middle_name)}</td>
            <td>${textField('suffix', values.suffix)}</td>
            <td>${selectField('sex', values.sex, ['Male', 'Female'], true)}</td>
            <td>${selectField('course', values.course, academicManualCourses, true)}</td>
            <td><input class="manual-row-input" data-derived-field="college_department" type="text" value="${escapeHtml(values.college_department || '')}" readonly></td>
            <td>${selectField('year_level', values.year_level, ['1st Year', '2nd Year', '3rd Year', '4th Year'], true)}</td>
            <td><input class="manual-row-input" type="text" value="${typeLabel}" readonly></td>
            <td>${scholarshipOptions}</td>
            <td><input class="manual-row-input" data-derived-field="funding" type="text" value="${escapeHtml(selectedScholarship || '—')}" readonly></td>
            <td><input class="manual-row-input" type="text" value="Aparri" readonly></td>
            <td><input class="manual-row-input" data-field="date" type="date" value="${escapeHtml(values.date || table.dataset.today || '')}" required></td>
            ${showAmount ? '<td>—</td>' : ''}
            <td>${statusSelect}</td>
            <td><button class="btn small manual-save-btn" type="button">Save</button><button class="btn small manual-cancel-btn" type="button">Cancel</button></td>
        </tr>`;
    }

    function manualRowHtml(values, grantType, showAmount, table) {
        if (grantType === 'all') return summaryManualRowHtml(values, table, showAmount);
        if (grantType === 'academic' || grantType === 'non-academic') return structuredManualRowHtml(values, grantType, showAmount, table);
        const typeLabel = grantType === 'academic' ? 'Academic' : 'Non-Academic';
        return `<tr class="manual-applicant-row" data-grant-type="${grantType}" data-id="${values.id || ''}">
            <td>${manualField(values.last_name, 'last_name')}</td>
            <td>${manualField(values.first_name, 'first_name')}</td>
            <td>${manualField(values.middle_name, 'middle_name')}</td>
            <td>${manualField(values.sex, 'sex')}</td>
            <td>${manualField(values.course, 'course')}</td>
            <td>${manualField(values.year_level, 'year_level')}</td>
            <td><input class="manual-row-input" type="text" value="${typeLabel}" readonly></td>
            <td>${manualField(values.scholarship_name, 'scholarship_name', 'text', grantType)}</td>
            <td>${manualField(values.date, 'date', 'date')}</td>
            ${showAmount ? '<td>—</td>' : ''}
            <td>${manualField(values.application_status || 'pending', 'application_status')}</td>
            <td><button class="btn small manual-save-btn" type="button">Save</button><button class="btn small manual-cancel-btn" type="button">Cancel</button></td>
        </tr>`;
    }

    function setManualButtonState(isOpen) {
        const button = document.querySelector('[data-action="add-applicant"]');
        if (button) {
            button.disabled = isOpen;
            button.textContent = isOpen ? 'Add Applicant' : 'Add Applicant';
        }
    }

    function manualValuesFromRow(row) {
        const cells = row.querySelectorAll('td');
        const showAmount = document.getElementById('admin-applications-table')?.dataset.showAmount === '1';
        const rowGrantType = row.dataset.grantType || document.getElementById('admin-applications-table')?.dataset.grantType;
        if (rowGrantType === 'all') {
            const grantType = cells[8]?.textContent.trim().toLowerCase() === 'non-academic' ? 'non-academic' : 'academic';
            const statusIndex = showAmount ? 14 : 13;
            return {
                id: row.dataset.id,
                last_name: cells[0]?.textContent.trim() || '',
                first_name: cells[1]?.textContent.trim() || '',
                middle_name: cells[2]?.textContent.trim() || '',
                suffix: cells[3]?.textContent.trim() || '',
                sex: cells[4]?.textContent.trim() || '',
                course: cells[5]?.textContent.trim() || '',
                college_department: cells[6]?.textContent.trim() || '',
                year_level: cells[7]?.textContent.trim() || '',
                grant_type: grantType,
                scholarship_name: cells[9]?.textContent.trim() || '',
                date: cells[12]?.textContent.trim() || '',
                application_status: cells[statusIndex]?.textContent.trim().toLowerCase() || 'approved',
            };
        }
        if (rowGrantType === 'academic' || rowGrantType === 'non-academic') {
            const parsedName = parseManualFullName(cells[0]?.textContent.trim() || '');
            return {
                id: row.dataset.id,
                last_name: parsedName.last_name,
                first_name: parsedName.first_name,
                middle_name: parsedName.middle_name,
                suffix: parsedName.suffix,
                sex: cells[1]?.textContent.trim() || '',
                course: cells[2]?.textContent.trim() || '',
                year_level: cells[3]?.textContent.trim() || '',
                scholarship_name: cells[4]?.textContent.trim() || '',
                date: cells[5]?.textContent.trim() || '',
                application_status: cells[showAmount ? 7 : 6]?.textContent.trim().toLowerCase() || 'pending',
            };
        }
        const statusIndex = showAmount ? 10 : 9;
        return {
            id: row.dataset.id,
            last_name: cells[0]?.textContent.trim() || '',
            first_name: cells[1]?.textContent.trim() || '',
            middle_name: cells[2]?.textContent.trim() || '',
            sex: cells[3]?.textContent.trim() || '',
            course: cells[4]?.textContent.trim() || '',
            year_level: cells[5]?.textContent.trim() || '',
            scholarship_name: cells[7]?.textContent.trim() || '',
            date: cells[8]?.textContent.trim() || '',
            application_status: cells[statusIndex]?.textContent.trim().toLowerCase() || 'pending',
        };
    }

    function collectManualValues(row) {
        const values = {};
        row.querySelectorAll('[data-field]').forEach(input => { values[input.dataset.field] = input.value.trim(); });
        if (!values.grant_type && row.dataset.grantType === 'all') {
            values.grant_type = row.querySelector('[data-field="grant_type"]')?.value || 'academic';
        }
        if (!values.grant_type) {
            values.grant_type = row.closest('table').dataset.grantType;
        }
        return values;
    }

    async function saveManualRow(row) {
        const values = collectManualValues(row);
        const selectedGrantType = values.grant_type && ['academic', 'non-academic'].includes(values.grant_type)
            ? values.grant_type
            : (row.dataset.grantType === 'all' ? 'academic' : row.dataset.grantType);
        values.grant_type = selectedGrantType;
        values.application_status = values.application_status || 'approved';

        const isStructuredGrant = selectedGrantType === 'academic' || selectedGrantType === 'non-academic';
        const requiredFields = isStructuredGrant
            ? structuredManualRequiredFields
            : manualApplicantFields.filter(field => field !== 'middle_name');
        const missing = requiredFields.some(field => !values[field]);
        if (missing) {
            await Swal.fire({ icon: 'error', title: 'Incomplete applicant', text: 'Please complete all required fields.' });
            return;
        }

        const id = row.dataset.id;
        const table = row.closest('table');
        const isSummaryTable = table.dataset.grantType === 'all';
        const url = isStructuredGrant
            ? (id ? table.dataset.manualUpdateUrlTemplate?.replace('__ID__', encodeURIComponent(id)) : (selectedGrantType === 'academic' ? table.dataset.academicStoreUrl : table.dataset.nonAcademicStoreUrl))
            : (id ? `/admin/manual-applicants/${encodeURIComponent(id)}` : '/admin/manual-applicants');
        const requestUrl = id && isSummaryTable
            ? table.dataset.manualUpdateUrlTemplate?.replace('__ID__', encodeURIComponent(id))
            : url;
        try {
            await fetch(requestUrl, {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify(values),
            }).then(async response => {
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.message || 'Unable to save applicant.');
                return result;
            });
            window.location.reload();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Unable to save applicant', text: error.message });
        }
    }

    function openManualCreateRow() {
        const table = document.getElementById('admin-applications-table');
        const button = document.querySelector('[data-action="add-applicant"]');
        if (!table || !button || openManualRow) return;
        const emptyRow = table.tBodies[0].querySelector('.empty-state-row')?.closest('tr');
        if (emptyRow) {
            openManualEmptyRow = emptyRow.outerHTML;
            emptyRow.remove();
        }
        const row = document.createElement('tbody');
        row.innerHTML = manualRowHtml({
            application_status: 'approved',
            date: table.dataset.today,
            grant_type: table.dataset.grantType === 'all' ? 'academic' : table.dataset.grantType,
        }, table.dataset.grantType, table.dataset.showAmount === '1', table);
        const newRow = row.firstElementChild;
        table.tBodies[0].prepend(newRow);
        openManualRow = newRow;
        setManualButtonState(true);
    }

    document.querySelector('[data-action="add-applicant"]')?.addEventListener('click', openManualCreateRow);
    document.getElementById('admin-applications-table')?.addEventListener('click', async event => {
        const table = document.getElementById('admin-applications-table');
        const editButton = event.target.closest('.manual-edit-btn');
        const deleteButton = event.target.closest('.manual-delete-btn');
        const saveButton = event.target.closest('.manual-save-btn');
        const cancelButton = event.target.closest('.manual-cancel-btn');

        if (saveButton) {
            await saveManualRow(saveButton.closest('tr'));
            return;
        }
        if (cancelButton) {
            const row = cancelButton.closest('tr');
            if (row.dataset.id) row.outerHTML = row.dataset.originalHtml;
            else row.remove();
            if (openManualEmptyRow && !table.tBodies[0].querySelector('tr')) {
                table.tBodies[0].insertAdjacentHTML('beforeend', openManualEmptyRow);
            }
            openManualEmptyRow = null;
            openManualRow = null;
            setManualButtonState(false);
            return;
        }
        if (editButton) {
            if (openManualRow) return;
            const row = editButton.closest('tr');
            row.dataset.originalHtml = row.outerHTML;
            const values = manualValuesFromRow(row);
            const replacement = document.createElement('tbody');
            const grantType = row.dataset.grantType || table.dataset.grantType;
            replacement.innerHTML = manualRowHtml(values, grantType, table.dataset.showAmount === '1', table);
            const editRow = replacement.firstElementChild;
            editRow.dataset.originalHtml = row.dataset.originalHtml;
            row.replaceWith(editRow);
            openManualRow = editRow;
            setManualButtonState(true);
            if (grantType === 'academic' || grantType === 'non-academic') {
                updateManualFunding(editRow);
                updateManualCollege(editRow);
            } else if (grantType === 'all') {
                updateManualFunding(editRow);
            }
            return;
        }
        if (deleteButton) {
            const result = await Swal.fire({ title: 'Delete applicant?', text: 'This manual applicant will be removed.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#b91c1c' });
            if (!result.isConfirmed) return;
            try {
                await fetch(`/admin/manual-applicants/${encodeURIComponent(deleteButton.dataset.id)}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } }).then(async response => { if (!response.ok) throw new Error('Unable to delete applicant.'); });
                window.location.reload();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Unable to delete applicant', text: error.message });
            }
        }
    });

    function updateManualFunding(row) {
        const scholarship = row.querySelector('[data-field="scholarship_name"]');
        const funding = row.querySelector('[data-derived-field="funding"]');
        if (scholarship && funding) funding.value = scholarship.selectedOptions[0]?.dataset.fundingType || '—';
    }

    async function updateManualCollege(row) {
        const table = row.closest('table');
        const course = row.querySelector('[data-field="course"]');
        const college = row.querySelector('[data-derived-field="college_department"]');
        if (!table || !course || !college) return;

        const selectedCourse = course.value;
        college.value = '';
        if (!selectedCourse) return;

        const url = new URL(table.dataset.collegeLookupUrl, window.location.origin);
        url.searchParams.set('course', selectedCourse);
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (response.ok && course.value === selectedCourse) college.value = result.college_department || '';
        } catch (error) {
            console.error('Unable to resolve college or department.', error);
        }
    }

    document.getElementById('admin-applications-table')?.addEventListener('change', event => {
        const row = event.target.closest('.manual-applicant-row');
        if (!row) return;

        if (event.target.matches('[data-field="grant_type"]')) {
            const selectedType = event.target.value;
            const scholarship = row.querySelector('[data-field="scholarship_name"]');
            if (scholarship) {
                scholarship.outerHTML = manualScholarshipOptions('', selectedType, row.closest('table'));
            }
            row.dataset.grantType = 'all';
            updateManualFunding(row);
            updateManualCollege(row);
            return;
        }

        if (row.dataset.grantType === 'all') {
            if (event.target.matches('[data-field="course"]')) updateManualCollege(row);
            if (event.target.matches('[data-field="scholarship_name"]')) updateManualFunding(row);
        } else if (row.dataset.grantType === 'academic' || row.dataset.grantType === 'non-academic') {
            if (event.target.matches('[data-field="course"]')) updateManualCollege(row);
            if (event.target.matches('[data-field="scholarship_name"]')) updateManualFunding(row);
        }
    });

    document.querySelectorAll('.delete-user-btn').forEach(button => button.addEventListener('click', async () => {
        const result = await Swal.fire({ title: 'Delete user?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#b91c1c', confirmButtonText: 'Delete' });
        if (!result.isConfirmed) return;
        try { await postJson(`/admin/users/${button.dataset.id}/delete`); button.closest('tr')?.remove(); } catch (error) { Swal.fire({ icon: 'error', title: 'Action failed', text: error.message }); }
    }));
})();
