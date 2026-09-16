<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Dashboard — CSU GMS</title>

<link rel="stylesheet" href="{{ asset('css/admin.css') }}">  
        <style>
        .topbar-right .btn {
            margin-right: 4px;
        }

        #admin-table-container .old-grants-pagination svg {
            width: 1rem;
            height: 1rem;
        }
    </style>
    </head>
<body>
    <div class="sidebar" id="adminSidebar">
    <a href="/admin" class="sidebar-logo">
        <div class="logo-img">
            <img src="{{ asset('images/csua-nobg.png') }}" alt="CSU Logo">
        </div>
        <div class="logo-text">
            <h2>CSU Grants</h2>
            <p>Management System</p>
        </div>
    </a>

    
    <nav class="sidebar-nav">

        <div class="nav-label">Main</div>

        <a href="/admin"
           class="nav-item ">
            <span class="nav-icon"></span> Dashboard
        </a>

        <div class="nav-label">Grants</div>

        <a href="/admin/academic"
           class="nav-item ">
            <span class="nav-icon"></span> Academic Grants
            <span class="nav-badge">0</span>
        </a>

        <a href="/admin/non-academic"
           class="nav-item ">
            <span class="nav-icon"></span> Non-Academic
            <span class="nav-badge">0</span>
        </a>

        <div style="margin-top: 12px; padding-left: 12px; font-size: 13px; color: #666; font-weight: 500;">Old Grants</div>

        <a href="/admin/old-academic"
           class="nav-item ">
            <span class="nav-icon"></span> Old Academic Grants
        </a>

        <a href="/admin/old-non-academic"
           class="nav-item ">
            <span class="nav-icon"></span> Old Non-Academic Grants
        </a>

        
      

        
                    <div class="nav-label">Administration</div>

            <a href="/admin"
               class="nav-item ">
                <span class="nav-icon"></span> Summary of Reports
                <span class="nav-badge">0</span>
            </a>


            <a href="/admin/users"
               class="nav-item ">
                <span class="nav-icon"></span> Users
            </a>

            <a href="/admin/settings"
               class="nav-item ">
                <span class="nav-icon"></span>Settings
            </a>

            <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button id="adminLogoutBtn" type="submit" class="nav-item" data-logout-url="{{ route('logout') }}" style="background:none;border:none;padding:12px 16px;text-align:left;width:100%;cursor:pointer;"> 
                    <span class="nav-icon"></span>Logout
                </button>
            </form>

        
       

       
    </nav>
    <div class="sidebar-user">
        <div class="u-avatar">
            SA
        </div>
        <div>
            <div class="u-name">System Administrator</div>
            <div class="u-role">Admin · Aparri</div>
        </div>
   
    </div>
</div>
    <div class="main" id="adminMain">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
            <span class="hamburger-icon"></span>
            <span class="hamburger-icon"></span>
            <span class="hamburger-icon"></span>
            <img class="arrow-icon" src="{{ asset('images/arrow.svg') }}" alt="Arrow icon">
        </button>

        <div class="topbar">
            <div class="topbar-left">
                <h1>Admin Dashboard</h1>
                <p>CSU Grants Management — System Overview</p>
            </div>
        </div>

        <div class="content">
            <div class="stats-row stats-4">
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">{{ $stats['total_grants'] ?? 0 }}</div>
            <div class="stat-label">Total Grants</div>
            <div class="stat-change up">↑ {{ $stats['grants_added_this_month'] ?? 0 }} added this month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">{{ $stats['total_applications'] ?? 0 }}</div>
            <div class="stat-label">Total Applications</div>
            <div class="stat-change up">↑ {{ $stats['applications_this_week'] ?? 0 }} this week</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">{{ $stats['approved'] ?? 0 }}</div>
            <div class="stat-label">Approved</div>
            <div class="stat-change up">{{ $stats['approval_rate'] ?? 0 }}% approval rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"> </div>
            <div class="stat-val">{{ $stats['registered_users'] ?? 0 }}</div>
            <div class="stat-label">Registered Users</div>
            <div class="stat-change up">↑ {{ $stats['new_registered_this_month'] ?? 0 }} new this month</div>
        </div>
    </div>

