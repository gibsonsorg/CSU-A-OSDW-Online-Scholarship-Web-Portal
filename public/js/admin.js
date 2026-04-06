(function(){
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let currentAppId = null;

function postJson(url, data, method = 'POST'){
    console.log(`Sending ${method} request to ${url}`, data);
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data||{})
    }).then(r => {
        console.log(`Response status: ${r.status}`);
        return r.text().then(text => {
            console.log(`Response text: ${text}`);
            let json = {};
            try {
                json = text ? JSON.parse(text) : {};
            } catch(e) {
                console.error('Failed to parse JSON:', e);
            }
            if (!r.ok) {
                throw new Error(json.error || json.message || `Server error ${r.status}`);
            }
            return json;
        });
    });
}

let currentProfileData = null;
let isEditMode = false;

document.querySelectorAll('.view-profile-btn').forEach(btn=>{
btn.addEventListener('click', function(){
    const id = this.dataset.id;
    currentAppId = id;
    isEditMode = false;
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileBody');
    body.innerHTML = 'Loading…';
    modal.style.display = 'block';
    fetch('/admin/applications/'+id)
        .then(r=>{ if(!r.ok) throw r; return r.json(); })
        .then(json=>{
            const app = json.data || json;
            currentProfileData = app;
            let html = '';
            
            // Helper function to create a field
            const field = (label, value, fieldName) => {
                return `<div style="display:flex; flex-direction:column;" data-field="${fieldName}">
                    <label style="font-weight:600; color:#333; font-size:13px; margin-bottom:6px;">${label}</label>
                    <span class="field-value" style="color:#666; font-size:14px; padding:10px; background:#f5f5f5; border-radius:6px; word-break:break-word;">${value || 'N/A'}</span>
                    <input type="text" class="field-input" style="display:none; color:#333; font-size:14px; padding:8px; border: 1px solid #ddd; border-radius:6px; box-sizing:border-box;" value="${value || ''}">
                </div>`;
            };
            
            const selectField = (label, value, fieldName, options) => {
                let optionsHtml = options.map(opt => `<option value="${opt}"${value === opt ? ' selected' : ''}>${opt}</option>`).join('');
                return `<div style="display:flex; flex-direction:column;" data-field="${fieldName}">
                    <label style="font-weight:600; color:#333; font-size:13px; margin-bottom:6px;">${label}</label>
                    <span class="field-value" style="color:#666; font-size:14px; padding:10px; background:#f5f5f5; border-radius:6px;">${value || 'N/A'}</span>
                    <select class="field-input" style="display:none; color:#333; font-size:14px; padding:8px; border: 1px solid #ddd; border-radius:6px; box-sizing:border-box;">
                        <option value="">Select...</option>${optionsHtml}
                    </select>
                </div>`;
            };
            
            // Helper function to format date
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            };
            
            html += field('First Name', app.first_name, 'first_name');
            html += field('Middle Name', app.middle_name, 'middle_name');
            html += field('Last Name', app.last_name, 'last_name');
            html += selectField('Sex', app.sex, 'sex', ['Male', 'Female']);
            html += field('Status', app.status, 'status');
            html += field('Email', app.email, 'email');
            html += field('Contact Number', app.contact_number, 'contact_number');
            html += field('Course', app.course, 'course');
            html += selectField('Year Level', app.year_level, 'year_level', ['1st Year', '2nd Year', '3rd Year', '4th Year']);
            html += field('Scholarship Name', app.scholarship_name, 'scholarship_name');
            html += field('Scholarship Type', app.scholarship_type, 'scholarship_type');
            html += field('Home Address', app.home_address, 'home_address');
            
            // Academic-specific fields
            if (app.grant_type === 'academic') {
                html += field('Program', app.program, 'program');
                html += field('ID Number', app.id_number, 'id_number');
                html += `<div style="display:flex; flex-direction:column;" data-field="birthdate">
                    <label style="font-weight:600; color:#333; font-size:13px; margin-bottom:6px;">Birthdate</label>
                    <span class="field-value" style="color:#666; font-size:14px; padding:10px; background:#f5f5f5; border-radius:6px;">${formatDate(app.birthdate)}</span>
                    <input type="date" class="field-input" style="display:none; color:#333; font-size:14px; padding:8px; border: 1px solid #ddd; border-radius:6px; box-sizing:border-box;" value="${app.birthdate ? app.birthdate.split('T')[0] : ''}">
                </div>`;
                html += field('Birthplace', app.birthplace, 'birthplace');
                html += field('Religion', app.religion, 'religion');
            }
            
            if (app.uploads && Array.isArray(app.uploads) && app.uploads.length){
                let uploadLinks = app.uploads.map(u=>(`<a href="/storage/${u}" target="_blank" style="color:#007bff; text-decoration:none; margin-right:8px;">View File</a>`)).join('');
                html += `<div style="display:flex; flex-direction:column; grid-column:1/-1;">
                    <label style="font-weight:600; color:#333; font-size:13px; margin-bottom:6px;">Uploaded Files</label>
                    <div style="color:#666; font-size:14px; padding:10px; background:#f5f5f5; border-radius:6px;">${uploadLinks}</div>
                </div>`;
            }
            
        if(app.application_status){
            const statusColor = app.application_status === 'approved' ? '#28a745' : app.application_status === 'rejected' ? '#dc3545' : '#ffc107';
            const statusBg = app.application_status === 'approved' ? '#e8f5e9' : app.application_status === 'rejected' ? '#ffebee' : '#fff8e1';
            html += `<div style="display:flex; flex-direction:column; grid-column:1/-1;">
                <label style="font-weight:600; color:#333; font-size:13px; margin-bottom:6px;">Application Status</label>
                <span style="color:${statusColor}; background:${statusBg}; padding:8px 12px; border-radius:6px; font-weight:600; text-transform:capitalize; text-align:center;">${app.application_status}</span>
            </div>`;
        }
        
        body.innerHTML = html;
    })
    .catch(()=>{ body.innerHTML = '<span style="grid-column:1/-1; color:#dc3545;">Unable to load profile.</span>'; });
});
});

