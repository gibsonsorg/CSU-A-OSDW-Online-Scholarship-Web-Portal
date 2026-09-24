<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSDW Portal - Login</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <style>
        .tabs-container {
            display: flex;
            gap: 0;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-button {
            padding: 15px 25px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            margin-bottom: -2px;
        }

        .tab-button:hover {
            color: #333;
            background-color: #f5f5f5;
        }

        .tab-button.active {
            color: #8b1a28;
            border-bottom-color: #8b1a28;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if(session('verification_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    text: @json(session('verification_success')),
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = @json(route('login'));
                });
            });
        </script>
    @endif

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

            <div class="auth-header">
                <h2>Sign in</h2>
                <p>Sign in to access your Scholarship Portal account</p>
            </div>


            <div id="student" class="tab-content active">
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <input type="hidden" name="login_type" value="student">

                    <div class="form-group">
                        <label for="studentIdentifier">EMAIL ADDRESS </label>
                        <input type="text" id="studentIdentifier" name="login_identifier" placeholder="Enter your email or Student ID" value="{{ old('login_identifier') }}" required>
                        @error('login_identifier')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="studentPassword">PASSWORD</label>
                        <div class="input-wrapper">
                            <input type="password" id="studentPassword" name="password" placeholder="Enter your password" required>
                            <span class="toggle-password" onclick="togglePassword('studentPassword')"></span>
                        </div>
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="forgot-password">
                        <a href="{{ route('password.request') }}">Forgot your password?</a>
                    </div>

                    <button type="submit" class="btn">Sign In</button>

                    <div class="divider"><span>or</span></div>
                    <div class="switch-form">Don't have an account? <a href="{{ route('register') }}">Create Account</a></div>
                </form>
            </div>

            <div id="admin" class="tab-content">
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <input type="hidden" name="login_type" value="admin">

                    <div class="form-group">
                        <label for="adminEmail">EMAIL ADDRESS</label>
                        <input type="email" id="adminEmail" name="email" placeholder="Enter your email address" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="adminPassword">PASSWORD</label>
                        <div class="input-wrapper">
                            <input type="password" id="adminPassword" name="password" placeholder="Enter your password" required>
                            <span class="toggle-password" onclick="togglePassword('adminPassword')"></span>
                        </div>
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="forgot-password">
                        <a href="{{ route('password.request') }}">Forgot your password?</a>
                    </div>

                    <button type="submit" class="btn">Sign In</button>

                    <div class="divider"><span>or</span></div>
                    <div class="switch-form">Back to <a href="#" onclick="switchTab('student', document.querySelector('.tab-button')); return false;">Student Login</a></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName, button) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            button.classList.add('active');
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>






