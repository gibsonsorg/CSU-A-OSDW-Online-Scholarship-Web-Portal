

<nav id="studentSidebar" class="student-sidebar-nav">
    <a href="{{ route('dashboard', ['section' => 'acad']) }}" class="student-sidebar-logo">
        <div class="logo-img">
            <img src="{{ asset('images/csua-nobg.png') }}" alt="CSU Logo">
        </div>
        <div class="logo-text">
            <h2>CSU Grants</h2>
            <p>Management System</p>
        </div>
    </a>

    <nav class="student-sidebar-menu" aria-label="Student dashboard navigation">
        <a href="{{ route('dashboard', ['section' => 'acad']) }}" class="student-sidebar-link {{ request()->routeIs('dashboard') && request()->query('section') === 'acad' ? 'active' : '' }}">
            {{ __('Academic Grants') }}
        </a>
        <a href="{{ route('dashboard', ['section' => 'non']) }}" class="student-sidebar-link {{ request()->routeIs('dashboard') && request()->query('section') === 'non' ? 'active' : '' }}">
            {{ __('Non-Academic Grants') }}
        </a>
        <a href="{{ route('application-status') }}" class="student-sidebar-link {{ request()->routeIs('application-status') ? 'active' : '' }}">
            {{ __('Application Status') }}
        </a>

        <form method="POST" action="{{ route('logout') }}" class="student-sidebar-logout-form">
            @csrf
            <button type="submit" class="student-sidebar-link student-sidebar-logout">
                {{ __('Logout') }}
            </button>
        </form>
    </nav>
</nav>

<button class="sidebar-toggle" id="studentSidebarToggle" type="button" aria-label="Toggle sidebar">
    <span class="hamburger-icon"></span>
    <span class="hamburger-icon"></span>
    <span class="hamburger-icon"></span>
    <img class="arrow-icon" src="{{ asset('images/arrow.svg') }}" alt="Arrow icon">
</button>

<style>
    .student-app-shell {
        display: flex;
        min-height: 100vh;
        width: 100%;
        background: var(--cream, #f5f4f0);
    }

    .student-main-content {
        margin-left: 250px;
        width: calc(100% - 250px);
        flex: 1;
        min-width: 0;
        transition: all 0.3s ease;
    }

    .student-app-shell.sidebar-collapsed .student-main-content {
        margin-left: 0;
        width: 100%;
    }

    .student-sidebar-nav {
        width: 250px;
        background: var(--maroon-dark, #4a0a12);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 200;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 1px rgba(201, 168, 76, 0.08);
    }

    .student-sidebar-nav.collapsed {
        width: 0;
        overflow: hidden;
        border-right: none;
    }

    .student-sidebar-nav.collapsed .student-sidebar-logo,
    .student-sidebar-nav.collapsed .student-sidebar-menu {
        opacity: 0;
        visibility: hidden;
    }

    .student-sidebar-logo {
        padding: 24px 20px 18px;
        border-bottom: 1px solid rgba(201, 168, 76, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        position: relative;
        padding-right: 56px;
    }

    .logo-img {
        width: 54px;
        height: 54px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .logo-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 15%;
    }

    .logo-text h2 {
        font-family: 'Cormorant Garamond', serif;
        color: var(--gold-light, #f5d9a7);
        font-size: 0.95rem;
        line-height: 1.2;
    }

    .logo-text p {
        color: rgba(255, 255, 255, 0.35);
        font-size: 0.62rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-top: 2px;
    }

    .student-sidebar-menu {
        flex: 1;
        padding: 16px 10px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .student-sidebar-link,
    .student-sidebar-logout {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        width: 100%;
        padding: 10px 12px;
        border-radius: 9px;
        color: rgba(255, 255, 255, 0.55);
        font-size: 0.83rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.18s ease;
        text-decoration: none;
        border: none;
        text-align: left;
        background: transparent;
    }

    .student-sidebar-link:hover,
    .student-sidebar-logout:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
    }

    .student-sidebar-link.active {
        background: linear-gradient(135deg, var(--maroon-light, #8b1a28), var(--maroon, #6b0f1a));
        color: var(--gold-light, #f5d9a7);
    }

    .student-sidebar-logout-form {
        margin-top: auto;
        padding: 0 10px 16px;
    }

    .student-sidebar-logout {
        justify-content: flex-start;
    }

    .sidebar-toggle {
        position: fixed;
        top: 18px;
        left: calc(250px - 70px);
        border: none;
        width: 42px;
        height: 42px;
        background-color: transparent;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 4px;
        padding: 0;
        color: var(--maroon-dark, #4a0a12);
        transition: all 0.3s ease;
        z-index: 400;
    }

    .sidebar-toggle .hamburger-icon {
        display: block;
        width: 18px;
        height: 2px;
        background: currentColor;
        border-radius: 2px;
        transition: all 0.3s ease;
        background-color: #fff;
    }

    .sidebar-toggle.active {
        left: 18px;
    }

    .sidebar-toggle .arrow-icon {
        display: none;
        width: 20px;
        height: 20px;
        transition: all 0.3s ease;
    }

    .sidebar-toggle.active .hamburger-icon {
        display: none;
    }

    .sidebar-toggle.active .arrow-icon {
        display: block;
    }

    @media (max-width: 768px) {
        .student-app-shell {
            display: block;
        }

        .student-sidebar-nav {
            width: 250px;
            position: fixed;
            transform: translateX(0);
        }

        .student-sidebar-nav.collapsed {
            width: 0;
        }

        .student-main-content {
            margin-left: 0;
            width: 100%;
        }

        .student-app-shell.sidebar-collapsed .student-main-content {
            margin-left: 0;
            width: 100%;
        }
    }
</style>
