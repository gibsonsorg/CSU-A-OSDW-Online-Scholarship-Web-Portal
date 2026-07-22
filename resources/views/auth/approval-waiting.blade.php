<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Under Review - CSU Scholarship Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            background: #8b1a28;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px 30px;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            max-width: 100px;
            height: auto;
        }

       

        h1 {
            color: #333;
            font-size: 28px;
            margin: 20px 0 10px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .message {
            background: #e8f4f8;
            
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
            text-align: left;
            color: #2c3e50;
            font-size: 14px;
        }

        .message strong {
            display: block;
            margin-bottom: 8px;
            color:black;
            opacity: 0.8;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: left;
        }

        .info-box h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-box ul {
            list-style: none;
            color: #666;
            font-size: 13px;
            line-height: 1.8;
        }

        .info-box li {
            padding: 5px 0;
            display: flex;
            align-items: flex-start;
        }

        .info-box li:before {
            content: "✓";
            color: #27ae60;
            font-weight: bold;
            margin-right: 10px;
            min-width: 20px;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 140px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background:#8b1a28;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .contact-info {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            font-size: 13px;
            color: #999;
        }

        .contact-info strong {
            color: #666;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        .loading-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .auto-refresh {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }

        .auto-refresh strong {
            color:black;
            opacity:0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            @if(file_exists(public_path('images/csu.logo.png')))
                <img src="{{ asset('images/csu.logo.png') }}" alt="CSU Logo">
            @else
                <img src="{{ asset('images/osdw logo.jpg') }}" alt="OSDW Logo">
            @endif
        </div>

      

        <h1>Account Under Review</h1>
        <p class="subtitle">
            Your registration has been received successfully!
        </p>

        <div class="message">
            <strong>Status: Pending Approval</strong>
            Your account is currently under review by our administration team.You will receive an email notification once your application is approved.
        </div>

       
        <div class="actions">
            <a href="{{ route('login') }}" class="btn btn-secondary">← Back to Login</a>
            <a href="/" class="btn btn-primary">Go to Home</a>
        </div>

        <div class="auto-refresh">
            <span class="loading-spinner"></span>
            <strong>Checking for approval updates...</strong> (Auto-refresh every 60 seconds)
        </div>

        <div class="contact-info">
            <p>Questions? Contact us at <strong>support@csu.edu.ph</strong></p>
        </div>
    </div>

    <script>
        // Auto-refresh page every 60 seconds to check if approved
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
