<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity Verification Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px; background: #fff; border-radius: 10px; border: 1px solid #e5e7eb;">
        <p>Dear {{ $user->name ?? 'Student' }},</p>

        <p>Your identity verification has been successfully approved.</p>

        <p>You may now continue with your scholarship application for <strong>{{ $scholarship->name ?? 'the selected scholarship' }}</strong>.</p>

        <p>Thank you for completing the verification process.</p>

        <p>Best regards,<br>Scholarship Office</p>
    </div>
</body>
</html>
