@extends('layouts.auth-rms')

@section('title', 'Mock TASIDO 2026 - Login')

@php
    $mockPortalManual = [
        'manualId' => 'mockPortalLoginManual',
        'manualTitle' => 'Mock Portal Login Guide',
        'manualSummary' => 'Use this quick guide when signing into the mock portal or when helping users who are unsure which login route to use.',
        'manualPdf' => '/system_overview.pdf',
        'manualSteps' => [
            ['title' => 'Open the correct login page', 'body' => 'Use the mock-portal login for Headteacher, DAO, RAO, and Secretariat accounts. Main IRMS accounts must use the standard IRMS login page instead.'],
            ['title' => 'Enter your registered email', 'body' => 'Use the same email address that was approved during portal registration. If the account belongs to another role, the system will redirect or block incorrect access.'],
            ['title' => 'Use the password toggle if needed', 'body' => 'Tap the eye icon to show or hide your password before submitting, especially on mobile devices.'],
            ['title' => 'Use Forgot Password when necessary', 'body' => 'If you cannot remember your password, use the password reset flow instead of creating a second account with the same responsibility.'],
            ['title' => 'Expect role-based access', 'body' => 'After login, the system sends you to the dashboard that matches your role. Headteachers, DAOs, RAOs, and Secretariat users do not share the same workspace.'],
        ],
        'manualNotes' => [
            '<strong>Tip:</strong> A 419 error usually means the page sat too long before submit or the session expired. Refresh the login page and try again.',
            '<strong>Security:</strong> Do not share passwords between multiple officers.'
        ],
    ];
@endphp

@section('content')
@php
    $showForgotCard = old('auth_view') === 'forgot' || session('mock_portal_auth_view') === 'forgot';
    $showResetCard = isset($showResetForm);
