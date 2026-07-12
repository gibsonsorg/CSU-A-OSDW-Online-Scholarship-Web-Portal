<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Application Status</title>
</head>
<body>
    <p>Dear {{ $user->name }},</p>

    <p>We regret to inform you that your account application has not been approved at this time.</p>

    <p>This may be due to incomplete or invalid information provided during registration.</p>

    @if (!empty($message))
        <p><strong>Administrator message:</strong> {{ $message }}</p>
    @endif

    <p>If you believe this decision was made in error or if you would like further clarification, please feel free to contact the administrator.</p>

    <p>Thank you for your understanding.</p>

    <p>Sincerely,<br>
    Scholarship System Administration</p>
</body>
</html>
