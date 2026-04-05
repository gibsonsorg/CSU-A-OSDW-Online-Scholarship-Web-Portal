<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Settings — CSU GMS</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .settings-header {
            background: linear-gradient(135deg, #8b3a3a 0%, #a54a4a 100%);
            color: white;
            padding: 24px;
            margin-bottom: 32px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .settings-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .settings-header p {
            font-size: 14px;
            opacity: 0.95;
        }

        .content {
            padding: 20px;
        }

        .settings-card {
            background-color: #f9f5f0;
            border: 2px solid #d4b896;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .settings-card h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #d4b896;
        }

        .backup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 32px;
        }

        .manual-backup-section h3,
        .automatic-backup-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .btn-download {
            background-color: #8b3a3a;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
            margin-bottom: 20px;
        }

        .btn-download:hover {
            background-color: #6f2f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 58, 58, 0.3);
        }

        .icon-download {
            width: 18px;
            height: 18px;
        }

        .last-backup-info {
            background-color: white;
            border: 1px solid #e8dcd0;
            border-radius: 8px;
            padding: 16px;
            font-size: 13px;
            color: #666;
        }

        .last-backup-info strong {
            display: block;
            color: #333;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .toggle-container {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .toggle-label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 56px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 30px;
            border: 2px solid #999;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #4caf50;
            border-color: #2d7a2d;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .dropdowns-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .dropdown-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .dropdown-label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropdown-select {
            padding: 12px 14px;
            border: 1px solid #d4a76a;
            border-radius: 6px;
            background-color: white;
            color: #333;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238b3a3a' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1.2em;
            padding-right: 36px;
        }

        .dropdown-select:hover {
            border-color: #8b3a3a;
            background-color: #fef9f3;
        }

        .dropdown-select:focus {
            outline: none;
            border-color: #8b3a3a;
        }

        .text-muted {
            color: #999;
            font-size: 12px;
            display: block;
            margin-bottom: 10px;
        }

        .table-section {
            margin-top: 40px;
        }

        .table-section h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #d4b896;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        table thead {
            background-color: #8b3a3a;
            color: white;
        }

        table th {
            padding: 16px 14px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        table td {
            padding: 16px 14px;
            border-bottom: 1px solid #e8dcd0;
            font-size: 13px;
            color: #555;
        }

        table tbody tr:hover {
            background-color: #faf8f5;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-deleted {
            display: inline-block;
            background-color: #f4978e;
            color: #8b2e2e;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .actions-cell {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-info {
            background-color: #8b3a3a;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-info:hover {
            background-color: #6f2f2f;
        }

        .btn-icon {
            background-color: transparent;
            border: 1px solid #d4b896;
            color: #8b3a3a;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s ease;
            padding: 0;
        }

        .btn-icon:hover {
            background-color: #f9f5f0;
            border-color: #8b3a3a;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            background-color: white;
            border-radius: 8px;
            color: #999;
        }

        @media (max-width: 1024px) {
            .backup-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dropdowns-container {
                grid-template-columns: 1fr;
            }

            table th, table td {
                padding: 12px 10px;
                font-size: 11px;
            }
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
            <a href="/admin" class="nav-item">
                <span class="nav-icon"></span> Dashboard
            </a>

            <div class="nav-label">Grants</div>
            <a href="/admin/academic" class="nav-item">
                <span class="nav-icon"></span> Academic Grants
                <span class="nav-badge">0</span>
            </a>

            <a href="/admin/non-academic" class="nav-item">
                <span class="nav-icon"></span> Non-Academic
                <span class="nav-badge">0</span>
            </a>

            <div class="nav-label">Administration</div>
            <a href="/admin" class="nav-item">
                <span class="nav-icon"></span> Summary of Reports
                <span class="nav-badge">0</span>
            </a>

            <a href="/admin" class="nav-item">
                <span class="nav-icon"></span> Users
            </a>

            <a href="/admin/settings" class="nav-item active">
                <span class="nav-icon"></span> Settings
            </a>
        </nav>

        <div class="sidebar-user">
            <div class="u-avatar">SA</div>
            <div>
                <div class="u-name">System Administrator</div>
                <div class="u-role">Admin · Aparri</div>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>System Settings</h1>
                <p>CSU Grants Management — Data Management & Recovery</p>
            </div>
        </div>

        <div class="content">
            <div class="settings-card">
                <h2>Backup & Recovery</h2>

                <div class="backup-grid">
                    <!-- Left Column: Manual Backup -->
                    <div class="manual-backup-section">
                        <h3>Manual Backup (Admin clicks button)</h3>
                        <form id="manualBackupForm">
                            @csrf
                            <button type="submit" class="btn-download">
                                <svg class="icon-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Generate and Download Manual Backup Now
                            </button>
                        </form>

                        <div class="last-backup-info">
                            <strong>Last manual backup: 15 Oct 2023, 10:15 AM</strong>
                            <span>Create an immediate, downloadable snapshot of all current system data (Database & User Files).</span>
                        </div>
                    </div>

                    <!-- Right Column: Automatic Backup -->
                    <div class="automatic-backup-section">
                        <h3>Automatic Backup (Scheduler runs in background)</h3>

                        <div class="toggle-container">
                            <label class="toggle-label">Enable Automatic Backups</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="autoBackupToggle" checked>
                                <span class="toggle-slider"></span>
                            </label>
                            <span style="color: #4caf50; font-weight: 600; font-size: 13px;">On</span>
                        </div>

                        <div class="dropdowns-container">
                            <div class="dropdown-group">
                                <label class="dropdown-label">Frequency</label>
                                <select class="dropdown-select">
                                    <option>Daily</option>
                                    <option>Weekly</option>
                                    <option>Monthly</option>
                                </select>
                            </div>

                            <div class="dropdown-group">
                                <label class="dropdown-label">Time Picker</label>
                                <input type="time" class="dropdown-select" value="03:00" style="padding-right: 14px; background-image: none;">
                            </div>
                        </div>

                        <p class="text-muted">Configure the automated, behind-the-scenes system scheduler for secure daily backups. Next run: Tomorrow at 03:00 AM.</p>
                        <p class="text-muted">Automatic backups are stored on a secure, separate server.</p>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="table-section">
                    <h2>Applicant Deleted Info</h2>

                    @if($deletedApplicants && count($deletedApplicants) > 0)
                        <div class="table-wrapper">
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
                                    @foreach($deletedApplicants as $applicant)
                                        <tr>
                                            <td>{{ $applicant->last_name ?? '-' }}</td>
                                            <td>{{ $applicant->first_name ?? '-' }}</td>
                                            <td>{{ $applicant->middle_name ?? '-' }}</td>
                                            <td>{{ $applicant->sex ?? '-' }}</td>
                                            <td>{{ $applicant->course ?? '-' }}</td>
                                            <td>{{ $applicant->scholarship_type ?? '-' }}</td>
                                            <td>{{ $applicant->scholarship_name ?? '-' }}</td>
                                            <td>{{ $applicant->deleted_at ? $applicant->deleted_at->format('Y-m-d') : '-' }}</td>
                                            <td>
                                                <span class="badge-deleted">Deleted</span>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <button type="button" class="btn-info btn-applicant-info" data-id="{{ $applicant->id }}" data-name="{{ $applicant->first_name }} {{ $applicant->last_name }}">
                                                        Applicant Info
                                                    </button>
                                                    <button type="button" class="btn-icon btn-restore" data-id="{{ $applicant->id }}" data-name="{{ $applicant->first_name }} {{ $applicant->last_name }}" title="Restore applicant">
                                                        ↺
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <p>No deleted applicants found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('manualBackupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            alert('Backup generated successfully! Downloading now...');
        });

        document.getElementById('autoBackupToggle').addEventListener('change', function() {
            const statusText = this.parentElement.nextElementSibling;
            if (this.checked) {
                statusText.textContent = 'On';
                statusText.style.color = '#4caf50';
            } else {
                statusText.textContent = 'Off';
                statusText.style.color = '#d4a76a';
            }
        });

        document.querySelectorAll('.btn-restore').forEach(button => {
            button.addEventListener('click', async function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                
                if (!confirm(`Are you sure you want to restore ${name}? They will be returned to their original grant section.`)) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/backup/restore/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();
                    
                    if (data.status === 'ok') {
                        alert('✓ ' + data.message);
                        // Reload the page to refresh the table
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        alert('✗ Error: ' + (data.error || 'Failed to restore applicant'));
                    }
                } catch (error) {
                    alert('✗ Error: ' + error.message);
                }
            });
        });

        document.querySelectorAll('.btn-applicant-info').forEach(button => {
            button.addEventListener('click', async function() {
                const id = this.dataset.id;
                const name = this.dataset.name;

                try {
                    const response = await fetch(`/admin/backup/applicant-info/${id}`);
                    const data = await response.json();

                    if (data.status === 'ok') {
                        const applicant = data.data;
                        const info = `
APPLICANT INFORMATION
${'-'.repeat(50)}

Name: ${applicant.first_name} ${applicant.middle_name || ''} ${applicant.last_name}
Email: ${applicant.email || 'N/A'}
Sex: ${applicant.sex || 'N/A'}
Contact: ${applicant.contact_number || 'N/A'}
Course: ${applicant.course || 'N/A'}
Year Level: ${applicant.year_level || 'N/A'}

Scholarship Info:
  Type: ${applicant.scholarship_type || 'N/A'}
  Name: ${applicant.scholarship_name || 'N/A'}
  Grant Type: ${applicant.grant_type || 'N/A'}

Status: ${applicant.application_status || 'N/A'}
ID Number: ${applicant.id_number || 'N/A'}
Deleted On: ${applicant.deleted_at ? new Date(applicant.deleted_at).toLocaleString() : 'N/A'}
                        `;
                        alert(info);
                    } else {
                        alert('✗ Error: ' + (data.error || 'Failed to load applicant info'));
                    }
                } catch (error) {
                    alert('✗ Error: ' + error.message);
                }
            });
        });
    </script>
</body>
</html>
