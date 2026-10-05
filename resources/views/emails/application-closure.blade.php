<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Scholarship Application Has Been Closed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px;">
        <p>Dear {{ $studentName }},</p>
        <p>Your application for {{ $scholarshipName }} has been closed after its final resubmission.</p>
        <p><strong>Reason for closure:</strong> {{ $rejectionRemark }}</p>
        <p>Best regards,</p>
        <p>Scholarship Office</p>
    </div>
</body>
</html>