// Edit profile button
document.getElementById('editProfile').addEventListener('click', function(){
    if (!currentAppId || !currentProfileData) return;
    isEditMode = !isEditMode;
    const body = document.getElementById('profileBody');
    const fields = body.querySelectorAll('[data-field]');
    
    fields.forEach(field => {
        const fieldName = field.getAttribute('data-field');
        const valueSpan = field.querySelector('.field-value');
        const input = field.querySelector('.field-input');
        
        if (isEditMode) {
            valueSpan.style.display = 'none';
            input.style.display = 'block';
        } else {
            valueSpan.style.display = 'block';
            input.style.display = 'none';
        }
    });
    
    // Toggle Save button visibility
    const actionButtons = document.querySelector('.modal-actions');
    let saveBtn = document.getElementById('saveProfileBtn');
    let cancelBtn = document.getElementById('cancelEditBtn');
    
    if (isEditMode) {
        this.style.display = 'none';
        
        if (!saveBtn) {
            saveBtn = document.createElement('button');
            saveBtn.id = 'saveProfileBtn';
            saveBtn.className = 'btn';
            saveBtn.style.background = '#28a745';
            saveBtn.textContent = 'Save Changes';
            actionButtons.insertBefore(saveBtn, actionButtons.firstChild);
        } else {
            saveBtn.style.display = 'block';
        }
        
        if (!cancelBtn) {
            cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancelEditBtn';
            cancelBtn.className = 'btn';
            cancelBtn.style.background = '#6c757d';
            cancelBtn.textContent = 'Cancel';
            actionButtons.insertBefore(cancelBtn, saveBtn.nextSibling);
        } else {
            cancelBtn.style.display = 'block';
        }
    } else {
        this.style.display = 'block';
        if (saveBtn) saveBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';
    }
});

// Save profile changes
document.addEventListener('click', function(e) {
    if (e.target.id === 'saveProfileBtn') {
        if (!currentAppId) return;
        
        const body = document.getElementById('profileBody');
        const fields = body.querySelectorAll('[data-field]');
        const updateData = {};
        
        fields.forEach(field => {
            const fieldName = field.getAttribute('data-field');
            const input = field.querySelector('.field-input');
            if (input) {
                updateData[fieldName] = input.value;
            }
        });
        
        postJson('/admin/applications/'+currentAppId+'/update', updateData)
            .then(response => {
                alert('Profile updated successfully!');
                isEditMode = false;
                // Reload profile
                document.querySelector('.view-profile-btn[data-id="'+currentAppId+'"]').click();
            })
            .catch(err => {
                alert('Error updating profile: ' + err.message);
            });
    }
});