@endphp
<div class="login-shell">
    {{-- Login Card --}}
    <div class="login-card login-card--compact" id="login-card" style="{{ ($showResetCard || $showForgotCard) ? 'display:none;' : '' }}">
        <div class="login-card-header">
            <div class="login-emblem-wrap">
                <img src="{{ asset('images/vian.png') }}" alt="System login illustration" class="login-emblem">
                <div class="login-stripes" aria-hidden="true">
                    <span style="background:#1eb53a;"></span>
                    <span style="background:#fcd116;"></span>
                    <span style="background:#000000;"></span>
                    <span style="background:#00a3dd;"></span>
                </div>
            </div>
            <h1>STANDARD VII MOCK TASIDO 2026</h1>
            <p>Registration Portal Login</p>
        </div>

        <div class="login-card-body">
            @if (session('status') && !$showForgotCard && !$showResetCard)
                <div class="status-alert" style="background:rgba(220, 252, 231, 0.9); color:#166534; padding:10px; border-radius:10px; margin-bottom:15px; text-align:center; font-size:0.85rem; border:1px solid #bbf7d0; backdrop-filter: blur(4px);">
                    <div style="display:flex; align-items:center; justify-content:center; gap:8px;">
                        <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            <form action="{{ route('mock-portal.login.submit') }}" method="POST" novalidate>
                @csrf

                @if ($errors->any() && !$showResetCard && !$showForgotCard)
                    <div class="login-error" role="alert" style="background:#fef2f2; color:#b91c1c; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.85rem;">
                        {{ $errors->first('email') ?? $errors->first() }}
                    </div>
                @endif

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/mail.png') }}" alt="">
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') is-invalid @enderror"
                            placeholder="Enter your email"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePortalPassword('password')" aria-label="Show or hide password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c7 0 10 8 10 8a17.45 17.45 0 0 1-2.16 3.19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M6.71 6.72A17.34 17.34 0 0 0 2 12s3 8 10 8a9.77 9.77 0 0 0 5.29-1.53" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-meta">
                    <span><a href="javascript:void(0)" onclick="showForgotForm()" style="color:#00a3dd; font-weight:bold;">Forgot Password?</a></span>
                </div>

                <button type="submit" class="login-button">Login to Portal</button>

                <div class="login-footer">
                    Don't have an account? <a href="{{ route('mock-portal.register') }}" style="color:#00a3dd; font-weight:bold;">Register Here</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Forgot Password Card --}}
    <div class="login-card login-card--compact" id="forgot-card" style="{{ $showForgotCard ? 'display:block;' : 'display:none;' }} padding-bottom: 20px;">
        <div class="login-card-header" style="padding-top: 15px; padding-bottom: 5px;">
            <div class="login-emblem-wrap" style="width: 80px; height: 80px; margin-bottom: 8px;">
                <img src="{{ asset('images/vian.png') }}" alt="" class="login-emblem">
            </div>
            <h1 style="font-size: 1.4rem; margin-bottom: 2px;">Forgot Password</h1>
            <p style="font-size: 0.8rem;">Enter your email to receive a reset link</p>
        </div>
        <div class="login-card-body" style="padding-top: 10px; padding-bottom: 10px;">
            @if ($errors->has('email') && $showForgotCard)
                <div class="login-error" role="alert" style="background:#fef2f2; color:#b91c1c; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.85rem;">
                    {{ $errors->first('email') }}
                </div>
            @endif

            @if (session('status') && $showForgotCard)
                <div class="status-alert" style="background:rgba(220, 252, 231, 0.9); color:#166534; padding:10px; border-radius:10px; margin-bottom:15px; text-align:center; font-size:0.85rem; border:1px solid #bbf7d0; backdrop-filter: blur(4px);">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('mock_portal_reset_link'))
                <div class="status-alert" style="background:rgba(219, 234, 254, 0.92); color:#1d4ed8; padding:10px; border-radius:10px; margin-bottom:15px; text-align:left; font-size:0.82rem; border:1px solid #93c5fd; word-break:break-all;">
                    <strong>Reset link:</strong><br>
                    <a href="{{ session('mock_portal_reset_link') }}" style="color:#1d4ed8; font-weight:600;">{{ session('mock_portal_reset_link') }}</a>
                </div>
            @endif

            <form action="{{ route('mock-portal.password.email') }}" method="POST">
                @csrf
                <input type="hidden" name="auth_view" value="forgot">
                <div class="form-group">
                    <label for="forgot_email" class="form-label">Email Address</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/mail.png') }}" alt="">
                        </span>
                        <input type="email" id="forgot_email" name="email" value="{{ old('auth_view') === 'forgot' ? old('email') : '' }}" class="form-input" placeholder="Enter your registered email" required oninput="validateForgotEmail()">
                    </div>
                </div>
                <button type="submit" id="forgot-submit" class="login-button" {{ (old('auth_view') === 'forgot' && filter_var(old('email'), FILTER_VALIDATE_EMAIL)) ? '' : 'disabled' }}>Send Reset Link</button>
                <div class="login-footer">
                    Remembered? <a href="javascript:void(0)" onclick="showLoginForm()" style="color:#00a3dd; font-weight:bold;">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset Password Card --}}
    @if ($showResetCard)
    <div class="login-card login-card--compact" id="reset-card">
        <div class="login-card-header">
            <div class="login-emblem-wrap">
                <img src="{{ asset('images/vian.png') }}" alt="" class="login-emblem">
            </div>
            <h1>Reset Password</h1>
            <p>Create a new secure password</p>
        </div>
        <div class="login-card-body">
            <form action="{{ route('mock-portal.password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                @if ($errors->any())
                    <div class="login-error" role="alert" style="background:#fef2f2; color:#b91c1c; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.85rem;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="form-group">
                    <label for="reset_password" class="form-label">New Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input type="password" id="reset_password" name="password" class="form-input" placeholder="Min. 6 characters" required>
                        <button type="button" class="password-toggle" onclick="togglePortalPassword('reset_password')" aria-label="Show or hide password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c7 0 10 8 10 8a17.45 17.45 0 0 1-2.16 3.19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M6.71 6.72A17.34 17.34 0 0 0 2 12s3 8 10 8a9.77 9.77 0 0 0 5.29-1.53" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reset_password_confirmation" class="form-label">Confirm Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input type="password" id="reset_password_confirmation" name="password_confirmation" class="form-input" placeholder="Repeat new password" required>
                        <button type="button" class="password-toggle" onclick="togglePortalPassword('reset_password_confirmation')" aria-label="Show or hide password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c7 0 10 8 10 8a17.45 17.45 0 0 1-2.16 3.19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M6.71 6.72A17.34 17.34 0 0 0 2 12s3 8 10 8a9.77 9.77 0 0 0 5.29-1.53" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-button">Save Password</button>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
    function togglePortalPassword(id) {
        const input = document.getElementById(id);
        const btn = input.nextElementSibling;
        if (!input || !btn) {
            return;
        }

        if (input.type === 'password') {
            input.type = 'text';
            btn.classList.add('is-visible');
        } else {
            input.type = 'password';
            btn.classList.remove('is-visible');
        }
    }

    function showForgotForm() {
        document.getElementById('login-card').style.display = 'none';
        document.getElementById('forgot-card').style.display = 'block';
    }

    function showLoginForm() {
        const forgotEmail = document.getElementById('forgot_email');
        const forgotSubmit = document.getElementById('forgot-submit');
        document.getElementById('forgot-card').style.display = 'none';
        document.getElementById('login-card').style.display = 'block';
        if (forgotEmail) {
            forgotEmail.value = '';
        }
        if (forgotSubmit) {
            forgotSubmit.disabled = true;
        }
    }

    function validateForgotEmail() {
        const email = document.getElementById('forgot_email').value;
        const btn = document.getElementById('forgot-submit');
        // Simple regex for email validation
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        btn.disabled = !isValid;
    }
</script>
<style>
    .login-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #94a3b8 !important;
        box-shadow: none !important;
        transform: none !important;
    }
</style>
@endsection
