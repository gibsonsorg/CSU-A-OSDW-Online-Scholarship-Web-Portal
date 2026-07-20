@php
    $isRejected = $application->application_status === 'rejected';
@endphp

<article
    class="app-item {{ $isRejected ? 'is-rejected' : '' }}"
    @if($isRejected)
        role="button"
        tabindex="0"
        aria-label="View rejection details for {{ $application->scholarship_name }}"
        data-application-id="{{ $application->id }}"
    @endif
>
    <div class="app-item-main">
        <h3 class="app-name">{{ $application->scholarship_name }}</h3>
        <div class="app-meta">
            <span>Applied {{ $application->created_at->format('M d, Y') }}</span>
            @if($isRejected)
                <span class="view-reason-hint">View rejection details &rarr;</span>
            @endif
        </div>
    </div>
    <div class="app-item-status">
        <span class="status-badge status-{{ $application->application_status }}">
            {{ ucfirst($application->application_status) }}
        </span>
    </div>
</article>
