@extends('layouts.auth-rms')

@section('title', 'Mock TASIDO 2026 - Registration Closed')

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
            <h1>STANDARD VII MOCK TASIDO 2026</h1>
            <p>Registration Window Closed</p>
        </div>

        <div class="login-card-body">
            <div class="login-error" role="alert" style="background:#fef2f2; color:#b91c1c; padding:12px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;">
                The mock registration period closed on {{ $registrationDeadline }}. Candidate registration changes are no longer accepted.
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('mock-portal.login') }}" class="btn btn-primary" style="text-decoration:none;">Login</a>
                <a href="{{ route('mock-portal.welcome') }}" class="btn btn-outline" style="text-decoration:none;">Portal Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
