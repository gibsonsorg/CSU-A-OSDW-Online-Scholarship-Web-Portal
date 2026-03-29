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
            color: #FF9800;
            border-bottom-color: #FF9800;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
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

            <!-- Sign In Header -->
            <div class="auth-header">
                <h2>Sign in</h2>
                <p>Sign in to access your Municipality Portal account</p>
            </div>

            <!-- Tabs -->
            <div class="tabs-container">
                <button type="button" class="tab-button active" onclick="switchTab('student')">Student Login</button>
                <button type="button" class="tab-button" onclick="switchTab('admin')">Admin Login</button>
            </div>

            <!-- Student Login Form -->
            <div id="student" class="tab-content active">
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <input type="hidden" name="login_type" value="student">
                    
                    <div class="form-group">
                        <label for="studentId">STUDENT ID NUMBER</label>
                        <input type="text" id="studentId" name="student_id" placeholder="Enter Student ID (e.g., 23-11941)" value="{{ old('student_id') }}" pattern="[0-9\-]{1,8}" maxlength="10" required>
                        @error('student_id')
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

                    <div class="divider">
                        <span>or</span>
                    </div>

                    <div class="switch-form">
                        Don't have an account? <a href="{{ route('register') }}">Create Account</a>
                    </div>
                </form>
            </div>

            <!-- Admin Login Form -->
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

                    <div class="divider">
                        <span>or</span>
                    </div>

                    <div class="switch-form">
                        Back to <a href="#" onclick="switchTab('student'); return false;">Student Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

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






