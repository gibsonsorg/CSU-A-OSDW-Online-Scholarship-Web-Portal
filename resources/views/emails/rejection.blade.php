<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Application Rejected</title>
</head>
<body>
    <p>Dear {{ $user->name }},</p>

    @if($attempt >= 2)
        <p>Your registration application has been rejected for the second time.</p>

        @if (!empty($rejectionReason))
            <p><strong>Reason:</strong> {{ $rejectionReason }}</p>
        @endif

        <p>You have reached the maximum of 2 registration attempts and cannot submit another registration using these credentials.</p>

        <p>If you believe this decision was made in error, please contact the System Administrator at lgu.camal.gov.ph@gmail.com.</p>
    @else
        <p>Your registration application has been rejected by the System Administrator.</p>

        @if (!empty($rejectionReason))
            <p><strong>Reason:</strong> {{ $rejectionReason }}</p>
        @endif

        <p>You may submit another registration. Please make sure that your information and submitted documents are complete and accurate.</p>

        <p>If you believe this rejection was made in error, please contact the System Administrator at lgu.camal.gov.ph@gmail.com.</p>
    @endif

    <p>Thank you for your understanding.</p>

    <p>Sincerely,<br>
    Scholarship System Administration</p>
</body>
</html>
