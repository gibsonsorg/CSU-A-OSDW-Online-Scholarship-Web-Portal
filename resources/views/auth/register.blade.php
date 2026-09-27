<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSDW Portal - Register</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">   
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="container">
        <div class="left-panel">
            <div class="logo">
             <img src="{{ asset('images/maroon osdw.png') }}" alt="OSDW Logo">
            </div>
            <h2 style="color:white;">Your gateway to Scholarship and beneficiaries web portal</h2>
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
                    
                </div>

                <div class="form-group">
                    <label for="regEmail">EMAIL ADDRESS</label>
                    <input type="email" id="regEmail" name="email" placeholder="Enter your email address" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="regPassword">PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="regPassword" name="password" placeholder="Create a password" required>
                        <button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false" onclick="togglePassword('regPassword', this)">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.7 10.7 0 0 0 4.1-.8"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="regConfirmPassword">CONFIRM PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="regConfirmPassword" name="password_confirmation" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false" onclick="togglePassword('regConfirmPassword', this)">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.7 10.7 0 0 0 4.1-.8"/></svg>
                        </button>
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
       
    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const errorType = '{{ session('error') }}';
                let title = 'Registration Error';
                let message = 'An error occurred. Please try again.';
                
                if (errorType === 'approved') {
                    title = 'Account Already Approved';
                    message = 'Your account has already been approved. You can now log in to the system.';
                }
                
                Swal.fire({
                    title: title,
                    text: message,
                    icon: 'error',
                    draggable: true,
                    confirmButtonText: 'OK'
                }).then((result) => {
                });
            });
        </script>
    @endif

    <script>
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            const isVisible = field.type === 'password';
            field.type = isVisible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(isVisible));
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
        }
    </script>

</body>
</html>










