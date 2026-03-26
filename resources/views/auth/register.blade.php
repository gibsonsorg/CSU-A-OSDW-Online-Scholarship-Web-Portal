<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU Portal - Dashboard</title>
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

            <!-- Login Form -->
            <form id="loginForm" action="{{ route('login') }}" method="POST">
                @csrf
                <div class="auth-header">
                    <h2>Sign in</h2>
                    <p>Sign in to access your Municipality Portal account</p>
                </div>

                <div class="form-group">
                    <label for="loginEmail">EMAIL ADDRESS</label>
                    <input type="email" id="loginEmail" name="email" placeholder="Enter your email address" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="loginPassword">PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                        <span class="toggle-password" onclick="togglePassword('loginPassword')"></span>
                    </div>
                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="showForgotPassword(); return false;">Forgot your password?</a>
                </div>

                <button type="submit" class="btn">Sign In</button>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="switch-form">
                    Don't have an account? <a href="#" onclick="showRegister(); return false;">Create Account</a>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="hidden" action="{{ route('register') }}" method="POST">
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
                    Already have an account? <a href="#" onclick="showLogin(); return false;">Sign In</a>
                </div>
            </form>

            <!-- Forgot Password Form -->
            <form id="forgotForm" class="hidden" action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="auth-header">
                    <h2>Reset Password</h2>
                    <p>Enter your email to receive a verification code</p>
                </div>

                <div class="form-group">
                    <label for="forgotEmail">EMAIL ADDRESS</label>
                    <input type="email" id="forgotEmail" name="email" placeholder="Enter your email address" required>
                </div>

                <button type="submit" class="btn">Send Verification Code</button>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="switch-form">
                    Remember your password? <a href="#" onclick="showLogin(); return false;">Sign In</a>
                </div>
            </form>
        </div>
    </div>

 
    @if(old('name') || $errors->has('name') || $errors->has('password'))
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                if(typeof showRegister === 'function'){
                    showRegister();
                } else {
                    // fallback: show by toggling classes
                    var r = document.getElementById('registerForm');
                    var l = document.getElementById('loginForm');
                    if(r && l){ l.classList.add('hidden'); r.classList.remove('hidden'); }
                }
            });
        </script>
    @endif
    <script src="{{ asset('js/create.js') }}"></script>

</body>
</html>






