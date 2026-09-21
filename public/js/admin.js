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

    document.querySelectorAll('.delete-user-btn').forEach(button => button.addEventListener('click', async () => {
        const result = await Swal.fire({ title: 'Delete user?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#b91c1c', confirmButtonText: 'Delete' });
        if (!result.isConfirmed) return;
        try { await postJson(`/admin/users/${button.dataset.id}/delete`); button.closest('tr')?.remove(); } catch (error) { Swal.fire({ icon: 'error', title: 'Action failed', text: error.message }); }
    }));
})();
