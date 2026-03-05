(function(){
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let currentAppId = null;

function postJson(url, data){
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data||{})
    }).then(r=>r.json());
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
            
            html += field('First Name', app.first_name);
            html += field('Middle Name', app.middle_name);
            html += field('Last Name', app.last_name);
            html += field('Sex', app.sex);
            html += field('Status', app.status);
            html += field('Email', app.email);
            html += field('Contact Number', app.contact_number);
            html += field('Course', app.course);
            html += field('Scholarship Name', app.scholarship_name);
            html += field('Scholarship Type', app.scholarship_type);
            html += field('Home Address', app.home_address);
            
            if (app.uploads && Array.isArray(app.uploads) && app.uploads.length){
                let uploadLinks = app.uploads.map(u=>(`<a href="/storage/${u}" target="_blank" style="color:#007bff; text-decoration:none; margin-right:8px;">📄 View File</a>`)).join('');
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

        document.getElementById('approveProfile').addEventListener('click', function(){
            if (!currentAppId || !confirm('Approve this application?')) return;
            postJson('/admin/applications/'+currentAppId+'/approve', {}).then(()=> location.reload());
        });

        document.getElementById('rejectProfile').addEventListener('click', function(){
            if (!currentAppId || !confirm('Reject this application?')) return;
            postJson('/admin/applications/'+currentAppId+'/reject', {}).then(()=> location.reload());
        });

        document.getElementById('closeProfile').addEventListener('click', function(){
            document.getElementById('profileModal').style.display = 'none';
            currentAppId = null;
        });
    })();
