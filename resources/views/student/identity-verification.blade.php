<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity Verification Required</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: min(720px, 100%);
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            padding: 32px;
            border: 1px solid #e5e7eb;
        }
        .eyebrow {
            color: #14532d;
            background: #dcfce7;
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 18px 0 10px;
            font-size: clamp(2rem, 4vw, 2.5rem);
        }
        .meta {
            font-size: 0.95rem;
            color: #475569;
            margin-bottom: 20px;
        }
        .list {
            list-style: none;
            padding: 0;
            margin: 20px 0 28px;
            display: grid;
            gap: 12px;
        }
        .list li {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .check {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #14532d;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 700;
            transition: opacity 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            background: #14532d;
            color: #fff;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }
        .status {
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .status strong { color: #0f172a; }
        @media (max-width: 640px) {
            .card { padding: 22px 18px; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="eyebrow">Identity Verification Required</div>
            <h1>Verify your identity before applying</h1>
            <p class="meta">
                Scholarship: <strong>{{ $scholarship->name }}</strong>
            </p>

            <p>Before continuing with your scholarship application, you must verify your identity. This secure step helps confirm that the student applying is the same person submitting the scholarship documents.</p>

            <ul class="list">
                <li><span class="check">✓</span> Upload your valid student ID</li>
                <li><span class="check">✓</span> Complete face and liveness verification</li>
                <li><span class="check">✓</span> Confirm your identity before the application form unlocks</li>
            </ul>

            @if($verification && $verification->status)
                <div class="status">
                    <strong>Current Status:</strong> {{ $verification->status }}
                    @if($verification->status === 'Approved')
                        <p style="margin: 12px 0 0;">Your verification has been approved. You can proceed to the scholarship application.</p>
                    @elseif($verification->status === 'Declined')
                        <p style="margin: 12px 0 0;">Your verification was unsuccessful. Please retry when ready.</p>
                    @elseif($verification->status === 'In Review')
                        <p style="margin: 12px 0 0;">Your identity verification is currently under review. Please wait until verification is completed.</p>
                    @elseif($verification->status === 'Expired')
                        <p style="margin: 12px 0 0;">This verification session expired. You may start a new one.</p>
                    @else
                        <p style="margin: 12px 0 0;">The verification process is still incomplete. Please finish the check before continuing.</p>
                    @endif
                </div>
            @endif

            <div class="btn-row" style="margin-top: 26px;">
                @if($verification && $verification->status === 'Approved')
                    <a href="{{ route('didit.continue', $scholarship) }}" class="btn btn-primary">Continue to Application</a>
                @else
                    <a href="{{ route('didit.start', $scholarship) }}" class="btn btn-primary">Verify My Identity</a>
                @endif
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
