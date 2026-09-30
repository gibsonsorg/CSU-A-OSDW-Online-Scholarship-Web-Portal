<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSDW Portal - Register</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <style>
        .password-strength {
            margin-top: 12px;
            margin-bottom: 10px;
        }

        .strength-bar {
            width: 100%;
            height: 10px;
            background: #f2dede;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid rgba(139, 26, 40, 0.18);
        }

        .strength-bar-fill {
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: #dc2626;
            transition: width 0.2s ease, background-color 0.2s ease;
        }

        .strength-bar.is-valid .strength-bar-fill {
            background: #16a34a;
        }

        .strength-summary {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #7f1d1d;
        }

        .strength-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            margin-top: 8px;
        }

        .strength-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .strength-item .bullet {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            color: transparent;
            font-size: 10px;
            font-weight: 700;
        }

        .strength-item.is-met {
            color: #166534;
        }

        .strength-item.is-met .bullet {
            background: #22c55e;
            color: #fff;
        }
    </style>
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
                    <div class="password-strength" aria-live="polite">
                        <div class="strength-bar" id="strengthBar">
                            <div class="strength-bar-fill" id="strengthBarFill"></div>
                        </div>
                        <div class="strength-summary" id="strengthSummary">Password strength: Too weak</div>
                        <div class="strength-list">
                            <span class="strength-item" data-requirement="length"><span class="bullet">✓</span> 8+ characters</span>
                            <span class="strength-item" data-requirement="uppercase"><span class="bullet">✓</span> Uppercase</span>
                            <span class="strength-item" data-requirement="lowercase"><span class="bullet">✓</span> Lowercase</span>
                            <span class="strength-item" data-requirement="number"><span class="bullet">✓</span> Number</span>
                            <span class="strength-item" data-requirement="special"><span class="bullet">✓</span> Special character</span>
                        </div>
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
        const passwordInput = document.getElementById('regPassword');
        const strengthBar = document.getElementById('strengthBar');
        const strengthBarFill = document.getElementById('strengthBarFill');
        const strengthSummary = document.getElementById('strengthSummary');
        const strengthItems = Array.from(document.querySelectorAll('.strength-item'));

        const strengthChecks = [
            { key: 'length', test: (value) => value.length >= 8 },
            { key: 'uppercase', test: (value) => /[A-Z]/.test(value) },
            { key: 'lowercase', test: (value) => /[a-z]/.test(value) },
            { key: 'number', test: (value) => /\d/.test(value) },
            { key: 'special', test: (value) => /[^A-Za-z0-9]/.test(value) },
        ];

        function updatePasswordStrength() {
            const value = passwordInput.value;
            const completed = strengthChecks.filter(({ test }) => test(value)).length;
            const progress = (completed / strengthChecks.length) * 100;

            strengthBarFill.style.width = progress + '%';
            strengthBar.classList.toggle('is-valid', completed === strengthChecks.length);
            strengthBarFill.style.backgroundColor = completed === strengthChecks.length ? '#16a34a' : '#dc2626';

            if (completed === 0) {
                strengthSummary.textContent = 'Password strength: Too weak';
                strengthSummary.style.color = '#7f1d1d';
            } else if (completed < strengthChecks.length) {
                strengthSummary.textContent = 'Password strength: In progress';
                strengthSummary.style.color = '#b45309';
            } else {
                strengthSummary.textContent = 'Password strength: Strong';
                strengthSummary.style.color = '#166534';
            }

            strengthItems.forEach((item) => {
                const requirement = item.dataset.requirement;
                const rule = strengthChecks.find((check) => check.key === requirement);
                const isMet = rule ? rule.test(value) : false;
                item.classList.toggle('is-met', isMet);
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', updatePasswordStrength);
        }

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










