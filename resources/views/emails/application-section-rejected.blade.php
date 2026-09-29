<p>Dear {{ $firstName }}, here is the current status of your application for {{ $scholarshipName }}:</p>

<ul>
    @foreach($sections as $section)
        <li>
            <strong>{{ $section['label'] }}:</strong> {{ $section['status'] }}
            @if($section['status'] === 'Rejected' && $section['reason'])
                - Reason: {{ $section['reason'] }}
            @endif
        </li>
    @endforeach
</ul>

<p>Overall Status: Rejected ({{ collect($sections)->where('status', 'Rejected')->count() }} of {{ count($sections) }} sections need your attention).</p>
<p>Please log in to your dashboard to review and correct the rejected section(s), then resubmit your application. If you have any questions, feel free to visit the OSDW office in Aparri.</p>