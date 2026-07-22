<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | CSU Aparri</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
    <style>
        .status-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
            animation: fadeUp 0.45s ease both;
        }

        .status-hero {
            background: linear-gradient(135deg, #ffffff 0%, #f8f5ff 100%);
            border: 1px solid #ece8f4;
            border-radius: 20px;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 30px rgba(109, 40, 217, 0.08);
        }

        .status-hero-top {
            text-align: center;
        }

        .status-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 3vw, 2.1rem);
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 0.5rem;
        }

        .status-hero p {
            margin: 0 auto;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            max-width: 520px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            border: 1px solid #ececec;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
            margin-bottom: 0.35rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1;
        }

        .stat-card.pending .stat-value { color: #1d4ed8; }
        .stat-card.approved .stat-value { color: #15803d; }
        .stat-card.rejected .stat-value { color: #b91c1c; }

        .status-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .status-panel {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #ececec;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .status-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.15rem 1.35rem;
            border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .status-panel-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
        }

        .count-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            background: #f3f4f6;
            color: #555;
        }

        .app-list {
            padding: 0.75rem;
        }

        .app-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.95rem 1rem;
            border-radius: 12px;
            transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
            border: 1px solid transparent;
        }

        .app-item:hover {
            background: #f9fafb;
        }

        .app-item + .app-item {
            border-top: 1px solid #f3f4f6;
        }

        .app-item.is-rejected {
            cursor: pointer;
        }

        .app-item.is-rejected:hover {
            background: #f9fafb;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }

        .app-item-main {
            min-width: 0;
        }

        .app-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 0.35rem;
            line-height: 1.35;
        }

        .app-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.82rem;
            color: #777;
        }

        .view-reason-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #1a1a1a;
            font-weight: 600;
            font-size: 0.78rem;
        }

        .app-item-status {
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-pending {
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }

        .status-approved {
            background: rgba(34, 197, 94, 0.12);
            color: #15803d;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        .empty-state {
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .empty-state h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 0.35rem;
        }

        .empty-state p {
            font-size: 0.88rem;
            color: #888;
            margin: 0 0 1rem;
            line-height: 1.5;
        }

        .empty-cta {
            display: inline-block;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            background: #6b0f1a;
            color:white;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.3s ease-in-out;
        }

        .empty-cta:hover {
        transform: translateY(-3px);
        }

        /* Rejection detail modal */
        .detail-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .detail-overlay.is-open {
            display: flex;
        }

        .detail-modal {
            background: #fff;
            border-radius: 20px;
            width: min(520px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
            animation: modalIn 0.25s ease both;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .detail-modal-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }

        .detail-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: #f3f4f6;
            color: #666;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .detail-close:hover {
            background: #e5e7eb;
            color: #333;
        }

        .detail-modal-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 0.5rem;
            padding-right: 2rem;
            line-height: 1.35;
        }

        .detail-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            font-size: 0.82rem;
            color: #777;
        }

        .detail-modal-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .detail-section-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #888;
            margin: 0 0 0.6rem;
        }

        .rejection-message-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 1.1rem 1.15rem;
            color: #7f1d1d;
            font-size: 0.92rem;
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .rejection-message-box.is-empty {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: #6b7280;
            font-style: italic;
        }

        .detail-help-box {
            margin-top: 1.25rem;
            padding: 1rem 1.1rem;
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
        }

        .detail-help-box p {
            margin: 0 0 0.75rem;
            font-size: 0.85rem;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .detail-help-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .detail-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.9rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .detail-btn-primary {
            background: #1a1a1a;
            color: #fff;
        }

        .detail-btn-primary:hover {
            background: #333;
            color: #fff;
        }

        .detail-btn-secondary {
            background: #fff;
            color: #1a1a1a;
            border: 1px solid #1a1a1a;
        }

        .detail-btn-secondary:hover {
            background: #f5f5f5;
            color: #1a1a1a;
        }

        @media (max-width: 900px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .status-sections {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 520px) {
            .status-hero {
                padding: 1.35rem 1.25rem;
            }

            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .app-item {
                grid-template-columns: 1fr;
            }

            .app-item-status {
                text-align: left;
            }
        }
    </style>
</head>
<body>

<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8"></div>
    </div>
</x-app-layout>

@php
    $allApplications = $academicApplications->concat($nonAcademicApplications);
    $totalCount = $allApplications->count();
    $pendingCount = $allApplications->where('application_status', 'pending')->count();
    $approvedCount = $allApplications->where('application_status', 'approved')->count();
    $rejectedCount = $allApplications->where('application_status', 'rejected')->count();
@endphp

<div class="main">
    <div class="status-page">
        <div class="status-hero">
            <div class="status-hero-top">
                <h1>My Application Status</h1>
                <p>Track all your scholarship applications in one place. Click any rejected application to read the administrator's feedback.</p>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total</div>
                <div class="stat-value">{{ $totalCount }}</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ $pendingCount }}</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $approvedCount }}</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-label">Rejected</div>
                <div class="stat-value">{{ $rejectedCount }}</div>
            </div>
        </div>

        <div class="status-sections">
            <section class="status-panel">
                <div class="status-panel-header">
                    <h2>Academic Scholarships</h2>
                    <span class="count-badge">{{ $academicApplications->count() }} {{ Str::plural('application', $academicApplications->count()) }}</span>
                </div>

                @if($academicApplications->isEmpty())
                    <div class="empty-state">
                        <h3>No academic applications yet</h3>
                        <p>Browse available academic grants and submit your first application.</p>
                        <a href="{{ route('dashboard', ['section' => 'acad']) }}" class="empty-cta">Browse Academic Grants</a>
                    </div>
                @else
                    <div class="app-list">
                        @foreach($academicApplications as $application)
                            @include('student.partials.application-status-item', ['application' => $application])
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="status-panel">
                <div class="status-panel-header">
                    <h2>Non-Academic Scholarships</h2>
                    <span class="count-badge">{{ $nonAcademicApplications->count() }} {{ Str::plural('application', $nonAcademicApplications->count()) }}</span>
                </div>

                @if($nonAcademicApplications->isEmpty())
                    <div class="empty-state">
                        <h3>No non-academic applications yet</h3>
                        <p>Explore non-academic grants and start a new application when you're ready.</p>
                        <a href="{{ route('dashboard', ['section' => 'non']) }}" class="empty-cta">Browse Non-Academic Grants</a>
                    </div>
                @else
                    <div class="app-list">
                        @foreach($nonAcademicApplications as $application)
                            @include('student.partials.application-status-item', ['application' => $application])
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

