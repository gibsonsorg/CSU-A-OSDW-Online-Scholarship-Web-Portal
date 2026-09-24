(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let currentAppId = null;
    let currentStep = 1;

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

    function reviewArea(section, status, reason) {
        const safeStatus = status || 'pending';
        return `<div class="review-section" data-section="${section}">
            <div><span class="review-status ${safeStatus}">Status: ${escapeHtml(safeStatus)}</span>${reason ? `<span>Reason: ${escapeHtml(reason)}</span>` : ''}</div>
            <div class="review-actions">
                
                <button type="button" class="review-approve" data-action="approve">✓ Approve</button>
                <button type="button" class="review-reject" data-action="reject">✕ Reject</button>
                <button type="button" class="review-delete" data-action="delete">Delete</button>
            </div>
        </div>`;
    }

    function documentGroup(label, files, group, reviews) {
        if (!Array.isArray(files) || !files.length) return '';
        return files.map((file, index) => {
            const key = `${group}.${index}`;
            const review = reviews?.[key] || {};
            const status = review.status || 'pending';
            const url = `/admin/applications/${encodeURIComponent(currentAppId)}/document/${encodeURIComponent(group)}/${index}`;
            return `<div class="document-review full">
                <label>${escapeHtml(label)}${files.length > 1 ? ` ${index + 1}` : ''}</label>
                <div class="document-actions">
                    <a class="document-view" href="${url}" target="_blank" rel="noopener noreferrer">View File</a>
                    <span class="review-status ${status}">Status: ${escapeHtml(status)}</span>
                    <button type="button" class="document-approve" data-document-group="${escapeHtml(group)}" data-document-index="${index}">Approve</button>
                    <button type="button" class="document-reject" data-document-group="${escapeHtml(group)}" data-document-index="${index}">Reject</button>
                </div>
                ${review.reason ? `<div>Reason: ${escapeHtml(review.reason)}</div>` : ''}
            </div>`;
        }).join('');
    }

    function renderProfile(app) {
        const body = document.getElementById('profileBody');
        const isNonAcademic = app.grant_type === 'non-academic';
        let uploads = app.uploads || {};
        if (Array.isArray(uploads)) uploads = { uploads };
        const step1Fields = [
            field('First name', app.first_name, 'first_name'), field('Middle name', app.middle_name, 'middle_name'),
            field('Last name', app.last_name, 'last_name'), field('Email', app.email, 'email'),
            field('Student ID Number', app.student_id_number || app.id_number, 'student_id_number'), field('Course', app.course, 'course'),
            field('Year Level', app.year_level, 'year_level')
        ];
        if (!isNonAcademic) step1Fields.push(field('Program', app.program, 'program'));
        const step1 = step1Fields.join('') + reviewArea('student-information', app.student_information_status, app.student_information_reason);
        const step2Fields = isNonAcademic ? [
            field('Sex', app.sex, 'sex'), field('Status', app.status, 'status'),
            field('Contact Number', app.contact_number, 'contact_number'), field('Home Address', app.home_address, 'home_address')
        ] : [
                field('Birthday', app.birthdate ? String(app.birthdate).split('T')[0] : '', 'birthdate'),
                field('Birthplace', app.birthplace, 'birthplace'), field('Sex', app.sex, 'sex'),
                field('Status', app.status, 'status'), field('Religion', app.religion, 'religion'),
                field('Contact Number', app.contact_number, 'contact_number'), field('Home Address', app.home_address, 'home_address')
            ];
        const step2 = step2Fields.join('') + reviewArea('personal-information', app.personal_information_status, app.personal_information_reason);
        const documents = [
            documentGroup('Certificate of Grades', uploads.certificate_of_grades || uploads.grades, uploads.certificate_of_grades ? 'certificate_of_grades' : 'grades', app.document_review || app.document_reviews),
            documentGroup('Certificate of Enrollment', uploads.certificate_of_enrollment || uploads.enrollment, uploads.certificate_of_enrollment ? 'certificate_of_enrollment' : 'enrollment', app.document_review || app.document_reviews),
            documentGroup('Certificate from Sports Coordinator', uploads.certificate_of_sports_coordinator || uploads.uploads, 'certificate_of_sports_coordinator', app.document_review || app.document_reviews),
            documentGroup('Uploaded Files', uploads.uploads, 'uploads', app.document_review || app.document_reviews),
            documentGroup('Certificate of Adviser', uploads.certificate_of_adviser, 'certificate_of_adviser', app.document_review || app.document_reviews)
        ].join('');
        const step3 = field('Name of Scholarship / Sponsor', app.scholarship_name, 'scholarship_name') +
            field('Types of Scholarship', app.scholarship_type, 'scholarship_type') +
            `<div class="full"><label>Uploaded documents</label>${documents || '<div class="input">No files uploaded.</div>'}</div>`;
        body.innerHTML = `<div class="academic-review-step" data-profile-step="1">${step1}</div><div class="academic-review-step" data-profile-step="2">${step2}</div><div class="academic-review-step" data-profile-step="3">${step3}</div>`;
        updateStepUI();
    }

    function updateStepUI() {
        document.querySelectorAll('#profileModal [data-profile-step]').forEach(panel => panel.classList.toggle('active', Number(panel.dataset.profileStep) === currentStep));
        document.querySelectorAll('#profileModal [data-profile-progress-step]').forEach(step => {
            const value = Number(step.dataset.profileProgressStep);
            step.classList.toggle('active', value === currentStep);
            step.classList.toggle('completed', value < currentStep);
        });
        document.getElementById('profileStepCounter').textContent = currentStep;
        document.getElementById('profilePageCounter').textContent = `${currentStep} / 3`;
        document.getElementById('profilePrevious').disabled = currentStep === 1;
        document.getElementById('profileNext').disabled = currentStep === 3;
        document.getElementById('profilePrevious').style.visibility = 'visible';
        document.getElementById('profilePreviousTop').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        document.getElementById('profileNext').style.visibility = 'visible';
    }

    async function loadProfile(id) {
        const response = await fetch(`/admin/applications/${encodeURIComponent(id)}`);
        if (!response.ok) throw new Error('Unable to load profile');
        renderProfile(await response.json());
    }

    async function rejectWithReason(title, url) {
        const result = await Swal.fire({ title, input: 'textarea', inputLabel: 'Reason', inputPlaceholder: 'Enter rejection reason...', showCancelButton: true, confirmButtonText: 'Reject', confirmButtonColor: '#b91c1c', inputValidator: value => !value.trim() ? 'A reason is required.' : undefined });
        if (!result.isConfirmed) return;
        await postJson(url, { reason: result.value.trim() });
        await loadProfile(currentAppId);
    }

    // CHANGE: Open the approved-applicant bulk amount editor from the filter bar.
    async function addAmountToFilteredApplicants() {
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
            text: 'This will update every applicant matching the current Academic Grants filters.',
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
                grant_type: document.querySelector('[data-action="add-amount"]')?.dataset.grantType || '',
                date_from: params.get('date_from') || '',
                date_to: params.get('date_to') || '',
                scholarship_name: params.get('scholarship_name') || '',
                status: params.get('status') || '',
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

    document.getElementById('profilePrevious')?.addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateStepUI(); } });
    document.getElementById('profilePreviousTop')?.addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateStepUI(); } });
    document.getElementById('profileNext')?.addEventListener('click', () => { if (currentStep < 3) { currentStep++; updateStepUI(); } });
    document.getElementById('closeProfile')?.addEventListener('click', () => document.getElementById('profileModal').classList.remove('open'));

    document.getElementById('profileBody')?.addEventListener('click', async event => {
        const documentButton = event.target.closest('[data-document-group]');
        const reviewSection = event.target.closest('.review-section');
        try {
            if (documentButton) {
                const group = documentButton.dataset.documentGroup;
                const index = documentButton.dataset.documentIndex;
                const url = `/admin/applications/${currentAppId}/documents/${encodeURIComponent(group)}/${index}`;
                if (documentButton.classList.contains('document-reject')) await rejectWithReason('Reject Document', `${url}/reject`);
                else { await postJson(`${url}/approve`); await loadProfile(currentAppId); }
                return;
            }
            if (!reviewSection) return;
            const action = event.target.dataset.action;
            const section = reviewSection.dataset.section;
            if (action === 'approve') { await postJson(`/admin/applications/${currentAppId}/sections/${section}/approve`); await loadProfile(currentAppId); }
            if (action === 'reject') await rejectWithReason(`Reject ${section.replaceAll('-', ' ')}`, `/admin/applications/${currentAppId}/sections/${section}/reject`);
            if (action === 'delete') {
                const result = await Swal.fire({ title: 'Delete application?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#b91c1c', confirmButtonText: 'Delete' });
                if (result.isConfirmed) { await postJson(`/admin/applications/${currentAppId}/delete`); location.reload(); }
            }
            if (action === 'edit') {
                const panel = reviewSection.closest('.academic-review-step');
                panel.querySelectorAll('[data-field]').forEach(input => { input.readOnly = false; input.style.background = '#fff'; });
                event.target.textContent = 'Save'; event.target.dataset.action = 'save';
            } else if (action === 'save') {
                const data = {}; reviewSection.closest('.academic-review-step').querySelectorAll('[data-field]').forEach(input => { data[input.dataset.field] = input.value === 'N/A' ? '' : input.value; });
                await postJson(`/admin/applications/${currentAppId}/update`, data); await loadProfile(currentAppId);
            }
        } catch (error) { Swal.fire({ icon: 'error', title: 'Action failed', text: error.message }); }
    });

    // CHANGE: Bind the approved-filter-only Add Amount button.
    document.querySelector('[data-action="add-amount"]')?.addEventListener('click', addAmountToFilteredApplicants);

    // CHANGE: Inline create/edit/delete controls for manually logged applicants.
    const manualApplicantFields = ['last_name', 'first_name', 'middle_name', 'sex', 'course', 'year_level', 'scholarship_name', 'date', 'application_status'];
    let openManualRow = null;
    let openManualEmptyRow = null;

    function manualScholarshipOptions(selectedValue, grantType) {
        const filterSelect = document.getElementById('grant-scholarship-name');
        const canonicalOptions = [];

        if (filterSelect) {
            Array.from(filterSelect.options).forEach(option => {
                if (option.value) canonicalOptions.push(option.value);
            });
        }

        const grantTypeMatches = grantType === 'academic' || grantType === 'non-academic';
        if (!canonicalOptions.length && grantTypeMatches) {
            const fallback = Array.from(document.querySelectorAll('#grant-scholarship-name option')).map(option => option.value).filter(Boolean);
            canonicalOptions.push(...fallback);
        }

        if (!canonicalOptions.length) {
            canonicalOptions.push(selectedValue || '');
        }

        return `<select class="manual-row-input" data-field="scholarship_name"><option value="">Select</option>${canonicalOptions.map(option => `<option value="${escapeHtml(option)}" ${selectedValue === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}</select>`;
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

    function manualRowHtml(values, grantType, showAmount) {
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
        values.grant_type = row.closest('table').dataset.grantType;
        return values;
    }

    async function saveManualRow(row) {
        const values = collectManualValues(row);
        const missing = manualApplicantFields.some(field => field !== 'middle_name' && !values[field]);
        if (missing) {
            await Swal.fire({ icon: 'error', title: 'Incomplete applicant', text: 'Please complete all required fields.' });
            return;
        }

        const id = row.dataset.id;
        const url = id ? `/admin/manual-applicants/${encodeURIComponent(id)}` : '/admin/manual-applicants';
        try {
            await fetch(url, {
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
        row.innerHTML = manualRowHtml({ application_status: 'pending' }, table.dataset.grantType, table.dataset.showAmount === '1');
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
            replacement.innerHTML = manualRowHtml(values, row.dataset.grantType || table.dataset.grantType, table.dataset.showAmount === '1');
            const editRow = replacement.firstElementChild;
            editRow.dataset.originalHtml = row.dataset.originalHtml;
            row.replaceWith(editRow);
            openManualRow = editRow;
            setManualButtonState(true);
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

    document.querySelectorAll('.delete-user-btn').forEach(button => button.addEventListener('click', async () => {
        const result = await Swal.fire({ title: 'Delete user?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#b91c1c', confirmButtonText: 'Delete' });
        if (!result.isConfirmed) return;
        try { await postJson(`/admin/users/${button.dataset.id}/delete`); button.closest('tr')?.remove(); } catch (error) { Swal.fire({ icon: 'error', title: 'Action failed', text: error.message }); }
    }));
})();