// Cancel edit
document.addEventListener('click', function(e) {
    if (e.target.id === 'cancelEditBtn') {
        isEditMode = false;
        const body = document.getElementById('profileBody');
        const fields = body.querySelectorAll('[data-field]');
        
        fields.forEach(field => {
            const valueSpan = field.querySelector('.field-value');
            const input = field.querySelector('.field-input');
            valueSpan.style.display = 'block';
            input.style.display = 'none';
        });
        
        const editBtn = document.getElementById('editProfile');
        editBtn.style.display = 'block';
        
        const saveBtn = document.getElementById('saveProfileBtn');
        const cancelBtn = document.getElementById('cancelEditBtn');
        if (saveBtn) saveBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';
    }
});

        const confirmModal = document.getElementById('confirmActionModal');
        const confirmActionText = document.getElementById('confirmActionText');
        const confirmYesBtn = document.getElementById('confirmYesBtn');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');
        let confirmResolve = null;

        function showConfirm(message) {
            return new Promise((resolve) => {
                confirmActionText.innerText = message;
                confirmModal.style.display = 'block';
                confirmResolve = resolve;
            });
        }

        function closeConfirm() {
            confirmModal.style.display = 'none';
            confirmResolve = null;
        }
        // updates status to approved student sees checkmark
        confirmYesBtn.addEventListener('click', function () {
            if (confirmResolve) confirmResolve(true);
            closeConfirm();
        });

        confirmCancelBtn.addEventListener('click', function () {
            if (confirmResolve) confirmResolve(false);
            closeConfirm();
        });

        document.getElementById('approveProfile').addEventListener('click', function(){
            if (!currentAppId) return;
            showConfirm('Are you sure you want to APPROVE this application?').then(confirmed => {
                if (!confirmed) return;
                postJson('/admin/applications/'+currentAppId+'/approve', {}).then(()=> location.reload());
            });
        });

        document.getElementById('rejectProfile').addEventListener('click', function(){
            if (!currentAppId) return;
            showConfirm('Are you sure you want to REJECT this application?').then(confirmed => {
                if (!confirmed) return;
                showRejectionReasonModal();
            });
        });

        // Rejection reason modal
        function showRejectionReasonModal() {
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            
            // Create modal content
            const modal = document.createElement('div');
            modal.style.cssText = 'background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
            modal.innerHTML = `
                <button style="float: right; background: none; border: none; font-size: 24px; cursor: pointer; color: #999;" id="closeRejectModal">✕</button>
                <h2 style="margin: 0 0 10px 0; color: #333; font-size: 24px;">Rejection Reason</h2>
                <p style="color: #666; margin: 0 0 15px 0;">Please provide a reason for rejecting this application:</p>
                <textarea id="rejectionReasonInput" placeholder="Enter rejection reason..." style="width: 100%; min-height: 120px; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: Arial; font-size: 14px; box-sizing: border-box; resize: vertical;"></textarea>
                <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: center;">
                    <button id="confirmRejectionBtn" style="padding: 10px 30px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Reject Application</button>
                </div>
            `;
            
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            
            // Focus on textarea
            setTimeout(() => {
                document.getElementById('rejectionReasonInput').focus();
            }, 100);
            
            // Close handlers
            function closeModal() {
                overlay.remove();
            }
            
            document.getElementById('closeRejectModal').addEventListener('click', closeModal);
            
            // Confirm handler
            document.getElementById('confirmRejectionBtn').addEventListener('click', function(){
                const reason = document.getElementById('rejectionReasonInput').value.trim();
                if (!reason) {
                    alert('Please enter a rejection reason');
                    return;
                }
                closeModal();
                postJson('/admin/applications/'+currentAppId+'/reject', {reason: reason}).then(()=> location.reload());
            });
            
            // Close on overlay click
            overlay.addEventListener('click', function(e){
                if (e.target === overlay) closeModal();
            });
        }

        document.getElementById('deleteProfile').addEventListener('click', function(){
            if (!currentAppId) {
                alert('No application selected');
                return;
            }
            showConfirm('Are you sure you want to DELETE this application?').then(confirmed => {
                if (!confirmed) return;
                console.log('Deleting application ID:', currentAppId);
                postJson('/admin/applications/'+currentAppId+'/delete', {}, 'POST')
                    .then(json => {
                        console.log('Delete response:', json);
                        if (json.status === 'ok') {
                            console.log('Delete successful, closing modal and removing row');
                            // Close the modal
                            document.getElementById('profileModal').style.display = 'none';
                            
                            // Find and remove the row by data-id
                            const buttons = document.querySelectorAll(`button[data-id="${currentAppId}"]`);
                            console.log('Found buttons:', buttons.length);
                            if (buttons.length > 0) {
                                const row = buttons[0].closest('tr');
                                if (row) {
                                    console.log('Found row, removing...');
                                    row.style.opacity = '0.5';
                                    setTimeout(() => {
                                        row.remove();
                                        console.log('Row removed');
                                    }, 300);
                                } else {
                                    console.warn('Could not find row for button');
                                }
                            } else {
                                console.warn('No button found with data-id=' + currentAppId);
                            }
                            
                            // Show success message
                            alert('Application deleted successfully!');
                            currentAppId = null;
                        } else {
                            throw new Error(json.error || 'Delete failed');
                        }
                    })
                    .catch(err => {
                        console.error('Delete error:', err);
                        alert('Unable to delete application: ' + err.message);
                    });
            });
        });

        document.getElementById('closeProfile').addEventListener('click', function(){
            document.getElementById('profileModal').style.display = 'none';
            currentAppId = null;
        });

    // --- Dynamic admin users loader: fetch users and replace table in-place ---
    function renderUsersTable(users){
        const container = document.getElementById('admin-table-container');
        if (!container) return;
        let html = `<table><thead><tr>
            <th>ID</th>
            <th>Name</th>
            <th>Student ID</th>
            <th>Email</th>
            <th>ID Document</th>
            <th>Registered</th>
            <th>Actions</th>
        </tr></thead><tbody>`;
        if (!users || users.length === 0) {
            html += `<tr><td colspan="6" class="text-center text-gray">No users found.</td></tr>`;
        } else {
            users.forEach(u => {
                const idLink = `<a href="#" data-user-id="${u.id}" class="user-link">${u.id}</a>`;
                const name = (u.name || '') + (u.last_name ? (' ' + u.last_name) : '');
                const studentId = u.student_id || '';
                const email = u.email || '';
                const idDoc = u.id_document ? (`<a href="/storage/${u.id_document}" target="_blank">View</a>`) : 'No ID uploaded';
                const reg = u.created_at ? (new Date(u.created_at)).toISOString().split('T')[0] : '';
                const deleteBtn = `<button class="btn small delete-user-btn" data-id="${u.id}" style="background:#dc3545;color:#fff;border:none;padding:6px 8px;border-radius:4px;cursor:pointer;">Delete</button>`;
                html += `<tr data-user-id="${u.id}"><td>${idLink}</td><td>${escapeHtml(name)}</td><td>${escapeHtml(studentId)}</td><td>${escapeHtml(email)}</td><td>${idDoc}</td><td>${reg}</td><td>${deleteBtn}</td></tr>`;
            });
        }
        html += `</tbody></table>`;
        container.innerHTML = html;
    }

    function escapeHtml(str){
        if (!str) return '';
        return String(str).replace(/[&<>"'`]/g, function(s){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'})[s];
        });
    }

    function loadUsers(url){
        fetch(url, { headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }})
            .then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.json();
            })
            .then(json => {
                const users = json.users || [];
                renderUsersTable(users);
            })
            .catch(err => {
                console.error('Failed to load users:', err);
                const container = document.getElementById('admin-table-container');
                if (container) container.innerHTML = '<div class="text-center text-gray">Unable to load users.</div>';
            });
    }

    const usersLink = document.getElementById('adminUsersLink');
    if (usersLink){
        usersLink.addEventListener('click', function(e){
            e.preventDefault();
            const url = this.dataset.url || '/admin/users';
            loadUsers(url);
            // update nav active state
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(n=> n.classList.remove('active'));
            this.classList.add('active');
        });
    }

    // Delegate delete user button clicks
    document.addEventListener('click', function(e){
        const el = e.target.closest && e.target.closest('.delete-user-btn');
        if (!el) return;
        e.preventDefault();
        const userId = el.getAttribute('data-id');
        if (!userId) return;
        showConfirm('Are you sure you want to DELETE this user?').then(confirmed => {
            if (!confirmed) return;
            postJson('/admin/users/'+userId+'/delete', {}, 'POST')
                .then(json => {
                    if (json.status === 'ok') {
                        const row = el.closest('tr');
                        if (row) row.remove();
                        alert('User deleted');
                    } else {
                        throw new Error(json.message || 'Delete failed');
                    }
                })
                .catch(err => {
                    alert('Failed to delete user: ' + err.message);
                });
        });
    });
    })();
