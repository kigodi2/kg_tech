@extends('layouts.auth-rms')

@section('title', 'IRMS Login')

@section('content')
<div class="login-shell">
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
    .login-button-secondary {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 12px;
        text-decoration: none;
        background: #111827;
        color: #ffffff;
    }

    .login-button-secondary:hover {
        color: #ffffff;
        text-decoration: none;
        background: #0f172a;
    }

    .login-button-secondary.disabled-btn {
        opacity: 0.45;
        cursor: not-allowed;
        background: #1f2937 !important;
        color: #9ca3af !important;
    }
    .github-restricted-note {
        color: #fca5a5;
        font-size: 0.75rem;
        margin-top: 6px;
        display: block;
        text-align: center;
        transition: opacity 0.2s ease;
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
