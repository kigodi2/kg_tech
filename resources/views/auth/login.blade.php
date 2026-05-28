@extends('layouts.auth-rms')

@section('title', 'IRMS Login')

@section('content')
<div class="login-shell">
    {{-- Radial Backlight Glow Layer --}}
    <div class="login-backlight" aria-hidden="true"></div>

    {{-- Login Card --}}
    <div class="login-card login-card--compact">
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
            <h1>Login</h1>
            <p>Provide your email and password to login</p>
        </div>

        <div class="login-card-body">
            <form action="{{ route('login') }}" method="POST" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="login-error" role="alert">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true" style="margin-right: 5px;"></i>
                        <span>{{ $errors->first('email') ?? $errors->first() }}</span>
                    </div>
                @endif

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
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
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
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
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Show or hide password">
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
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="login-meta">
                    <span>Forgot Password? Contact Administrator</span>
                </div>

                <button type="submit" class="login-button">Login</button>

                @if (config('services.github.client_id') && config('services.github.client_secret'))
                    <a href="{{ route('auth.github.redirect') }}" id="github-login-btn" class="login-button login-button-secondary">
                        <i class="fab fa-github" aria-hidden="true" style="margin-right: 8px;"></i>
                        <span>Continue with GitHub</span>
                    </a>
                    <span id="github-restricted-helper" class="github-restricted-note">GitHub login is restricted to the system administrator.</span>
                @endif

                <div class="login-footer">
                    Need access? <strong>Contact the system administrator</strong>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    :root {
        --tz-green: #1EB53A;
        --tz-yellow: #FCD116;
        --tz-blue: #00A3DD;
        --tz-text: #f0f4f7;
        --tz-muted: rgba(255, 255, 255, .45);
    }

    /* Centered Shell with backlight container */
    .login-shell {
        position: relative;
        flex: 1 0 auto;
        min-height: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(24px, 4vw, 40px) clamp(16px, 3vw, 24px);
        overflow: hidden;
    }

    /* Backlight Radial Glow */
    .login-backlight {
        position: absolute;
        width: 460px;
        height: 460px;
        background: radial-gradient(circle, rgba(0, 163, 221, 0.14) 0%, rgba(30, 181, 58, 0.04) 52%, transparent 70%);
        filter: blur(50px);
        pointer-events: none;
        z-index: 0;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        border-radius: 50%;
    }

    /* Redesigned Card */
    .login-card {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 380px;
        background: linear-gradient(135deg, #0d1b2a, #111e29) !important;
        border: 1px solid rgba(0, 163, 221, 0.2) !important;
        box-shadow: 0 24px 64px rgba(5, 10, 15, 0.5) !important;
        border-radius: 16px !important;
        color: #f0f4f7 !important;
        font-family: 'Maiandra GD', sans-serif !important;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.24s ease, border-color 0.24s ease;
    }

    .login-card::before {
        display: none !important;
    }

    .login-card-header {
        position: relative;
        padding: 24px 24px 8px;
        text-align: center;
    }

    .login-emblem-wrap {
        position: relative;
        width: 96px;
        height: 96px;
        margin: 0 auto 12px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid rgba(0, 163, 221, 0.25) !important;
        background: linear-gradient(135deg, #080f15, #111e29) !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3) !important;
    }

    .login-emblem {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .login-stripes {
        position: absolute;
        left: 50%;
        bottom: -7px;
        transform: translateX(-50%);
        display: flex;
        gap: 3px;
    }

    .login-stripes span {
        display: block;
        width: 11px;
        height: 4px;
        border-radius: 999px;
    }

    .login-card-header h1 {
        margin: 0 0 4px;
        font-size: 1.5rem !important;
        font-weight: 900 !important;
        color: #f0e6c8 !important;
        letter-spacing: -0.5px !important;
    }

    .login-card-header p {
        margin: 0;
        color: var(--tz-muted) !important;
        font-size: 0.8rem !important;
        font-family: inherit;
    }

    /* Card Body & Form Groups */
    .login-card-body {
        padding: 16px 24px 24px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        margin-bottom: 6px !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        color: #f0e6c8 !important;
    }

    .field-wrap {
        position: relative;
    }

    .field-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        color: rgba(255, 255, 255, 0.35);
    }

    .field-icon img {
        width: 16px;
        height: 16px;
        object-fit: contain;
        display: block;
        filter: brightness(0) invert(0.95) !important;
        opacity: 0.65 !important;
    }

    /* Glassmorphic Form Inputs */
    .form-input {
        width: 100%;
        height: 42px !important;
        padding: 9px 40px 9px 38px !important;
        background: rgba(8, 15, 21, 0.6) !important;
        border: 1px solid rgba(0, 163, 221, 0.2) !important;
        color: #f0f4f7 !important;
        border-radius: 8px !important;
        font-size: 0.85rem !important;
        font-family: inherit;
        transition: all 0.2s ease !important;
    }

    .form-input:focus {
        outline: none !important;
        border-color: #00A3DD !important;
        box-shadow: 0 0 0 3px rgba(0, 163, 221, 0.15) !important;
    }

    .form-input.is-invalid {
        border-color: rgba(239, 68, 68, 0.4) !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: rgba(255, 255, 255, 0.35) !important;
        font-size: 15px;
        padding: 4px 6px;
        cursor: pointer;
    }

    .password-toggle svg {
        width: 16px;
        height: 16px;
        display: block;
        color: currentColor;
    }

    .password-toggle .icon-eye-off {
        display: none;
    }

    .password-toggle.is-visible .icon-eye {
        display: none;
    }

    .password-toggle.is-visible .icon-eye-off {
        display: block;
    }

    .login-meta {
        display: flex;
        justify-content: flex-end;
        margin: 0 0 16px;
        font-size: 0.76rem;
    }

    .login-meta span {
        color: var(--tz-blue) !important;
        font-weight: 700 !important;
        cursor: default;
    }

    /* Buttons Override */
    .login-button {
        width: 100%;
        height: 42px;
        border: 0;
        border-radius: 8px !important;
        padding: 10px 16px;
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        font-family: inherit;
        color: #fff !important;
        background: linear-gradient(135deg, #00A3DD, #006fa3) !important;
        box-shadow: 0 8px 24px rgba(0, 163, 221, 0.2) !important;
        transition: all 0.2s ease !important;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .login-button:hover {
        background: linear-gradient(135deg, #00b4f0, #0081be) !important;
        box-shadow: 0 10px 28px rgba(0, 163, 221, 0.3) !important;
        transform: translateY(-1px) !important;
    }

    .login-button-secondary {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #f0f4f7 !important;
        box-shadow: none !important;
        margin-top: 12px !important;
        text-decoration: none !important;
    }

    .login-button-secondary:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        transform: translateY(-1px) !important;
    }

    .login-button-secondary.disabled-btn {
        opacity: 0.45 !important;
        cursor: not-allowed !important;
        background: rgba(255, 255, 255, 0.01) !important;
        color: rgba(255, 255, 255, 0.35) !important;
        border-color: rgba(255, 255, 255, 0.04) !important;
        transform: none !important;
        box-shadow: none !important;
    }

    .github-restricted-note {
        color: var(--tz-muted) !important;
        font-size: 0.72rem;
        margin-top: 6px;
        display: block;
        text-align: center;
    }

    .login-footer {
        margin-top: 18px;
        text-align: center;
        font-size: 0.78rem;
        color: var(--tz-muted) !important;
    }

    .login-footer strong {
        color: var(--tz-blue) !important;
        font-weight: 700;
    }

    .login-error {
        margin-bottom: 14px;
        padding: 10px 12px;
        background: rgba(239, 68, 68, 0.1) !important;
        border: 1px solid rgba(239, 68, 68, 0.25) !important;
        color: #fca5a5 !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .field-error {
        display: block;
        margin-top: 6px;
        font-size: 0.76rem;
        color: #fca5a5;
    }

    /* Backlight Scaling on Mobile */
    @media (max-width: 480px) {
        .login-backlight {
            width: 320px;
            height: 320px;
            opacity: 0.7;
        }

        .login-card {
            border-radius: 12px !important;
        }

        .login-card-header {
            padding: 20px 20px 6px;
        }

        .login-card-body {
            padding: 12px 20px 20px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.getElementById('email');
        const githubBtn = document.getElementById('github-login-btn');
        const helperText = document.getElementById('github-restricted-helper');
        
        if (!emailInput || !githubBtn) return;

        const baseHref = githubBtn.getAttribute('href');

        function updateGithubState() {
            const email = (emailInput.value || '').trim().toLowerCase();
            if (email === 'agreykigodi@gmail.com') {
                githubBtn.classList.remove('disabled-btn');
                githubBtn.setAttribute('aria-disabled', 'false');
                githubBtn.setAttribute('href', baseHref + '?email=' + encodeURIComponent(email));
                if (helperText) {
                    helperText.style.display = 'none';
                }
            } else {
                githubBtn.classList.add('disabled-btn');
                githubBtn.setAttribute('aria-disabled', 'true');
                githubBtn.setAttribute('href', 'javascript:void(0)');
                if (helperText) {
                    helperText.style.display = 'block';
                }
            }
        }

        emailInput.addEventListener('input', updateGithubState);
        emailInput.addEventListener('change', updateGithubState);

        githubBtn.addEventListener('click', function(e) {
            const email = (emailInput.value || '').trim().toLowerCase();
            if (email !== 'agreykigodi@gmail.com') {
                e.preventDefault();
            }
        });

        // Run initial check
        updateGithubState();
    });
</script>
@endsection
