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
    </style>
    </head>
<body>
    <div class="sidebar">
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
    <div class="main">
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
            <div class="stat-val">0</div>
            <div class="stat-label">Total Grants</div>
            <div class="stat-change up">↑ 0 added this month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">0</div>
            <div class="stat-label">Total Applications</div>
            <div class="stat-change up">↑ 0 this week</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">0</div>
            <div class="stat-label">Approved</div>
            <div class="stat-change up">0% approval rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"> </div>
            <div class="stat-val">3</div>
            <div class="stat-label">Registered Users</div>
            <div class="stat-change up">↑ 3 new this month</div>
        </div>
    </div>

<div class="grid-2"> 
<div class="card">
    <div class="card-title">
        @if($filterType === 'academic')
            Academic Grants
        @elseif($filterType === 'non-academic')
            Non-Academic Grants
        @elseif($filterType === 'users')
            Registered Users
        @else
            Recent Applications
        @endif
        <a href="http://127.0.0.1:8000/admin/applications" class="card-action">View All →</a>
    </div>
    <div id="admin-table-container">
        @if($filterType === 'users')
            @include('admin.partials.users-table', ['users' => $users])
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
            @if(!empty($recentApplications) && count($recentApplications) > 0)
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
                    <td colspan="10" class="text-center text-gray">No applications yet.</td>
                </tr>
            @endif
        </tbody>
        </table>
        @endif
    </div>

            <!-- Profile Modal -->
       <div id="profileModal" class="modal">
        <div class="modal-content">
        <button id="closeProfile" class="modal-close-btn">✕</button>
        <h2>Applicant Profile</h2>
        <div id="profileBody">Loading…</div>
        <div class="modal-actions">
            <button id="editProfile" class="btn">Edit</button>
            <button id="approveProfile" class="btn">✓ Approve</button>
            <button id="rejectProfile" class="btn">✕ Reject</button>
            <button id="deleteProfile" class="btn">Delete</button>
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

<!-- Reject User Modal -->
<div id="rejectModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal-content" style="max-width:400px;width:90%;background:white;border-radius:8px;padding:30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <h2 style="margin-top:0;color:#e74c3c;">Reject User</h2>
        <p style="font-size:14px;color:#666;margin:15px 0;">Enter rejection reason:</p>
        <textarea id="rejectReason" placeholder="Enter rejection reason (optional)" style="width:100%;height:80px;padding:10px;border:1px solid #ddd;border-radius:4px;font-family:Arial;font-size:13px;box-sizing:border-box;resize:vertical;"></textarea>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
            <button id="rejectCancel" class="btn" style="background:#ccc;color:#333;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-size:14px;flex:1;">Cancel</button>
            <button id="rejectConfirm" class="btn" style="background:#e74c3c;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-size:14px;flex:1;">Reject</button>
        </div>
    </div>
</div>

          <script src="{{ asset('js/admin.js') }}?v={{ time() }}"></script>
          <script>
              // Modals
              const schoolIDModal = document.getElementById('schoolIDModal');
              const approveModal = document.getElementById('approveModal');
              const rejectModal = document.getElementById('rejectModal');
              
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

              // Reject Modal
              document.getElementById('rejectCancel').addEventListener('click', function() {
                  rejectModal.style.display = 'none';
                  document.getElementById('rejectReason').value = '';
                  pendingUserId = null;
              });

              document.getElementById('rejectConfirm').addEventListener('click', function() {
                  if (pendingUserId) {
                      const reason = document.getElementById('rejectReason').value || 'Account rejected by administrator';
                      performReject(pendingUserId, reason);
                      rejectModal.style.display = 'none';
                      document.getElementById('rejectReason').value = '';
                      pendingUserId = null;
                  }
              });

              rejectModal.addEventListener('click', function(event) {
                  if (event.target === rejectModal) {
                      rejectModal.style.display = 'none';
                      document.getElementById('rejectReason').value = '';
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
                          alert('User approved!');
                          loadUsers();
                      } else {
                          alert('Error: ' + (data.message || 'Failed'));
                      }
                  })
                  .catch(error => {
                      console.error('Error:', error);
                      alert('Error approving user');
                  });
              }

              function rejectUser(userId) {
                  pendingUserId = userId;
                  document.getElementById('rejectReason').value = '';
                  rejectModal.style.display = 'flex';
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
                          alert('User rejected!');
                          loadUsers();
                      } else {
                          alert('Error: ' + (data.message || 'Failed'));
                      }
                  })
                  .catch(error => {
                      console.error('Error:', error);
                      alert('Error rejecting user');
                  });
              }
          </script>
    </body>
</html>
