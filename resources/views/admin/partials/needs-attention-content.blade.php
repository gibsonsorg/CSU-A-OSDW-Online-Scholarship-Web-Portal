<div class="needs-attention-header">
    <div>
        <span class="needs-attention-icon" aria-hidden="true"></span>
        <h2 id="needs-attention-title">Needs Attention</h2>
    </div>
</div>

<div class="needs-attention-grid">
    <section class="needs-attention-section" aria-labelledby="pending-applications-title">
        <h3 id="pending-applications-title">Pending Applications (Aging)</h3>
        @if ($pendingApplicationsTotal === 0)
            <p class="needs-attention-empty">Nothing needs attention right now.</p>
        @elseif (empty($pendingApplications))
            <p class="needs-attention-empty">No more items.</p>
        @else
            @foreach ($pendingApplications as $application)
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
            @endforeach
        @endif
    </section>

    <section class="needs-attention-section" aria-labelledby="closing-soon-title">
        <h3 id="closing-soon-title">Scholarships Closing Soon</h3>
        @if ($scholarshipsClosingSoonTotal === 0)
            <p class="needs-attention-empty">Nothing needs attention right now.</p>
        @elseif (empty($scholarshipsClosingSoon))
            <p class="needs-attention-empty">No more items.</p>
        @else
            @foreach ($scholarshipsClosingSoon as $scholarship)
                <div class="needs-attention-item">
                    <div class="needs-attention-text">
                        <span class="needs-attention-name">{{ $scholarship['name'] }}</span>
                    </div>
                    <div class="needs-attention-actions">
                        <span class="needs-attention-badge needs-attention-badge-{{ $scholarship['urgency'] }}">{{ $scholarship['days_remaining'] }} day{{ $scholarship['days_remaining'] === 1 ? '' : 's' }}</span>
                        <a class="needs-attention-link" href="{{ route('admin.scholarships.availability', ['name' => $scholarship['name']]) }}">Manage <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            @endforeach
        @endif
    </section>
</div>

<section class="needs-attention-section needs-attention-section-full" aria-labelledby="near-capacity-title">
    <h3 id="near-capacity-title">Scholarships Near Capacity</h3>
    @if ($nearCapacityScholarshipsTotal === 0)
        <p class="needs-attention-empty">Nothing needs attention right now.</p>
    @elseif (empty($nearCapacityScholarships))
        <p class="needs-attention-empty">No more items.</p>
    @else
        @foreach ($nearCapacityScholarships as $scholarship)
            <div class="needs-attention-capacity-item">
                <div class="needs-attention-capacity-row">
                    <span class="needs-attention-name">{{ $scholarship['name'] }}</span>
                    <span class="needs-attention-capacity-value">{{ $scholarship['active_applications'] }}/{{ $scholarship['max_slots'] }} slots filled</span>
                </div>
                <div class="needs-attention-progress-track" aria-hidden="true">
                    <span class="needs-attention-progress-bar needs-attention-progress-{{ $scholarship['urgency'] }}" style="width: {{ min(100, $scholarship['percentage']) }}%"></span>
                </div>
            </div>
        @endforeach
    @endif
</section>

@if ($needsAttentionTotalPages > 1)
    <nav class="profile-pagination needs-attention-pagination" aria-label="Needs Attention pages">
        <button type="button" class="academic-nav-btn" data-needs-attention-page="{{ $needsAttentionPage - 1 }}" @disabled($needsAttentionPage <= 1)>← Previous</button>
        <span aria-live="polite">{{ $needsAttentionPage }} / {{ $needsAttentionTotalPages }}</span>
        <button type="button" class="academic-nav-btn" data-needs-attention-page="{{ $needsAttentionPage + 1 }}" @disabled($needsAttentionPage >= $needsAttentionTotalPages)>Next →</button>
    </nav>
@endif