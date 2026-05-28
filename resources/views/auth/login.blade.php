@extends('layouts.auth-rms')

@section('title', 'IRMS Login')

@section('content')
<div class="login-shell">
    {{-- Login Card --}}
    <div class="login-card login-card--compact login-3d-card">
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

                <button type="submit" class="login-button login-primary-button">Login</button>

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

    {{-- Radial Backlight Glow Layer (placed after card for sibling selector styling) --}}
    <div class="login-backlight" aria-hidden="true"></div>
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

    /* Backlight Radial Glow - inherits tone from Import Pupils card */
    .login-backlight {
        position: absolute;
        width: 460px;
        height: 460px;
        background: radial-gradient(circle, rgba(219, 234, 254, 0.18) 0%, rgba(191, 219, 254, 0.06) 45%, transparent 70%) !important;
        filter: blur(50px);
        pointer-events: none;
        z-index: 0;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                    opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                    filter 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Redesigned Card - Premium elevated 3D panel with bezel top highlights and contact shadows */
    .login-card {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 380px;
        background: linear-gradient(145deg, rgba(15, 31, 45, 0.98), rgba(8, 22, 35, 0.98)) !important;
        border: 2px solid rgba(56, 189, 248, 0.45) !important;
        box-shadow:
            0 2px 0 rgba(255, 255, 255, 0.08) inset,
            0 -10px 22px rgba(0, 0, 0, 0.38) inset,
            0 10px 18px rgba(0, 0, 0, 0.55),
            0 24px 55px rgba(0, 0, 0, 0.65),
            0 0 32px rgba(14, 165, 233, 0.2),
            0 0 70px rgba(14, 165, 233, 0.1) !important;
        border-radius: 16px !important;
        color: #f0f4f7 !important;
        font-family: 'Maiandra GD', sans-serif !important;
        overflow: visible !important; /* Allow pseudo contact shadows to overflow */
        display: flex;
        flex-direction: column;
        transform: perspective(1200px) translateZ(0);
        transition: transform 220ms cubic-bezier(0.4, 0, 0.2, 1),
                    border-color 220ms cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 220ms cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Premium 3D illuminated panel overrides */
    .login-3d-card {
        position: relative !important;
        isolation: isolate !important;
        width: 100%;
        max-width: 430px !important;
        padding: 42px 44px !important;
        border-radius: 22px !important;
        border: 2px solid rgba(56, 189, 248, 0.58) !important;
        background: linear-gradient(145deg, rgba(14, 37, 54, 0.98), rgba(6, 19, 30, 0.98)) !important;
        box-shadow:
            inset 0 2px 0 rgba(255, 255, 255, 0.09),
            inset 0 -14px 28px rgba(0, 0, 0, 0.42),
            0 10px 22px rgba(0, 0, 0, 0.58),
            0 28px 70px rgba(0, 0, 0, 0.72),
            0 0 48px rgba(14, 165, 233, 0.34),
            0 0 110px rgba(14, 165, 233, 0.18) !important;
        color: #f0f4f7 !important;
        font-family: 'Maiandra GD', sans-serif !important;
        overflow: visible !important;
        display: flex;
        flex-direction: column;
        transform: perspective(1200px) translateZ(0);
        transition:
            transform 220ms ease,
            box-shadow 220ms ease,
            border-color 220ms ease !important;
    }

    .login-3d-card .login-card-header {
        padding: 0 0 16px 0 !important;
    }

    .login-3d-card .login-card-body {
        padding: 0 !important;
    }

    /* Premium backlight glow behind the 3D card */
    .login-3d-card::before {
        content: "" !important;
        position: absolute !important;
        inset: -34px !important;
        z-index: -1 !important;
        border-radius: 32px !important;
        background:
            radial-gradient(circle at 50% 20%, rgba(14, 165, 233, 0.45), transparent 48%),
            radial-gradient(circle at 50% 95%, rgba(6, 182, 212, 0.30), transparent 58%) !important;
        filter: blur(24px) !important;
        opacity: 0.92 !important;
        pointer-events: none !important;
        transition: all 220ms ease !important;
    }

    /* Soft physical 3D contact bottom thickness shadow */
    .login-3d-card::after {
        content: "" !important;
        position: absolute !important;
        left: 26px !important;
        right: 26px !important;
        bottom: -20px !important;
        height: 34px !important;
        z-index: -2 !important;
        border-radius: 999px !important;
        background: rgba(0, 0, 0, 0.72) !important;
        filter: blur(18px) !important;
        opacity: 0.85 !important;
        pointer-events: none !important;
        transition: all 220ms ease !important;
    }

    /* Premium hover and focus-within growth / glow intensification */
    .login-3d-card:hover,
    .login-3d-card:focus-within {
        transform: perspective(1200px) translateY(-4px) scale(1.015) !important;
        border-color: rgba(56, 189, 248, 0.82) !important;
        box-shadow:
            inset 0 2px 0 rgba(255, 255, 255, 0.12),
            inset 0 -16px 32px rgba(0, 0, 0, 0.48),
            0 14px 26px rgba(0, 0, 0, 0.64),
            0 36px 85px rgba(0, 0, 0, 0.78),
            0 0 62px rgba(14, 165, 233, 0.45),
            0 0 135px rgba(14, 165, 233, 0.25) !important;
    }

    /* Sibling backlight response on hover/focus-within */
    .login-3d-card:hover ~ .login-backlight,
    .login-3d-card:focus-within ~ .login-backlight {
        opacity: 1.35 !important;
        transform: translate(-50%, -50%) scale(1.08) !important;
        filter: blur(38px) !important;
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
        border: 3px solid rgba(56, 189, 248, 0.3) !important;
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

    /* Glassmorphic Form Inputs - Sleek, solid background with inset shadows */
    .form-input {
        width: 100%;
        height: 42px !important;
        padding: 9px 40px 9px 38px !important;
        background: rgba(16, 28, 41, 0.85) !important;
        border: 1px solid rgba(56, 189, 248, 0.3) !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4) !important;
        color: #f0f4f7 !important;
        border-radius: 8px !important;
        font-size: 0.85rem !important;
        font-family: inherit;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .form-input:focus {
        outline: none !important;
        border-color: #38bdf8 !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 0 0 3px rgba(56, 189, 248, 0.25) !important;
    }

    .form-input.is-invalid {
        border-color: rgba(239, 68, 68, 0.45) !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }

    /* Recessed premium bright light inputs with slate-900 contrast text */
    .login-3d-card .form-input {
        border-radius: 10px !important;
        background: #eaf4ff !important;
        box-shadow:
            inset 0 2px 4px rgba(0, 0, 0, 0.16),
            0 1px 0 rgba(255, 255, 255, 0.18) !important;
        border: 1px solid rgba(186, 230, 253, 0.7) !important;
        color: #0f172a !important; /* Deep slate for legibility */
    }

    .login-3d-card .form-input:focus {
        outline: none !important;
        border-color: rgba(56, 189, 248, 0.95) !important;
        box-shadow:
            0 0 0 3px rgba(14, 165, 233, 0.22),
            inset 0 2px 4px rgba(0, 0, 0, 0.16) !important;
    }

    .login-3d-card .form-input.is-invalid {
        border-color: rgba(239, 68, 68, 0.75) !important;
        box-shadow:
            0 0 0 3px rgba(239, 68, 68, 0.22),
            inset 0 2px 4px rgba(0, 0, 0, 0.16) !important;
    }

    /* Placeholder and icon visibility overrides */
    .login-3d-card .form-input::placeholder {
        color: #64748b !important;
        opacity: 0.8 !important;
    }

    .login-3d-card .field-icon img {
        filter: brightness(0) invert(0.15) !important;
        opacity: 0.72 !important;
    }

    .login-3d-card .password-toggle svg {
        color: #64748b !important;
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

    /* Buttons Override - Beveled 3D Button with active press states */
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
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.15) inset,
            0 -3px 0 rgba(0, 0, 0, 0.25) inset,
            0 6px 16px rgba(0, 163, 221, 0.25) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .login-button:hover {
        background: linear-gradient(135deg, #00b4f0, #0081be) !important;
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.2) inset,
            0 -3px 0 rgba(0, 0, 0, 0.25) inset,
            0 8px 20px rgba(0, 163, 221, 0.35) !important;
        transform: translateY(-1px) !important;
    }

    .login-button:active {
        transform: translateY(1px) scale(0.985) !important;
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.05) inset,
            0 -1px 0 rgba(0, 0, 0, 0.15) inset,
            0 2px 6px rgba(0, 163, 221, 0.15) !important;
    }

    /* Tactile 3D Action button */
    .login-primary-button {
        background: linear-gradient(180deg, #16b7e8 0%, #0787bd 100%) !important;
        border-radius: 10px !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.22),
            0 7px 0 rgba(3, 105, 161, 0.45),
            0 16px 30px rgba(14, 165, 233, 0.24) !important;
        transition:
            transform 180ms ease,
            box-shadow 180ms ease,
            filter 180ms ease !important;
    }

    .login-primary-button:hover {
        transform: translateY(-2px) !important;
        filter: brightness(1.06) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.28),
            0 9px 0 rgba(3, 105, 161, 0.48),
            0 22px 38px rgba(14, 165, 233, 0.32) !important;
    }

    .login-primary-button:active {
        transform: translateY(3px) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.18),
            0 3px 0 rgba(3, 105, 161, 0.45),
            0 10px 22px rgba(14, 165, 233, 0.20) !important;
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

    /* Mobile safety query */
    @media (max-width: 640px) {
        .login-3d-card {
            max-width: calc(100vw - 32px) !important;
            padding: 34px 24px !important;
            border-radius: 20px !important;
        }

        .login-3d-card .login-card-header {
            padding: 0 0 12px 0 !important;
        }

        .login-3d-card::before {
            inset: -20px !important;
            filter: blur(18px) !important;
        }

        .login-3d-card:hover,
        .login-3d-card:focus-within {
            transform: none !important;
        }
    }

    /* Reduced motion safety */
    @media (prefers-reduced-motion: reduce) {
        .login-3d-card,
        .login-primary-button {
            transition: none !important;
        }

        .login-3d-card:hover,
        .login-3d-card:focus-within,
        .login-primary-button:hover,
        .login-primary-button:active {
            transform: none !important;
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
