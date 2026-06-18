@extends('layouts.auth-rms')

@section('title', 'Standard VII Mock TASIDO 2026 - Registration Portal')

@php
    $mockPortalManual = [
        'manualId' => 'mockPortalWelcomeManual',
        'manualTitle' => 'Mock Portal User Manual',
        'manualSummary' => 'This guide helps schools and mock-portal users start correctly, choose the right account path, and avoid registration mistakes.',
        'manualPdf' => '/system_overview.pdf',
        'manualSteps' => [
            ['title' => 'Choose the correct entry point', 'body' => 'Use Login if you already have a mock-portal account, Register if you need a new one, and Zonal Secretariat only if you are authorized for secretariat access.'],
            ['title' => 'Register with the correct role', 'body' => 'Select the exact portal role that matches your responsibility: Headteacher, DAO, RAO, or Secretariat. Wrong role selection can block access to the right dashboard.'],
            ['title' => 'Use the right school, district, or region details', 'body' => 'During registration, ensure the selected school, district, and region belong to your actual area of responsibility before submitting the form.'],
            ['title' => 'Keep your login details safe', 'body' => 'After registration, use the same email and password for future access. If you forget your password, use the password reset option instead of creating duplicate accounts.'],
            ['title' => 'Follow your dashboard workflow', 'body' => 'After login, each role gets a different dashboard. Headteachers manage pupils, DAOs manage schools and district issues, RAOs review regional data, and Secretariat users monitor the wider system.'],
        ],
        'manualNotes' => [
            '<strong>Important:</strong> Use only one valid account per role and school responsibility to avoid confusion and duplicate records.',
            '<strong>Download option:</strong> Use the PDF guide for offline sharing with schools and district officers.'
        ],
    ];
@endphp

@section('content')
<style>
.welcome-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: clamp(24px, 4vw, 40px) 20px;
    background: linear-gradient(160deg, #060d12 0%, #0d1b2a 50%, #060d12 100%);
    position: relative;
    overflow: hidden;
}

/* Subtle animated glow */
.welcome-shell::before {
    content: '';
    position: absolute;
    top: -200px;
    left: 50%;
    transform: translateX(-50%);
    width: 700px;
    height: 700px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,163,221,0.07) 0%, transparent 65%);
    animation: pulse 6s ease-in-out infinite;
    pointer-events: none;
}
@keyframes pulse {
    0%, 100% { transform: translateX(-50%) scale(1); opacity: 1; }
    50% { transform: translateX(-50%) scale(1.1); opacity: 0.7; }
}

/* Flag stripe */
.flag-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    display: flex;
    z-index: 100;
}
.flag-bar span { display: block; flex: 1; }

/* Card */
.welcome-card {
    background: rgba(16, 21, 24, 0.92);
    border: 1px solid rgba(187,164,94,0.18);
    border-radius: 24px;
    width: 100%;
    max-width: min(480px, 100%);
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.6);
    backdrop-filter: blur(20px);
}