<div class="grid-2"> 
<div class="card">
    <div class="card-title">
        @if($filterType === 'academic')
            Academic Grants
        @elseif($filterType === 'non-academic')
            Non-Academic Grants
        @elseif($filterType === 'old-academic')
            Old Academic Grants
            <button class="btn small" onclick="openOldGrantUploadModal()" style="margin-left: auto;">Upload Old Data</button>
        @elseif($filterType === 'old-non-academic')
            Old Non-Academic Grants
            <button class="btn small" onclick="openOldGrantUploadModal()" style="margin-left: auto;">Upload Old Data</button>
        @elseif($filterType === 'users')
            Registered Users
        @else
            Recent Applications
        @endif
        @if(in_array($filterType, ['academic', 'non-academic', 'old-academic', 'old-non-academic'], true))
            @include('admin.partials.grant-filters', ['filterType' => $filterType])
        @elseif($filterType === 'users')
            @include('admin.partials.users-filters')
        @elseif($filterType === 'all')
            @include('admin.partials.application-filters')
        @endif
        @if($filterType !== 'old-academic' && $filterType !== 'old-non-academic')
            <a href="http://127.0.0.1:8000/admin/applications" class="card-action">View All →</a>
        @endif
    </div>
    <div id="admin-table-container">
        @if($filterType === 'users')
            @include('admin.partials.users-table', ['users' => $users])
        @elseif($filterType === 'old-academic' || $filterType === 'old-non-academic')
        <table>
            <thead>
                <tr>
                    <th>Sequence No.</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Sex</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Scholarship / Financial Assistance</th>
                    <th>Type of Scholarship</th>
                    <th>Funding</th>
                    <th>Campus</th>
                    <th>Amount of Financial Assistance</th>
                </tr>
            </thead>
            <tbody>
            @if($oldRecords->count() > 0)
                @foreach($oldRecords as $record)
                    <tr>
                        <td>{{ $record->sequence_no ?? '' }}</td>
                        <td>{{ $record->last_name ?? '' }}</td>
                        <td>{{ $record->first_name }}</td>
                        <td>{{ $record->middle_name }}</td>
                        <td>{{ $record->sex }}</td>
                        <td>{{ $record->course }}</td>
                        <td>{{ $record->year }}</td>
                        <td>{{ $record->scholarship_name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $record->scholarship_type)) }}</td>
                        <td>{{ $record->funding_source }}</td>
                        <td>{{ $record->campus }}</td>
                        <td>{{ $record->amount ?? '' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="11" class="text-center text-gray">No applications found.</td>
                </tr>
            @endif
        </tbody>
        </table>
        @if(method_exists($oldRecords, 'links'))
            <div class="old-grants-pagination">
                {{ $oldRecords->links() }}
            </div>
        @endif
        @else
        <table>
            <thead>
                <tr>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Sex</th>
                    <th>Course</th>
                    <th>Type of Scholarship</th>
                    <th>Name of Scholarship</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @if($recentApplications->count() > 0)
                @foreach($recentApplications as $app)
                    <tr>
                        <td>{{ $app->last_name }}</td>
                        <td>{{ $app->first_name }}</td>
                        <td>{{ $app->middle_name }}</td>
                        <td>{{ $app->sex }}</td>
                        <td>{{ $app->course }}</td>
                        <td>{{ $app->scholarship_name }}</td>
                        <td>{{ $app->scholarship_type }}</td>
                        <td>{{ optional($app->created_at)->format('Y-m-d') }}</td>
                        <td>
                            @if(isset($app->application_status) && $app->application_status === 'approved')
                                <span class="badge approved">Approved</span>
                            @elseif(isset($app->application_status) && $app->application_status === 'rejected')
                                <span class="badge rejected">Rejected</span>
                            @else
                                <span class="badge pending">Pending</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn small view-profile-btn" data-id="{{ $app->id }}">Applicant info</button>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" class="text-center text-gray">No applications found.</td>
                </tr>
            @endif
        </tbody>
        </table>
        @if(method_exists($recentApplications, 'links'))
            {{ $recentApplications->links() }}
        @endif
        @endif
    </div>

            <!-- Profile Modal -->
       <div id="profileModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <div class="application-modal-header">
                <button type="button" class="academic-nav-btn" id="profilePreviousTop" aria-label="Back to previous step">← Back</button>
                <h1 class="form-title">Scholarship Application</h1>
                <button type="button" class="modal-close" id="closeProfile" aria-label="Close applicant profile">✕</button>
            </div>
            <div class="academic-progress-wrapper">
                <div class="academic-progress-header">Step <span id="profileStepCounter">1</span> of 3</div>
                <div class="academic-progress-track" aria-live="polite">
                    <div class="academic-progress-step active" data-profile-progress-step="1"><div class="academic-progress-dot">1</div><div class="academic-progress-label">Student Information</div></div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-profile-progress-step="2"><div class="academic-progress-dot">2</div><div class="academic-progress-label">Personal Information</div></div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-profile-progress-step="3"><div class="academic-progress-dot">3</div><div class="academic-progress-label">Scholarship &amp; Requirements</div></div>
                </div>
            </div>
            <div id="profileBody">Loading...</div>
            <div class="academic-form-actions profile-pagination">
                <button type="button" class="academic-nav-btn" id="profilePrevious">← Previous</button>
                <span id="profilePageCounter">1 / 3</span>
                <button type="button" class="academic-nav-btn" id="profileNext">Next →</button>
            </div>
        </div>
    </div>

<!-- confirmation modal -->
<div id="confirmActionModal" class="modal">
    <div class="modal-content">
        <h3>Are you sure?</h3>
        <p id="confirmActionText" class="margin-15">Do you want to proceed with this action?</p>
        <div class="flex-center-gap">
            <button id="confirmCancelBtn" class="btn">NO</button>
            <button id="confirmYesBtn" class="btn">YES</button>
        </div>
    </div>
</div>

<!-- School ID Document Modal -->
<div id="schoolIDModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal-content" style="max-width:600px;width:90%;background:white;border-radius:8px;padding:30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);position:relative;">
        <button id="closeSchoolIDModal" class="modal-close-btn" style="position:absolute;top:10px;right:10px;background:none;border:none;font-size:24px;cursor:pointer;">✕</button>
        <h2 style="margin-top:0;">School ID Document</h2>
        <div style="padding:20px 0;">
            <img id="schoolIDImage" src="" alt="School ID" style="max-width:100%;max-height:500px;border-radius:6px;" />
        </div>
    </div>
</div>

<!-- Approve User Modal -->
<div id="approveModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal-content" style="max-width:400px;width:90%;background:white;border-radius:8px;padding:30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <h2 style="margin-top:0;color:#27ae60;">Approve User</h2>
        <p style="font-size:16px;color:#666;margin:20px 0;">Are you sure you want to approve this user account?</p>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:30px;">
            <button id="approveCancel" class="btn" style="background:#ccc;color:#333;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-size:14px;flex:1;">Cancel</button>
            <button id="approveConfirm" class="btn" style="background:#27ae60;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-size:14px;flex:1;">Approve</button>
        </div>
    </div>
</div>

</div>

          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
          <script>
              // Sidebar Toggle
              const sidebarToggle = document.getElementById('sidebarToggle');
              const adminSidebar = document.getElementById('adminSidebar');
              const adminMain = document.getElementById('adminMain');

              sidebarToggle.addEventListener('click', function(e) {
                  e.preventDefault();
                  e.stopPropagation();
                  
                  adminSidebar.classList.toggle('collapsed');
                  adminMain.classList.toggle('sidebar-collapsed');
                  sidebarToggle.classList.toggle('active');
                  
                  // Save state to localStorage
                  const isCollapsed = adminSidebar.classList.contains('collapsed');
                  localStorage.setItem('adminSidebarCollapsed', isCollapsed);
              });

              // Restore sidebar state on page load
              window.addEventListener('load', function() {
                  const isCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';
                  if (isCollapsed) {
                      adminSidebar.classList.add('collapsed');
                      adminMain.classList.add('sidebar-collapsed');
                      sidebarToggle.classList.add('active');
                  }
              });

              function confirmDelete(id) {
                  Swal.fire({
                      title: 'Are you sure?',
                      text: 'Are you sure you want to DELETE this application?',
                      icon: 'error',
                      showCancelButton: true,
                      confirmButtonText: 'YES',
                      cancelButtonText: 'NO',
                      confirmButtonColor: '#dc3545',
                      cancelButtonColor: '#6c757d'
                  }).then((result) => {
                      if (result.isConfirmed) {
                          var f = document.getElementById('deleteForm-' + id);
                          if (f) f.submit();
                      }
                  });
              }

              function confirmApprove(id) {
                  Swal.fire({
                      title: 'Are you sure?',
                      text: 'Are you sure you want to APPROVE this application?',
                      icon: 'success',
                      showCancelButton: true,
                      confirmButtonText: 'YES',
                      cancelButtonText: 'NO',
                      confirmButtonColor: '#28a745',
                      cancelButtonColor: '#6c757d'
                  }).then((result) => {
                      if (result.isConfirmed) {
                          var f = document.getElementById('approveForm-' + id);
                          if (f) f.submit();
                      }
                  });
              }

              function confirmReject(id) {
                  Swal.fire({
                      title: 'Are you sure?',
                      text: 'Are you sure you want to REJECT this application?',
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonText: 'YES',
                      cancelButtonText: 'NO',
                      confirmButtonColor: '#f97316',
                      cancelButtonColor: '#6c757d'
                  }).then((result) => {
                      if (result.isConfirmed) {
                          var f = document.getElementById('rejectForm-' + id);
                          if (f) f.submit();
                      }
                  });
              }

              // Approve user with SweetAlert (used by users table)
              function confirmApproveUser(id) {
                  Swal.fire({
                      title: 'Are you sure?',
                      text: 'Are you sure you want to approve this user?',
                      icon: 'success',
                      showCancelButton: true,
                      confirmButtonText: 'Approve',
                      cancelButtonText: 'Cancel',
                      confirmButtonColor: '#28a745',
                      cancelButtonColor: '#6c757d'
                  }).then(result => {
                      if (!result.isConfirmed) return;
                      // submit form if exists
                      var f = document.getElementById('approveForm-' + id);
                      if (f) { f.submit(); return; }
                      // otherwise call existing performApprove if present
                      if (typeof performApprove === 'function') {
                          performApprove(id);
                      } else {
                          // fallback: POST and show toast
                          fetch(`/admin/users/${id}/approve`, { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }})
                          .then(r=>r.json()).then(data=>{
                              if (data.status === 'ok') {
                                  Swal.fire({ toast:true, position:'top-end', icon:'success', title:'User approved successfully', showConfirmButton:false, timer:2000, timerProgressBar:true });
                                  if (typeof loadUsers === 'function') loadUsers();
                              } else {
                                  Swal.fire({ icon:'error', title:'Error', text: data.message || 'Failed' });
                              }
                          }).catch(err=>{
                              Swal.fire({ icon:'error', title:'Error', text: err.message });
                          });
                      }
                  });
              }
          </script>
          <script src="{{ asset('js/admin.js') }}?v={{ time() }}"></script>
          <script>
              // Modals
              const schoolIDModal = document.getElementById('schoolIDModal');
              const approveModal = document.getElementById('approveModal');
              
              let pendingUserId = null;

              // School ID Modal
              const closeSchoolIDBtn = document.getElementById('closeSchoolIDModal');
              if (closeSchoolIDBtn) {
                  closeSchoolIDBtn.addEventListener('click', function() {
                      schoolIDModal.style.display = 'none';
                  });
              }

              if (schoolIDModal) {
                  schoolIDModal.addEventListener('click', function(event) {
                      if (event.target === schoolIDModal) {
                          schoolIDModal.style.display = 'none';
                      }
                  });
              }

              function viewSchoolID(imageUrl) {
                  document.getElementById('schoolIDImage').src = imageUrl;
                  schoolIDModal.style.display = 'flex';
              }

              // Approve Modal
              document.getElementById('approveCancel').addEventListener('click', function() {
                  approveModal.style.display = 'none';
                  pendingUserId = null;
              });

              document.getElementById('approveConfirm').addEventListener('click', function() {
                  if (pendingUserId) {
                      performApprove(pendingUserId);
                      approveModal.style.display = 'none';
                      pendingUserId = null;
                  }
              });

              approveModal.addEventListener('click', function(event) {
                  if (event.target === approveModal) {
                      approveModal.style.display = 'none';
                      pendingUserId = null;
                  }
              });

              function loadUsers() {
                  fetch('{{ route("admin.users") }}', {
                      headers: {
                          'X-Requested-With': 'XMLHttpRequest'
                      }
                  })
                  .then(response => response.text())
                  .then(html => {
                      document.getElementById('admin-table-container').innerHTML = html;
                  })
                  .catch(error => {
                      console.error('Error:', error);
                      alert('Error loading users');
                  });
              }

              function approveUser(userId) {
                  pendingUserId = userId;
                  approveModal.style.display = 'flex';
              }

              function performApprove(userId) {
                  fetch(`/admin/users/${userId}/approve`, {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                      }
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.status === 'ok') {
                          Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'User approved successfully', showConfirmButton: false, timer: 2000, timerProgressBar: true });
                          loadUsers();
                      } else {
                          Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed' });
                      }
                  })
                  .catch(error => {
                      console.error('Error:', error);
                      Swal.fire({ icon: 'error', title: 'Error', text: 'Error approving user' });
                  });
              }

              function rejectUser(userId) {
                  Swal.fire({
                      title: 'Reject Registration',
                      html: '<textarea id="rejectReasonInput" placeholder="Enter rejection reason (optional)" style="width:100%;height:80px;padding:10px;border:1px solid #ddd;border-radius:4px;font-family:Arial;font-size:13px;box-sizing:border-box;resize:vertical;"></textarea>',
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonText: 'Reject',
                      cancelButtonText: 'Cancel',
                      confirmButtonColor: '#e74c3c',
                      cancelButtonColor: '#6c757d',
                      preConfirm: () => {
                          const reason = document.getElementById('rejectReasonInput').value || 'Account rejected by administrator';
                          return reason;
                      }
                  }).then(result => {
                      if (result.isConfirmed) {
                          performReject(userId, result.value);
                      }
                  });
              }

              function performReject(userId, reason) {
                  fetch(`/admin/users/${userId}/reject`, {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                      },
                      body: JSON.stringify({
                          reason: reason
                      })
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.status === 'ok') {
                          Swal.fire({
                              toast: true,
                              position: 'top-end',
                              icon: 'success',
                              title: 'User rejected successfully',
                              showConfirmButton: false,
                              timer: 2000,
                              timerProgressBar: true
                          });
                          loadUsers();
                      } else {
                          Swal.fire({
                              icon: 'error',
                              title: 'Error',
                              text: data.message || 'Failed to reject user'
                          });
                      }
                  })
                  .catch(error => {
                      console.error('Error:', error);
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: 'Error rejecting user'
                      });
                  });
              }

              function confirmDeleteUser(userId) {
                  Swal.fire({
                      title: 'Delete Registration?',
                      text: 'Are you sure you want to delete this registration?',
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonText: 'Delete',
                      cancelButtonText: 'Cancel',
                      confirmButtonColor: '#dc3545',
                      cancelButtonColor: '#6c757d'
                  }).then(result => {
                      if (result.isConfirmed) {
                          fetch(`/admin/users/${userId}/delete`, {
                              method: 'POST',
                              headers: {
                                  'Content-Type': 'application/json',
                                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                              }
                          })
                          .then(response => response.json())
                          .then(data => {
                              if (data.status === 'ok') {
                                  Swal.fire({
                                      toast: true,
                                      position: 'top-end',
                                      icon: 'success',
                                      title: 'Registration deleted successfully',
                                      showConfirmButton: false,
                                      timer: 2000,
                                      timerProgressBar: true
                                  });
                                  loadUsers();
                              } else {
                                  Swal.fire({
                                      icon: 'error',
                                      title: 'Error',
                                      text: data.message || 'Failed to delete registration'
                                  });
                              }
                          })
                          .catch(error => {
                              console.error('Error:', error);
                              Swal.fire({
                                  icon: 'error',
                                  title: 'Error',
                                  text: 'Error deleting registration'
                              });
                          });
                      }
                  });
              }
          </script>

          <!-- Include Old Grant Upload Modal -->
          @include('admin.partials.old-grant-upload-modal')
    </body>
</html>
