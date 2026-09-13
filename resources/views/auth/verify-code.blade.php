<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSDW Portal - Verify Email</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .verification-card {
            width: min(100% - 32px, 480px);
            padding: 38px 40px;
            background: #8b1a28;
            border-radius: 16px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.12);
            text-align: center;
        }

        .verification-card h1 {
            margin-bottom: 28px;
            color:white;
            font-size: 1.3rem;
        }

        .code-inputs {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .code-input {
            width: 48px;
            height: 54px;
            border: none;
            border-radius: 8px;
            color: #111827;
            font-size: 1.8rem;
            text-align: center;
            outline: none;
        }

        .code-input:focus {
             color: #8b1a28;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .verify-button {
            width: 100%;
            padding: 13px;
            border: 0;
            border-radius: 8px;
              color: #8b1a28;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background-color: #6b0f1a;
        }

        .expiry-text {
            margin: -12px 0 24px;
            color:white;
            font-size: 0.8rem;
        }

        .resend-button {
            margin-top: 20px;
            border: 0;
            background-color:#8b1a28;
              color:white;
            cursor: pointer;
            font-size: 0.95rem;

        }

        @media (max-width: 420px) {
            .verification-card { padding: 30px 18px; }
            .code-inputs { gap: 7px; }
            .code-input { width: 41px; height: 49px; }
        }
    </style>
</head>
<body>
    <main class="verification-card">
        <h1>Enter 6-digit authentication code</h1>

        <form id="verificationForm" action="{{ route('verification.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="verification_code" id="verificationCode">
            <div class="code-inputs" aria-label="Six-digit verification code">
                @for ($index = 0; $index < 6; $index++)
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" aria-label="Digit {{ $index + 1 }}" autocomplete="one-time-code">
                @endfor
            </div>
            <p class="expiry-text">Code will expire in 10 minutes.</p>
            <button class="verify-button" type="submit">Verify</button>
        </form>

        <form action="{{ route('verification.resend') }}" method="POST">
            @csrf
            <button class="resend-button" type="submit">Resend Code</button>
        </form>
    </main>

    @if(session('success'))
        <script>
            const successType = @json(session('success'));
            Swal.fire({
                title: successType === 'registration_verified' ? 'Registration Successful' : 'Verification Code Sent',
                text: successType === 'registration_verified' ? 'You can now login.' : 'A new verification code has been sent to your email.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed && successType === 'registration_verified') {
                    window.location.href = @json(route('login'));
                }
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            const verificationError = @json(session('error'));
            const verificationAlerts = {
                verification_incorrect: ['Incorrect Verification Code', 'Please enter the correct code.'],
                verification_expired: ['Verification Code Expired', 'Your verification code has expired. Please request a new code.'],
                verification_attempts: ['Verification Failed', 'Too many incorrect attempts. Please request a new code.'],
            };
            const alertContent = verificationAlerts[verificationError] || ['Verification Error', 'Please try again.'];
            Swal.fire({ title: alertContent[0], text: alertContent[1], icon: 'error', confirmButtonText: 'OK' });
        </script>
    @endif

    <script>
        const inputs = [...document.querySelectorAll('.code-input')];
        const form = document.getElementById('verificationForm');

        inputs[0].focus();
        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '');
                if (digits.length > 1) {
                    digits.slice(0, inputs.length - index).split('').forEach((digit, digitIndex) => {
                        inputs[index + digitIndex].value = digit;
                    });
                    inputs[Math.min(index + digits.length, inputs.length) - 1].focus();
                    return;
                }
                input.value = digits;
                if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
            });
            input.addEventListener('paste', (event) => {
                const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                if (!pasted) return;
                event.preventDefault();
                inputs.forEach((otpInput, digitIndex) => { otpInput.value = pasted[digitIndex] || ''; });
                inputs[Math.min(pasted.length, 6) - 1].focus();
            });
        });
        form.addEventListener('submit', (event) => {
            const code = inputs.map(input => input.value.trim()).join('');
            if (!/^\d{6}$/.test(code)) {
                event.preventDefault();
                inputs.find(input => !input.value)?.focus();
                return;
            }
            document.getElementById('verificationCode').value = code;
        });
    </script>
</body>
</html>