<div class="sidebar" id="adminSidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
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
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon"></span> Dashboard
        </a>

        <div class="nav-label">Grants</div>
        <a href="{{ route('admin.academic') }}" class="nav-item {{ request()->routeIs('admin.academic') ? 'active' : '' }}">
            <span class="nav-icon"></span> Academic Grants
            
        </a>
        <a href="{{ route('admin.non_academic') }}" class="nav-item {{ request()->routeIs('admin.non_academic') ? 'active' : '' }}">
            <span class="nav-icon"></span> Non-Academic Grants
            
        </a>
       

        <div style="margin-top: 12px; padding-left: 12px; font-size: 13px; color: #666; font-weight: 500;">Old Grants</div>
        <a href="{{ route('admin.old_academic') }}" class="nav-item {{ request()->routeIs('admin.old_academic') ? 'active' : '' }}">
            <span class="nav-icon"></span> Old Academic Grants
        </a>
        <a href="{{ route('admin.old_non_academic') }}" class="nav-item {{ request()->routeIs('admin.old_non_academic') ? 'active' : '' }}">
            <span class="nav-icon"></span> Old Non-Academic Grants
        </a>

        <div class="nav-label">Administration</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-item">
            <span class="nav-icon"></span> Summary of Reports
            
        </a>
          <a href="{{ route('admin.scholarships.create') }}" class="nav-item {{ request()->routeIs('admin.scholarships.create') ? 'active' : '' }}">
            <span class="nav-icon"></span> Add Scholarship
        </a>
        <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <span class="nav-icon"></span> Users
        </a>
        <a href="/admin/settings" class="nav-item">
            <span class="nav-icon"></span>Settings
        </a>

        <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button id="adminLogoutBtn" type="submit" class="nav-item" data-logout-url="{{ route('logout') }}" style="background:none;border:none;padding:12px 16px;text-align:left;width:100%;cursor:pointer;">
                <span class="nav-icon"></span>Logout
            </button>
        </form>
    </nav>

 
    </div>
</div>
