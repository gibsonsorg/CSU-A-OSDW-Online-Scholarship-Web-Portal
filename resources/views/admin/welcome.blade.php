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
            <img src="{{ asset('images/csu.logo.png') }}" alt="CSU Logo">
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


            <a href="/admin"
               class="nav-item ">
                <span class="nav-icon"></span> Users
            </a>

            <a href="/admin"
               class="nav-item ">
                <span class="nav-icon"></span> Analytics
            </a>

        
       

       
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
        @else
            Recent Applications
        @endif
        <a href="http://127.0.0.1:8000/admin/applications" class="card-action">View All →</a>
    </div>
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
                        <button class="btn small view-profile-btn" data-id="{{ $app->id }}">View Profile</button>
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

          <script src="{{ asset('js/admin.js') }}?v={{ time() }}"></script>
    </body>
</html>
