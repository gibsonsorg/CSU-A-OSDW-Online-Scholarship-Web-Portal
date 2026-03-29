<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSDW Portal - Register</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">   
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
             <img src="{{ asset('images/osdw logo.jpg') }}" alt="OSDW Logo">
            </div>
            <h2 style="color:black;">Your gateway to Scholarship and beneficiaries web portal</h2>
        </div>  

        <div class="right-panel">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="back-button">
                <a href="/" class="back-link">
                    <span class="arrow">←</span>
                    <span>Back</span>
                </a>
            </div>

            <!-- Register Form -->
            <form id="registerForm" action="{{ route('register') }}" method="POST">
                @csrf
                <div class="auth-header">
                    <h2>Create Account</h2>
                    <p>Join the Municipality Portal</p>
                </div>

                <div class="form-group">
                    <label for="regFullName">FULL NAME</label>
                    <input type="text" id="regFullName" name="name" placeholder="Enter your full name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="regStudentId">STUDENT ID NUMBER</label>
                    <input type="text" id="regStudentId" name="student_id" placeholder="Enter Student ID (e.g., 23-11941)" value="{{ old('student_id') }}" pattern="[0-9\-]{1,8}" maxlength="10" required>
                    @error('student_id')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="regPassword">PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="regPassword" name="password" placeholder="Create a password" required>
                        <span class="toggle-password" onclick="togglePassword('regPassword')"></span>
                    </div>
                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="regConfirmPassword">CONFIRM PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="regConfirmPassword" name="password_confirmation" placeholder="Confirm your password" required>
                        <span class="toggle-password" onclick="togglePassword('regConfirmPassword')"></span>
                    </div>
                    @error('password_confirmation')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="agreeTerms" required>
                    <label for="agreeTerms">I agree to the Terms and Conditions</label>
                </div>

                <button type="submit" class="btn">Create Account</button>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="switch-form">
                    Already have an account? <a href="{{ route('login') }}">Sign In</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
            } else {
                field.type = 'password';
            }
        }
    </script>

</body>
</html>










