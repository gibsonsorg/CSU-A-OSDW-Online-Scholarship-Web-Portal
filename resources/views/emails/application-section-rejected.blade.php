<p>Hello {{ $studentName }},</p>

@if($documentName)
    <p>Your uploaded document has been rejected by the administrator.</p>
    <p><strong>Document:</strong><br>{{ $documentName }}</p>
@else
    <p>Your {{ $section }} submission for your scholarship application has been rejected by the administrator.</p>
@endif

<p><strong>Reason:</strong><br>{{ $reason }}</p>
<p>Please log in to your account and correct the required information or upload the corrected document.</p>
<p>Thank you.</p>