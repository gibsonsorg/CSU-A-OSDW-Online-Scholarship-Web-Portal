<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Application Status</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px;">
        <p>Dear {{ $userName }},</p>

        @if ($status === 'approved')
            <p>Congratulations! Your application for the "{{ $scholarshipName }}" has been <strong>APPROVED</strong>.</p>
            <p>You may now proceed with the next steps or wait for further instructions.</p>
        @else
            <p>We regret to inform you that your application for the "{{ $scholarshipName }}" has been <strong>REJECTED</strong>.</p>
            <p>You may apply for other available scholarships.</p>
        @endif

        <p>Best regards,<br>
        Scholarship Office</p>
    </div>
</body>
</html>
