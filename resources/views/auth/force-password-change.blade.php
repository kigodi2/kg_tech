@extends('layouts.auth-rms')

@section('title', 'Password Change Required')

@section('content')
<div class="login-shell">
    <div class="login-card">
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
            <h1>Password Change Required</h1>
            <p>You must change your password on first login for security.</p>
        </div>

        <div class="login-card-body">
            <form action="{{ route('password.update-required') }}" method="POST" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="login-error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            required
                            class="form-input @error('current_password') is-invalid @enderror"
                            placeholder="Current Password"
                            autocomplete="current-password"
                        />
                    </div>
                    @error('current_password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="New Password"
                            autocomplete="new-password"
                        />
                    </div>
                    <small style="display:block; margin-top:6px; color:#64748b; font-size:0.78rem;">Minimum 12 characters</small>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            class="form-input"
                            placeholder="Confirm Password"
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <button type="submit" class="login-button">Change Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
