<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <img src="{{ asset('images/lgucamal.png') }}" alt="Municipality Logo">
            </div>
            <h2 style="color:black;">Your gateway to Scholarship and beneficiaries web portal</h2>
        </div>

        <div class="right-panel">
            <form action="{{ route('password.verify.submit') }}" method="POST">
                @csrf
                
                <div class="auth-header">
                    <h2>Enter Verification Code</h2>
                    <p>Please enter the 6-digit code sent to your email</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <input type="email" id="email" name="email" value="{{ session('email') ?? old('email') }}" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="code">VERIFICATION CODE</label>
                    <input type="text" id="code" name="code" placeholder="Enter 6-digit code" maxlength="6" required>
                </div>

                <button type="submit" class="btn">Verify Code</button>

                <div class="switch-form">
                    <a href="{{ route('password.request') }}">Resend Code</a> | 
                    <a href="{{ route('login') }}">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>