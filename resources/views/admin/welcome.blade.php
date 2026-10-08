<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard — CSU GMS</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .topbar-right .btn { margin-right: 4px; }
        .old-grants-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            width: 100%;
        }
        .old-grants-toolbar > .grant-filters {
            flex: 1 1 0;
            min-width: 0;
            width: auto;
        }
        .old-grants-toolbar > .btn {
            flex: 0 0 auto;
            margin: 0 0 10px;
            white-space: nowrap;
        }
        @media (max-width: 900px) {
            .old-grants-toolbar {
                flex-wrap: wrap;
            }
            .old-grants-toolbar > .btn {
                margin: 14px 0 0;
            }
            .old-grants-toolbar > .grant-filters {
                flex-basis: 100%;
            }
        }
    </style>
</head>
<body>
    @include('admin.partials.sidebar')
    <div class="main" id="adminMain">
        @include('admin.partials.sidebar-toggle')

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
            <div class="stat-change up">Approved scholarship grants</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">{{ $stats['total_applications'] ?? 0 }}</div>
            <div class="stat-label">Total Applications</div>
            <div class="stat-change up">All submitted applications</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-val">{{ $stats['pending_applications'] ?? 0 }}</div>
            <div class="stat-label">Pending Applications</div>
            <div class="stat-change up">Awaiting review</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"> </div>
            <div class="stat-val">{{ $stats['registered_users'] ?? 0 }}</div>
            <div class="stat-label">Registered Users</div>
            <div class="stat-change up">↑ {{ $stats['new_registered_this_month'] ?? 0 }} new this month</div>
        </div>
    </div>

    @if($filterType === 'dashboard')
        <section class="needs-attention-panel" aria-labelledby="needs-attention-title">
            <div class="needs-attention-header">
                <div>
                    <span class="needs-attention-icon" aria-hidden="true"></span>
                    <h2 id="needs-attention-title">Needs Attention</h2>
                </div>
            </div>

            <div class="needs-attention-grid">
                <section class="needs-attention-section" aria-labelledby="pending-applications-title">
                    <h3 id="pending-applications-title">Pending Applications (Aging)</h3>
                    @forelse($pendingApplications as $application)
                        <div class="needs-attention-item">
                            <div class="needs-attention-text">
                                <span class="needs-attention-name">{{ $application['scholarship_name'] ?: 'Scholarship' }}</span>
                                <span class="needs-attention-subtitle">{{ $application['full_name'] ?: 'Applicant' }}</span>
                            </div>
                            <div class="needs-attention-actions">
                                <span class="needs-attention-badge needs-attention-badge-{{ $application['urgency'] }}">{{ $application['days_pending'] }} day{{ $application['days_pending'] === 1 ? '' : 's' }}</span>
                                <button type="button" class="needs-attention-link needs-attention-review" data-id="{{ $application['id'] }}">Review <span aria-hidden="true">→</span></button>
                            </div>
                        </div>
                    @empty
                        <p class="needs-attention-empty">Nothing needs attention right now.</p>
                    @endforelse
                </section>

                <section class="needs-attention-section" aria-labelledby="closing-soon-title">
                    <h3 id="closing-soon-title">Scholarships Closing Soon</h3>
                    @forelse($scholarshipsClosingSoon as $scholarship)
                        <div class="needs-attention-item">
                            <div class="needs-attention-text">
                                <span class="needs-attention-name">{{ $scholarship['name'] }}</span>
                            </div>
                            <div class="needs-attention-actions">
                                <span class="needs-attention-badge needs-attention-badge-{{ $scholarship['urgency'] }}">{{ $scholarship['days_remaining'] }} day{{ $scholarship['days_remaining'] === 1 ? '' : 's' }}</span>
                                <a class="needs-attention-link" href="{{ route('admin.scholarships.availability', ['name' => $scholarship['name']]) }}">Manage <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    @empty
                        <p class="needs-attention-empty">Nothing needs attention right now.</p>
                    @endforelse
                </section>
            </div>

            <section class="needs-attention-section needs-attention-section-full" aria-labelledby="near-capacity-title">
                <h3 id="near-capacity-title">Scholarships Near Capacity</h3>
                @forelse($nearCapacityScholarships as $scholarship)
                    <div class="needs-attention-capacity-item">
                        <div class="needs-attention-capacity-row">
                            <span class="needs-attention-name">{{ $scholarship['name'] }}</span>
                            <span class="needs-attention-capacity-value">{{ $scholarship['active_applications'] }}/{{ $scholarship['max_slots'] }} slots filled</span>
                        </div>
                        <div class="needs-attention-progress-track" aria-hidden="true">
                            <span class="needs-attention-progress-bar needs-attention-progress-{{ $scholarship['urgency'] }}" style="width: {{ min(100, $scholarship['percentage']) }}%"></span>
                        </div>
                    </div>
                @empty
                    <p class="needs-attention-empty">Nothing needs attention right now.</p>
                @endforelse
            </section>
        </section>
    @endif

