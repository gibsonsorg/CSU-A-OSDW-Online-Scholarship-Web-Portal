@php
    $statusSummary = $application->section_status_summary;
    $applicationStatus = strtolower($application->application_status);
    $statusSummaryText = match ($applicationStatus) {
        'approved' => "Approved — {$statusSummary['approved_count']} of {$statusSummary['total_count']} sections approved",
        'rejected' => "Rejected — {$statusSummary['rejected_count']} of {$statusSummary['total_count']} sections need attention",
        default => "Pending — {$statusSummary['reviewed_count']} of {$statusSummary['total_count']} sections reviewed",
    };
@endphp

<article
    class="app-item is-clickable"
    role="button"
    tabindex="0"
    aria-label="View application details for {{ $application->scholarship_name }}"
    data-application-id="{{ $application->id }}"
>
    <div class="app-item-main">
        <h3 class="app-name">{{ $application->scholarship_name }}</h3>
        <div class="app-meta">
            <span>Applied {{ $application->created_at->format('M d, Y') }}</span>
            <span class="view-reason-hint">{{ $statusSummaryText }} · View details &rarr;</span>
        </div>
    </div>
    <div class="app-item-status">
        <span class="status-badge status-{{ $application->application_status }}">
            {{ ucfirst($application->application_status) }}
        </span>
    </div>
</article>