<div id="rejectionDetailOverlay" class="detail-overlay" role="dialog" aria-modal="true" aria-labelledby="rejectionDetailTitle">
    <div class="detail-modal">
        <div class="detail-modal-header">
            <button type="button" class="detail-close" id="rejectionDetailClose" aria-label="Close">&times;</button>
            <h2 id="rejectionDetailTitle"></h2>
            <div class="detail-meta-row">
                <span id="rejectionDetailType"></span>
                <span id="rejectionDetailApplied"></span>
                <span id="rejectionDetailUpdated"></span>
            </div>
        </div>
        <div class="detail-modal-body">
            <p class="detail-section-label">Message from Administrator</p>
            <div id="rejectionDetailMessage" class="rejection-message-box"></div>

            <div class="detail-help-box">
                <p>You may review the feedback above, update your documents if needed, and apply for other available scholarships.</p>
                <div class="detail-help-actions">
                    <a href="{{ route('dashboard', ['section' => 'acad']) }}" class="detail-btn detail-btn-primary" id="rejectionBrowseLink">Browse Scholarships</a>
                    <button type="button" class="detail-btn detail-btn-secondary" id="rejectionDetailCloseBtn">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $rejectedApplicationsData = $allApplications
        ->where('application_status', 'rejected')
        ->map(function ($application) {
            return [
                'id' => $application->id,
                'scholarship_name' => $application->scholarship_name,
                'scholarship_type' => $application->scholarship_type,
                'grant_type' => $application->grant_type,
                'applied_at' => $application->created_at->format('M d, Y'),
                'updated_at' => $application->updated_at->format('M d, Y'),
                'rejection_reason' => $application->rejection_reason,
            ];
        })
        ->values();
@endphp

<script id="rejectedApplicationsData" type="application/json">@json($rejectedApplicationsData)</script>

<script>
(function () {
    const overlay = document.getElementById('rejectionDetailOverlay');
    const titleEl = document.getElementById('rejectionDetailTitle');
    const typeEl = document.getElementById('rejectionDetailType');
    const appliedEl = document.getElementById('rejectionDetailApplied');
    const updatedEl = document.getElementById('rejectionDetailUpdated');
    const messageEl = document.getElementById('rejectionDetailMessage');
    const browseLink = document.getElementById('rejectionBrowseLink');
    const rejectedApplications = JSON.parse(
        document.getElementById('rejectedApplicationsData').textContent || '[]'
    );
    const rejectedById = Object.fromEntries(
        rejectedApplications.map(function (application) {
            return [String(application.id), application];
        })
    );

    function openRejectionDetail(applicationId) {
        const application = rejectedById[String(applicationId)];
        if (!application) {
            return;
        }

        titleEl.textContent = application.scholarship_name || 'Scholarship Application';
        typeEl.textContent = application.scholarship_type ? 'Type: ' + application.scholarship_type : '';
        appliedEl.textContent = application.applied_at ? 'Applied: ' + application.applied_at : '';
        updatedEl.textContent = application.updated_at ? 'Updated: ' + application.updated_at : '';

        const reason = (application.rejection_reason || '').trim();
        if (reason) {
            messageEl.textContent = reason;
            messageEl.classList.remove('is-empty');
        } else {
            messageEl.textContent = 'No specific reason was provided. Please contact the scholarship office for more information.';
            messageEl.classList.add('is-empty');
        }

        browseLink.href = application.grant_type === 'non-academic'
            ? '{{ route('dashboard', ['section' => 'non']) }}'
            : '{{ route('dashboard', ['section' => 'acad']) }}';

        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeRejectionDetail() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.app-item.is-rejected').forEach(function (item) {
        item.addEventListener('click', function () {
            openRejectionDetail(item.dataset.applicationId);
        });

        item.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openRejectionDetail(item.dataset.applicationId);
            }
        });
    });

    document.getElementById('rejectionDetailClose').addEventListener('click', closeRejectionDetail);
    document.getElementById('rejectionDetailCloseBtn').addEventListener('click', closeRejectionDetail);

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeRejectionDetail();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeRejectionDetail();
        }
    });
})();
</script>

</body>
</html>
