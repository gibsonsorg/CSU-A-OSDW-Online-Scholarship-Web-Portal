<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Action Required: Your Scholarship Application Needs Corrections' }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px;">
        <p>Hi {{ $studentName }},</p>
        <p>Your application for {{ $scholarshipName }} needs a few corrections before it can be reviewed.</p>
        <p><strong>What to fix:</strong> {{ $rejectionRemark }}</p>
        <p><strong>What to do:</strong></p>
        <ol>
            <li>Log in to the CSU Grants portal.</li>
            <li>Open your application and tap View &amp; Apply.</li>
            <li>Correct the items above and submit again.</li>
        </ol>
        <p><strong>Deadline:</strong> {{ $deadline }}</p>
        <p>Your slot is being held for you until then. This is your final resubmission, so please double-check your documents and form before sending. If the deadline passes, the slot will be released and your application will expire.</p>
        <p>If you have questions, visit the OSDW Aparri office.</p>
        <p>Thank you,</p>
    </div>
</body>
</html>