@if($filterType !== 'dashboard')
<div class="grid-2"> 
<div class="card">
    <div class="card-title">
        @if($filterType === 'old-academic')
            <div class="old-grants-toolbar">
                @include('admin.partials.old-grant-filters', ['filterType' => $filterType, 'scholarshipNames' => $scholarshipNames ?? collect(), 'yearLevels' => $yearLevels ?? collect(), 'courses' => $courses ?? collect()])
                <button class="btn small" onclick="openOldGrantUploadModal()">Upload Old Data</button>
            </div>
        @elseif($filterType === 'old-non-academic')
            <div class="old-grants-toolbar">
                @include('admin.partials.old-grant-filters', ['filterType' => $filterType, 'scholarshipNames' => $scholarshipNames ?? collect(), 'yearLevels' => $yearLevels ?? collect(), 'courses' => $courses ?? collect()])
                <button class="btn small" onclick="openOldGrantUploadModal()">Upload Old Data</button>
            </div>
        @elseif($filterType === 'users')
            Registered Users
        @endif
        @if(in_array($filterType, ['academic', 'non-academic'], true))
            @include('admin.partials.grant-filters', ['filterType' => $filterType, 'scholarshipNames' => $scholarshipNames ?? collect()])
        @elseif($filterType === 'all')
            @include('admin.partials.application-filters')
        @endif
        @if($filterType !== 'all' && $filterType !== 'academic' && $filterType !== 'non-academic' && $filterType !== 'old-academic' && $filterType !== 'old-non-academic')
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
                    <th>Row No.</th>
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
                        <td>{{ ($oldRecords->currentPage() - 1) * $oldRecords->perPage() + $loop->iteration }}</td>
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
            {{ $oldRecords->links('vendor.pagination.admin') }}
        @endif
        @else
        {{-- The Summary of Reports is approved-only, so Amount is always visible there. --}}
        @php
            $showAmountColumn = $filterType === 'all' || request('status') === 'approved';
        @endphp
        <table id="admin-applications-table" data-grant-type="{{ $filterType }}" data-show-amount="{{ $showAmountColumn ? '1' : '0' }}" @if($filterType === 'academic') data-today="{{ now()->format('Y-m-d') }}" data-college-lookup-url="{{ route('admin.academic.manual_applicants.college_department') }}" data-manual-store-url="{{ route('admin.academic.manual_applicants.store') }}" data-manual-update-url-template="{{ route('admin.academic.manual_applicants.update', ['id' => '__ID__']) }}" data-academic-scholarships='@json($academicScholarshipCatalog ?? [])' data-academic-store-url="{{ route('admin.academic.manual_applicants.store') }}" data-non-academic-store-url="{{ route('admin.non_academic.manual_applicants.store') }}" @elseif($filterType === 'non-academic') data-today="{{ now()->format('Y-m-d') }}" data-college-lookup-url="{{ route('admin.academic.manual_applicants.college_department') }}" data-manual-store-url="{{ route('admin.non_academic.manual_applicants.store') }}" data-manual-update-url-template="{{ route('admin.non_academic.manual_applicants.update', ['id' => '__ID__']) }}" data-non-academic-scholarships='@json($nonAcademicScholarshipCatalog ?? [])' data-academic-store-url="{{ route('admin.academic.manual_applicants.store') }}" data-non-academic-store-url="{{ route('admin.non_academic.manual_applicants.store') }}" @else data-today="{{ now()->format('Y-m-d') }}" data-manual-update-url-template="{{ url('/admin/manual-applicants/__ID__') }}" data-college-lookup-url="{{ route('admin.academic.manual_applicants.college_department') }}" data-academic-scholarships='@json($academicScholarshipCatalog ?? [])' data-non-academic-scholarships='@json($nonAcademicScholarshipCatalog ?? [])' data-academic-store-url="{{ route('admin.academic.manual_applicants.store') }}" data-non-academic-store-url="{{ route('admin.non_academic.manual_applicants.store') }}" @endif>
            <thead>
                <tr>
                    @if(in_array($filterType, ['academic', 'non-academic'], true))
                        <th>Full Name</th>
                        <th>Sex</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Name of Scholarship</th>
                        <th>Date</th>
                        @if($showAmountColumn)
                            <th>Amount</th>
                        @endif
                        <th>Status</th>
                        <th>Actions</th>
                    @else
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Suffix</th>
                        <th>Sex</th>
                        <th>Course</th>
                        <th>College or Department</th>
                        <th>Year Level</th>
                        <th>Type of Scholarship</th>
                        <th>Name of Scholarship</th>
                        @if($filterType === 'all' || in_array($filterType, ['academic', 'non-academic'], true))
                            <th>Funding</th>
                            <th>Campus</th>
                        @endif
                        <th>Date</th>
                        @if($showAmountColumn)
                            {{-- CHANGE: Display the saved applicant amount only in Approved view. --}}
                            <th>Amount</th>
                        @endif
                        <th>Status</th>
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @if($recentApplications->count() > 0)
                @foreach($recentApplications as $app)
                    @php
                        $fullNameParts = array_filter([
                            $app->last_name ?? null,
                            $app->middle_name ?? null,
                            $app->first_name ?? null,
                            $app->suffix ?? null,
                        ], fn ($value) => !is_null($value) && trim((string) $value) !== '');
                        $fullName = trim(implode(', ', $fullNameParts));
                    @endphp
                    <tr data-source="{{ $app->source ?? 'application' }}" data-id="{{ $app->id }}">
                        @if(in_array($filterType, ['academic', 'non-academic'], true))
                            <td>{{ $fullName !== '' ? strtoupper($fullName) : '—' }}</td>
                            <td>{{ $app->sex }}</td>
                            <td>{{ $app->course }}</td>
                            <td>{{ $app->year_level }}</td>
                            <td>{{ ($app->source ?? 'application') === 'manual' ? $app->scholarship_name : ($app->scholarship_name ?? '—') }}</td>
                            <td>{{ optional($app->created_at)->format('Y-m-d') }}</td>
                            @if($showAmountColumn)
                                <td>
                                    @if($app->application_status === 'approved' && $app->amount !== null)
                                        ₱{{ number_format((float) $app->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            <td>
                                @if($app->renewal_required)
                                    <span class="badge approved">Approved</span>
                                @elseif(isset($app->application_status) && $app->application_status === 'approved')
                                    <span class="badge approved">Approved</span>
                                @elseif(isset($app->application_status) && $app->application_status === 'rejected')
                                    <span class="badge rejected">Rejected</span>
                                @else
                                    <span class="badge pending">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if(($app->source ?? 'application') === 'manual')
                                    <button class="btn small manual-edit-btn" data-id="{{ $app->id }}" type="button">Edit</button>
                                    <button class="btn small manual-delete-btn" data-id="{{ $app->id }}" type="button">Delete</button>
                                @else
                                    <button class="btn small view-profile-btn" data-id="{{ $app->id }}">Applicant info</button>
                                @endif
                            </td>
                        @else
                            <td>{{ $app->last_name }}</td>
                            <td>{{ $app->first_name }}</td>
                            <td>{{ $app->middle_name }}</td>
                            <td>{{ $app->suffix ?? '—' }}</td>
                            <td>{{ $app->sex }}</td>
                            <td>{{ $app->course }}</td>
                            <td>{{ $app->college_department ?? '—' }}</td>
                            <td>{{ $app->year_level }}</td>
                            <td>{{ ($app->source ?? 'application') === 'manual' ? ($app->grant_type === 'academic' ? 'Academic' : 'Non-Academic') : (($app->scholarship_category_type ?? null) === 'academic' ? 'Academic' : ((($app->scholarship_category_type ?? null) === 'non-academic') ? 'Non-Academic' : 'Unknown')) }}</td>
                            <td>{{ ($app->source ?? 'application') === 'manual' ? $app->scholarship_name : ($app->scholarship_name ?? '—') }}</td>
                            @if($filterType === 'all' || in_array($filterType, ['academic', 'non-academic'], true))
                                <td>{{ ($app->scholarship_funding_type ?? null) ?: '—' }}</td>
                                <td>{{ $app->campus ?: 'Aparri' }}</td>
                            @endif
                            <td>{{ optional($app->created_at)->format('Y-m-d') }}</td>

                            @if($showAmountColumn)
                                {{-- CHANGE: Preserve approved-row amount formatting when the column is visible. --}}
                                <td>
                                    @if($app->application_status === 'approved' && $app->amount !== null)
                                        ₱{{ number_format((float) $app->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif

                            <td>
                                @if($app->renewal_required)
                                    <span class="badge approved">Approved</span>
                                @elseif(isset($app->application_status) && $app->application_status === 'approved')
                                    <span class="badge approved">Approved</span>
                                @elseif(isset($app->application_status) && $app->application_status === 'rejected')
                                    <span class="badge rejected">Rejected</span>
                                @else
                                    <span class="badge pending">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if(($app->source ?? 'application') === 'manual')
                                    <button class="btn small manual-edit-btn" data-id="{{ $app->id }}" type="button">Edit</button>
                                    <button class="btn small manual-delete-btn" data-id="{{ $app->id }}" type="button">Delete</button>
                                @else
                                    <button class="btn small view-profile-btn" data-id="{{ $app->id }}">Applicant info</button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            @else
                <tr>
                    {{-- CHANGE: Match the empty-state span to the conditional Amount column. --}}
                    <td colspan="{{ 13 + (in_array($filterType, ['academic', 'non-academic'], true) ? 2 : 0) + ($showAmountColumn ? 1 : 0) }}" class="text-center text-gray empty-state-row">No applications found.</td>
                </tr>
            @endif
        </tbody>
        </table>
        @if(method_exists($recentApplications, 'links'))
            {{ $recentApplications->links('vendor.pagination.admin') }}
        @endif
        @endif
    </div>
@endif

            <!-- Profile Modal -->
       <div id="profileModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <div class="application-modal-header">
                <button type="button" class="academic-nav-btn" id="profilePreviousTop" aria-label="Back to previous step">← Back</button>
                <h1 class="form-title">Scholarship Application</h1>
                <button type="button" class="modal-close" id="closeProfile" aria-label="Close applicant profile">✕</button>
            </div>
            <div class="academic-progress-wrapper">
                <div class="academic-progress-header">Step <span id="profileStepCounter">1</span> of <span id="profileStepTotal">3</span></div>
                <div class="academic-progress-track" aria-live="polite">
                    <div class="academic-progress-step active" data-profile-progress-step="1"><div class="academic-progress-dot">1</div><div class="academic-progress-label">Student Information</div></div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-profile-progress-step="2"><div class="academic-progress-dot">2</div><div class="academic-progress-label">Personal Information</div></div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-profile-family-progress hidden><div class="academic-progress-dot">3</div><div class="academic-progress-label">Family Background</div></div>
                    <div class="academic-progress-line" data-profile-family-progress-line hidden></div>
                    <div class="academic-progress-step" data-profile-final-progress data-profile-progress-step="3"><div class="academic-progress-dot">3</div><div class="academic-progress-label">Scholarship &amp; Requirements</div></div>
                </div>
            </div>
            <div id="profileBody">Loading...</div>
            <div class="academic-form-actions profile-pagination" aria-label="Applicant profile pages">
                <button type="button" class="academic-nav-btn" id="profilePrevious" aria-label="Previous applicant profile page">← Previous</button>
                <span id="profilePageCounter" aria-live="polite">1 / 3</span>
                <button type="button" class="academic-nav-btn" id="profileNext" aria-label="Next applicant profile page">Next →</button>
            </div>
        </div>
    </div>

<div id="documentPreviewModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div class="modal-content" style="max-width:1100px;width:92%;background:white;border-radius:8px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,0.3);position:relative;">
        <button id="closeDocumentPreviewModal" class="modal-close-btn" style="position:absolute;top:10px;right:10px;background:none;border:none;font-size:24px;cursor:pointer;">✕</button>
        <h2 id="documentPreviewTitle" style="margin-top:0;">Document Preview</h2>
        <div style="padding:10px 0; height:75vh; min-height:480px;">
            <iframe id="documentPreviewFrame" src="" style="width:100%;height:100%;border:1px solid #ddd;border-radius:6px;background:#fff;" title="Document preview"></iframe>
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
          <script src="{{ asset('js/admin-sidebar.js') }}"></script>
          <script>
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
