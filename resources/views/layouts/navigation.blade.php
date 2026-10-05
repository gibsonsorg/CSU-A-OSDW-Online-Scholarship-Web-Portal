

@php
    $currentStudent = auth()->user();
    $studentEmail = $currentStudent?->email ?? 'No email on file';
    $studentNotifications = $currentStudent ? $currentStudent->studentNotifications()->orderByDesc('created_at')->get() : collect();
    $unreadNotificationCount = $studentNotifications->whereNull('read_at')->count();
@endphp

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

        <div class="student-sidebar-footer">
            <div class="student-sidebar-divider"></div>

            <div class="student-sidebar-user-card">
                <div class="student-sidebar-user-meta">
                    <span class="student-sidebar-avatar" aria-hidden="true">{{ strtoupper(substr(($currentStudent->name ?? $studentEmail), 0, 1)) }}</span>
                    <span class="student-sidebar-email" title="{{ $studentEmail }}">{{ Str::limit($studentEmail, 20, '...') }}</span>
                </div>

                <button type="button" class="student-sidebar-bell" aria-label="View notifications" data-student-bell>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 3a5 5 0 0 1 5 5v3.4l1.7 4.1a1 1 0 0 1-.95 1.5H5.25a1 1 0 0 1-.95-1.5L6 11.4V8a5 5 0 0 1 5-5Zm0 18a2.5 2.5 0 0 1-2.45-2h4.9A2.5 2.5 0 0 1 12 21Z"/>
                    </svg>
                    <span class="student-sidebar-badge" data-notification-badge {{ $unreadNotificationCount > 0 ? '' : 'hidden' }}>{{ $unreadNotificationCount }}</span>
                </button>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="student-sidebar-logout-form">
                @csrf
                <button type="submit" class="student-sidebar-link student-sidebar-logout">
                    <img src="{{ asset('images/arrow.svg') }}" alt="Logout" class="student-sidebar-logout-icon" />
                    {{ __('Logout') }}
                </button>
            </form>
        </div>
    </nav>
</nav>