/* Header */
.welcome-header {
    padding: clamp(24px, 4vw, 36px) clamp(20px, 5vw, 36px) clamp(22px, 3.5vw, 28px);
    text-align: center;
    border-bottom: 1px solid rgba(187,164,94,0.1);
    background: linear-gradient(180deg, rgba(0,163,221,0.05), transparent);
}
.welcome-emblem {
    width: clamp(58px, 16vw, 72px);
    height: clamp(58px, 16vw, 72px);
    border-radius: 18px;
    background: linear-gradient(135deg, #00A3DD, #006fa3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: clamp(1.5rem, 5vw, 2rem);
    color: #fff;
    margin: 0 auto 18px;
    box-shadow: 0 8px 24px rgba(0,163,221,0.3);
}
.welcome-eyebrow {
    font-size: clamp(0.62rem, 2vw, 0.68rem);
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #BBA45E;
    margin-bottom: 8px;
}
.welcome-title {
    font-size: clamp(1.05rem, 4.6vw, 1.3rem);
    font-weight: 900;
    color: #f0e6c8;
    line-height: 1.25;
    margin: 0 0 6px;
}
.welcome-subtitle {
    font-size: clamp(0.78rem, 2.8vw, 0.84rem);
    color: rgba(255,255,255,0.45);
    line-height: 1.5;
    margin: 0;
}

/* Action buttons */
.welcome-body {
    padding: clamp(20px, 4vw, 32px) clamp(20px, 5vw, 36px) clamp(24px, 4vw, 36px);
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.portal-btn {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: clamp(14px, 3.2vw, 18px) clamp(16px, 4vw, 22px);
    border-radius: 14px;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid transparent;
    cursor: pointer;
}
.portal-btn-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.portal-btn-text { flex: 1; }
.portal-btn-label {
    font-size: clamp(0.96rem, 3.2vw, 1rem);
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 2px;
}
.portal-btn-desc {
    font-size: clamp(0.72rem, 2.7vw, 0.76rem);
    line-height: 1.4;
    opacity: 0.65;
}
.portal-btn-arrow {
    font-size: 0.9rem;
    opacity: 0.4;
    transition: all 0.2s;
}

/* Login button — blue */
.btn-login {
    background: linear-gradient(135deg, rgba(0,163,221,0.14), rgba(0,111,163,0.08));
    border-color: rgba(0,163,221,0.25);
    color: #f0f4f7;
}
.btn-login:hover {
    background: linear-gradient(135deg, rgba(0,163,221,0.22), rgba(0,111,163,0.14));
    border-color: rgba(0,163,221,0.45);
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(0,163,221,0.15);
}
.btn-login .portal-btn-icon { background: rgba(0,163,221,0.15); color: #67d8ff; }
.btn-login:hover .portal-btn-arrow { opacity: 0.9; color: #67d8ff; transform: translateX(3px); }

/* Register button — gold */
.btn-register {
    background: linear-gradient(135deg, rgba(187,164,94,0.1), rgba(187,164,94,0.04));
    border-color: rgba(187,164,94,0.2);
    color: #f0f4f7;
}
.btn-register:hover {
    background: linear-gradient(135deg, rgba(187,164,94,0.18), rgba(187,164,94,0.08));
    border-color: rgba(187,164,94,0.4);
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(187,164,94,0.1);
}
.btn-register .portal-btn-icon { background: rgba(187,164,94,0.12); color: #BBA45E; }
.btn-register:hover .portal-btn-arrow { opacity: 0.9; color: #BBA45E; transform: translateX(3px); }

/* Zonal button - secretariat */
.btn-zonal {
    background: linear-gradient(135deg, rgba(30,181,58,0.14), rgba(0,163,221,0.08));
    border-color: rgba(30,181,58,0.3);
    color: #f0f4f7;
}
.btn-zonal:hover {
    background: linear-gradient(135deg, rgba(30,181,58,0.22), rgba(0,163,221,0.14));
    border-color: rgba(30,181,58,0.45);
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(30,181,58,0.14);
}
.btn-zonal .portal-btn-icon { background: rgba(30,181,58,0.15); color: #6ae086; }
.btn-zonal:hover .portal-btn-arrow { opacity: 0.9; color: #6ae086; transform: translateX(3px); }

/* Divider */
.welcome-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.2);
}
.welcome-divider::before, .welcome-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.06);
}

/* Footer */
.welcome-footer {
    padding: 16px clamp(20px, 5vw, 36px);
    border-top: 1px solid rgba(255,255,255,0.04);
    text-align: center;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.25);
    background: rgba(0,0,0,0.2);
}

@media (max-width: 767px) {
    .welcome-shell {
        min-height: calc(100vh - 58px);
        justify-content: flex-start;
    }
}

@media (min-width: 768px) and (max-width: 1024px) {
    .welcome-card {
        max-width: 560px;
    }
}

@media (max-width: 767px) {
    .welcome-shell::before {
        width: 520px;
        height: 520px;
        top: -140px;
    }

    .welcome-card {
        border-radius: 20px;
    }

    .portal-btn {
        gap: 12px;
    }

    .portal-btn-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        font-size: 1rem;
    }

    .welcome-divider {
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .welcome-shell {
        padding-inline: 12px;
    }

    .welcome-card {
        border-radius: 16px;
    }

    .welcome-header,
    .welcome-body,
    .welcome-footer {
        padding-left: 16px;
        padding-right: 16px;
    }

    .welcome-emblem {
        margin-bottom: 14px;
    }

    .portal-btn {
        align-items: flex-start;
    }

    .portal-btn-text {
        min-width: 0;
    }

    .portal-btn-arrow {
        align-self: center;
    }
}

@media (max-width: 360px) {
    .welcome-eyebrow {
        letter-spacing: 0.08em;
    }

    .portal-btn {
        padding: 13px 14px;
    }

    .portal-btn-icon {
        width: 36px;
        height: 36px;
    }
}
</style>

{{-- Tanzania flag stripe --}}
<div class="flag-bar" aria-hidden="true">
    <span style="background:#1EB53A;"></span>
    <span style="background:#FCD116;"></span>
    <span style="background:#000000;"></span>
    <span style="background:#00A3DD;"></span>
</div>

<div class="welcome-shell">
    <div class="welcome-card">

        {{-- Header --}}
        <div class="welcome-header">
            <div class="welcome-emblem">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="welcome-eyebrow">Standard VII Mock Examination — TASIDO 2026</div>
            <h1 class="welcome-title">REGISTRATION PORTAL</h1>
            <p class="welcome-subtitle">Official online registration system for DAOs and Headteachers</p>
        </div>

        {{-- Action Buttons --}}
        <div class="welcome-body">
            @auth
                <div style="text-align:center; margin-bottom: 20px;">
                    <div class="welcome-eyebrow">Logged in as Administrator</div>
                    <div style="color:rgba(255,255,255,0.4); font-size: 0.8rem;">You can preview the portal dashboards below:</div>
                </div>

                <a href="{{ route('mock-portal.rao.dashboard') }}" class="portal-btn" style="background: linear-gradient(135deg, rgba(30,181,58,0.14), rgba(30,181,58,0.08)); border-color: rgba(30,181,58,0.25); color: #f0f4f7; margin-bottom: 12px; display: flex; align-items: center; padding: 16px 20px; border-radius: 14px; border: 1px solid; text-decoration: none; transition: all 0.2s;">
                    <div class="portal-btn-icon" style="background: rgba(30,181,58,0.15); color: #6ae086; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-right: 16px;">
                        <i class="fas fa-city"></i>
                    </div>
                    <div class="portal-btn-text" style="flex: 1;">
                        <div class="portal-btn-label" style="font-size: 0.9rem; font-weight: 700; color: #fff;">RAO Dashboard</div>
                        <div class="portal-btn-desc" style="font-size: 0.72rem; color: rgba(255,255,255,0.4);">View the Regional Academic Officer interface</div>
                    </div>
                    <i class="fas fa-chevron-right portal-btn-arrow" style="font-size: 0.8rem; color: rgba(255,255,255,0.2);"></i>
                </a>

                <a href="{{ route('mock-portal.secretariat.dashboard') }}" class="portal-btn btn-zonal">
                    <div class="portal-btn-icon">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div class="portal-btn-text">
                        <div class="portal-btn-label">Zonal Control Centre</div>
                        <div class="portal-btn-desc">Open the zonal secretariat workspace for regions, districts, schools and registration status</div>
                    </div>
                    <i class="fas fa-chevron-right portal-btn-arrow"></i>
                </a>

                <a href="{{ route('mock-portal.dao.dashboard') }}" class="portal-btn btn-login">
                    <div class="portal-btn-icon">
                        <i class="fas fa-gauge-high"></i>
                    </div>
                    <div class="portal-btn-text">
                        <div class="portal-btn-label">DAO Dashboard</div>
                        <div class="portal-btn-desc">View the District Academic Officer interface</div>
                    </div>
                    <i class="fas fa-chevron-right portal-btn-arrow"></i>
                </a>

                <a href="{{ route('mock-portal.school.dashboard') }}" class="portal-btn btn-register">
                    <div class="portal-btn-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="portal-btn-text">
                        <div class="portal-btn-label">School Dashboard</div>
                        <div class="portal-btn-desc">View the Headteacher registration interface</div>
                    </div>
                    <i class="fas fa-chevron-right portal-btn-arrow"></i>
                </a>
            @else
                <a href="{{ route('mock-portal.login') }}" class="portal-btn btn-login" id="btn-portal-login">
                    <div class="portal-btn-icon">
                        <i class="fas fa-right-to-bracket"></i>
                    </div>
                    <div class="portal-btn-text">
                        <div class="portal-btn-label">Login to Portal</div>
                        <div class="portal-btn-desc">Already have an account? Sign in here</div>
                    </div>
                    <i class="fas fa-chevron-right portal-btn-arrow"></i>
                </a>

                <div class="welcome-divider">or</div>

                <a href="{{ route('mock-portal.register') }}" class="portal-btn btn-register" id="btn-portal-register">
                    <div class="portal-btn-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="portal-btn-text">
                        <div class="portal-btn-label">Create Account</div>
                        <div class="portal-btn-desc">New user? Register as Zonal Secretariat, RAO, DAO or Headteacher</div>
                    </div>
                    <i class="fas fa-chevron-right portal-btn-arrow"></i>
                </a>
            @endauth
        </div>

        {{-- Footer note --}}
        <div class="welcome-footer">
            For assistance, contact your District Academic Officer (DAO)
        </div>

    </div>
</div>
@endsection
