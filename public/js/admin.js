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

document.querySelectorAll('.view-profile-btn').forEach(btn=>{
btn.addEventListener('click', function(){
    const id = this.dataset.id;
    currentAppId = id;
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileBody');
    body.innerHTML = 'Loading…';
    modal.style.display = 'block';
    fetch('/admin/applications/'+id)
        .then(r=>{ if(!r.ok) throw r; return r.json(); })
        .then(json=>{
            const app = json.data || json;
            let html = '';
            
            // Helper function to create a field
            const field = (label, value) => {
                return `<div style="display:flex; flex-direction:column;">
                    <label style="font-weight:600; color:#333; font-size:13px; margin-bottom:6px;">${label}</label>
                    <span style="color:#666; font-size:14px; padding:10px; background:#f5f5f5; border-radius:6px; word-break:break-word;">${value || 'N/A'}</span>
                </div>`;
            };
            
            // Helper function to format date
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            };
            
            html += field('First Name', app.first_name);
            html += field('Middle Name', app.middle_name);
            html += field('Last Name', app.last_name);
            html += field('Sex', app.sex);
            html += field('Status', app.status);
            html += field('Email', app.email);
            html += field('Contact Number', app.contact_number);
            html += field('Course', app.course);
            html += field('Year Level', app.year_level);
            html += field('Scholarship Name', app.scholarship_name);
            html += field('Scholarship Type', app.scholarship_type);
            html += field('Home Address', app.home_address);
            
            // Academic-specific fields
            if (app.grant_type === 'academic') {
                html += field('Program', app.program);
                html += field('ID Number', app.id_number);
                html += field('Birthdate', formatDate(app.birthdate));
                html += field('Birthplace', app.birthplace);
                html += field('Religion', app.religion);
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
    })();