<div id="studentNotificationDialog" class="student-notification-dialog" aria-hidden="true" hidden>
    <div class="student-notification-backdrop" data-close-notifications></div>
    <div class="student-notification-modal" role="dialog" aria-modal="true" aria-labelledby="studentNotificationTitle">
        <div class="student-notification-header">
            <div class="student-notification-header-row">
                <h3 id="studentNotificationTitle">Notifications</h3>
                <span id="studentNotificationCount" class="student-notification-count">0</span>
            </div>
            <button type="button" class="student-notification-close" data-close-notifications aria-label="Close notifications">×</button>
        </div>

        <div id="studentNotificationList" class="student-notification-list"></div>
        <div id="studentNotificationEmpty" class="student-notification-empty" hidden>
            No notifications yet. Your scholarship application updates will appear here.
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="studentSidebarToggle" type="button" aria-label="Toggle sidebar">
    <span class="hamburger-icon"></span>
    <span class="hamburger-icon"></span>
    <span class="hamburger-icon"></span>
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
        gap: 9px;
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

    .student-sidebar-footer {
        margin-top: auto;
        padding: 0 10px 12px;
    }

    .student-sidebar-divider {
        height: 1px;
        background: rgba(246, 216, 138, 0.28);
        margin: 0 0 12px;
    }

    .student-sidebar-user-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: rgba(0, 0, 0, 0.12);
        border-radius: 12px;
        padding: 8px 10px;
        margin-top: 2px;
    }

    .student-sidebar-user-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }

    .student-sidebar-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .student-sidebar-email {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.74rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    .student-sidebar-bell {
        position: relative;
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
    }

    .student-sidebar-bell svg {
        width: 15px;
        height: 15px;
        fill: #f5f5f5;
    }

    .student-sidebar-badge {
        position: absolute;
        top: -4px;
        right: -3px;
        min-width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #d82f2f;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.62rem;
        font-weight: 700;
        line-height: 1;
        padding: 0 4px;
        box-shadow: 0 0 0 2px rgba(74, 10, 18, 0.75);
    }

    .student-notification-dialog {
        position: fixed;
        inset: 0;
        z-index: 900;
        display: block;
    }

    .student-notification-dialog[hidden] {
        display: none;
    }

    .student-notification-backdrop {
        position: fixed;
        inset: 0;
        background: transparent;
    }

    .student-notification-modal {
        position: fixed;
        left: 264px;
        bottom: 100px;
        z-index: 1;
        display: flex;
        flex-direction: column;
        width: min(350px, calc(100vw - 280px));
        max-height: min(400px, calc(100vh - 120px));
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        border: 1px solid rgba(17, 24, 39, 0.08);
    }

    .student-notification-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px 10px;
        border-bottom: 1px solid rgba(17, 24, 39, 0.08);
        background: #fff;
    }

    .student-notification-header-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .student-notification-header h3 {
        margin: 0;
        color: #2c1b1d;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .student-notification-count {
        min-width: 24px;
        height: 24px;
        border-radius: 999px;
        background: #d82f2f;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0 7px;
    }

    .student-notification-close {
        border: none;
        background: rgba(120, 26, 25, 0.08);
        color: #3f2f2f;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 1.4rem;
        line-height: 1;
        cursor: pointer;
    }

    .student-notification-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 8px;
        background: #f9f6f1;
    }

    .student-notification-item {
        display: flex;
        gap: 12px;
        width: 100%;
        border: 1px solid rgba(17, 24, 39, 0.04);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 16px rgba(17, 24, 39, 0.04);
        padding: 10px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .student-notification-item:hover {
        box-shadow: 0 8px 18px rgba(17, 24, 39, 0.08);
        border-color: rgba(120, 26, 25, 0.14);
    }

    .student-notification-item.is-read {
        opacity: 0.82;
    }

    .student-notification-status {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .student-notification-status.approved {
        background: rgba(22, 163, 74, 0.12);
        color: #15803d;
    }

    .student-notification-status.rejected {
        background: rgba(220, 38, 38, 0.12);
        color: #b91c1c;
    }

    .student-notification-body {
        flex: 1;
        min-width: 0;
    }

    .student-notification-topline {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 4px;
    }

    .student-notification-title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: #1f2937;
    }

    .student-notification-decision {
        font-size: 0.63rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border-radius: 999px;
        padding: 4px 7px;
        font-weight: 800;
        white-space: nowrap;
    }

    .student-notification-decision.approved {
        background: rgba(22, 163, 74, 0.12);
        color: #15803d;
    }

    .student-notification-decision.rejected {
        background: rgba(220, 38, 38, 0.1);
        color: #b91c1c;
    }

    .student-notification-scholarship {
        margin: 0 0 5px;
        color: #4b5563;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .student-notification-message {
        margin: 0;
        color: #374151;
        line-height: 1.35;
        font-size: 0.77rem;
    }

    .student-notification-reason {
        margin-top: 8px;
        background: #fef2f2;
        border: 1px solid rgba(220, 38, 38, 0.12);
        border-radius: 10px;
        color: #7f1d1d;
        padding: 8px 10px;
        font-size: 0.72rem;
        line-height: 1.4;
    }

    .student-notification-meta {
        margin-top: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        color: #6b7280;
        font-size: 0.68rem;
    }

    .student-notification-empty {
        padding: 22px 18px 28px;
        color: #4b5563;
        text-align: center;
        line-height: 1.6;
        font-size: 0.85rem;
        background: #fff;
    }

    .student-sidebar-logout-icon {
        width: 15px;
        height: 15px;
        display: inline-block;
        filter: brightness(0) invert(1);
        opacity: 0.9;
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
        color: #fff;
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
        color: #000;
    }

    .sidebar-toggle.active .hamburger-icon {
        background-color: #000;
    }

    .sidebar-toggle .hamburger-icon {
        display: block;
    }

    @media (max-width: 768px) {
        .student-notification-modal {
            left: 12px;
            right: 12px;
            bottom: 88px;
            width: auto;
            max-height: min(400px, calc(100vh - 104px));
        }

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
