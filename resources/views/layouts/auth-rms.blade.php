<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IRMS Login')</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --login-blue: #0e4c8c;
            --login-blue-dark: #0a3d71;
            --login-text: #1f2937;
            --login-muted: #6b7280;
            --login-bg: #eef3f8;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                linear-gradient(rgba(247, 250, 252, 0.72), rgba(238, 243, 248, 0.82)),
                url("{{ asset('assets/rms-login/images/bg.jpg') }}") center center / cover no-repeat fixed,
                radial-gradient(circle at top left, rgba(14, 76, 140, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(30, 181, 58, 0.14), transparent 24%),
                linear-gradient(180deg, #f7fafc 0%, var(--login-bg) 100%);
            color: var(--login-text);
        }

        .page-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .page-header-stripes,
        .page-footer-stripes {
            display: flex;
            width: 100%;
        }

        .page-header-stripes {
            height: 3px;
        }

        .page-footer-stripes {
            height: 2px;
        }

        .page-header-stripes span,
        .page-footer-stripes span {
            display: block;
            width: 25%;
        }

        .page-header-inner {
            padding: 0 18px 0 12px;
        }

        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 56px;
            padding: 7px 0;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .page-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #0e4c8c;
            text-decoration: none;
        }

        .page-brand:hover {
            text-decoration: none;
        }

        .page-brand-emblem {
            width: 40px;
            height: 40px;
            border-radius: 0;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(14, 76, 140, 0.14));
        }

        .page-brand-text {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.12em;
            line-height: 1;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .mobile-menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #4b5563;
            cursor: pointer;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .mobile-menu-toggle:hover {
            color: var(--login-blue);
            background: #f3f4f6;
        }

        .mobile-menu-icon {
            position: relative;
            width: 24px;
            height: 24px;
        }

        .mobile-menu-icon span {
            position: absolute;
            left: 2px;
            width: 20px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .mobile-menu-icon span:nth-child(1) {
            top: 7px;
        }

        .mobile-menu-icon span:nth-child(2) {
            top: 11px;
        }

        .mobile-menu-icon span:nth-child(3) {
            top: 15px;
        }

        .top-nav {
            display: none;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            align-self: center;
        }

        .nav-divider {
            width: 1px;
            height: 28px;
            background: #e5e7eb;
        }

        .nav-link,
        .mobile-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            color: #1f2937;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .nav-link {
            padding: 10px 16px;
            min-height: 38px;
        }

        .mobile-nav-link {
            margin: 4px 0;
            padding: 12px 14px;
            font-size: 14px;
        }

        .nav-link:hover,
        .nav-link.active,
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: #0e4c8c;
            color: #ffffff;
            text-decoration: none;
        }

        .nav-link.is-disabled,
        .mobile-nav-link.is-disabled,
        .disabled-nav-link {
            cursor: not-allowed !important;
            opacity: 0.72;
            filter: grayscale(1);
        }

        .nav-link.is-disabled:hover,
        .mobile-nav-link.is-disabled:hover,
        .disabled-nav-link:hover {
            background: transparent;
            color: #1f2937;
            text-decoration: none;
            transform: none;
        }

        .disabled-nav-link .nav-icon {
            opacity: 0.55;
        }

        .mobile-nav-link:hover {
            transform: scale(1.02);
        }

        .nav-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
        }

        .nav-icon img {
            width: 16px;
            height: 16px;
            object-fit: contain;
            display: block;
            filter: brightness(0) invert(0.15);
            transition: filter 0.2s ease, opacity 0.2s ease;
        }

        .nav-link:hover .nav-icon img,
        .nav-link.active .nav-icon img,
        .mobile-nav-link:hover .nav-icon img,
        .mobile-nav-link.active .nav-icon img {
            filter: brightness(0) invert(1);
        }

        .mobile-drawer {
            position: fixed;
            inset: 0;
            z-index: 60;
            pointer-events: none;
        }

        .mobile-drawer.is-open {
            pointer-events: auto;
        }

        .mobile-drawer-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .mobile-drawer.is-open .mobile-drawer-overlay {
            opacity: 1;
        }

        .mobile-drawer-panel {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 272px;
            max-width: 82vw;
            background: #ffffff;
            box-shadow: -8px 0 24px rgba(15, 23, 42, 0.18);
            transform: translateX(100%);
            transition: transform 0.25s ease;
        }

        .mobile-drawer.is-open .mobile-drawer-panel {
            transform: translateX(0);
        }

        .mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .mobile-drawer-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        .mobile-drawer-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #4b5563;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }

        .mobile-drawer-close:hover {
            color: var(--login-blue);
            background: #f3f4f6;
            transform: rotate(90deg);
        }

        .mobile-drawer-nav {
            display: flex;
            flex-direction: column;
            padding: 12px;
        }

        .mobile-nav-divider {
            margin: 8px 4px;
            border-top: 1px solid #e5e7eb;
        }

        .public-main {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
        }

        .login-shell {
            flex: 1 0 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(16px, 3vw, 28px);
        }

        .login-card {
            position: relative;
            width: 100%;
            max-width: 380px;
            min-height: min(75vh, 760px);
            border: 1px solid rgba(255, 255, 255, 0.74);
            border-radius: 24px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.62) 0%, rgba(255, 255, 255, 0.22) 18%, rgba(255, 255, 255, 0.04) 42%),
                linear-gradient(180deg, #e4edf9 0%, #d6e2f2 52%, #cedbeb 100%);
            box-shadow:
                0 46px 90px rgba(14, 76, 140, 0.2),
                0 22px 42px rgba(9, 53, 104, 0.22),
                0 8px 0 rgba(186, 200, 219, 0.72),
                inset 0 1px 0 rgba(255, 255, 255, 0.82),
                inset 0 -2px 0 rgba(152, 170, 196, 0.22);
            transform: perspective(1200px) rotateX(4deg) translateY(-6px);
            transform-origin: center top;
            overflow: hidden;
            backdrop-filter: blur(6px);
            display: flex;
            flex-direction: column;
        }

        .login-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background:
                linear-gradient(90deg, rgba(252, 209, 73, 0) 12%, rgba(252, 209, 73, 0.18) 32%, rgba(255, 227, 138, 0.28) 50%, rgba(252, 209, 73, 0.16) 68%, rgba(252, 209, 73, 0) 88%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.44), rgba(255, 255, 255, 0.06) 26%, rgba(255, 255, 255, 0) 38%),
                radial-gradient(circle at 18% 14%, rgba(255, 255, 255, 0.62), transparent 30%),
                radial-gradient(circle at 78% 18%, rgba(255, 255, 255, 0.18), transparent 20%),
                radial-gradient(circle at 82% 84%, rgba(148, 163, 184, 0.16), transparent 24%);
            pointer-events: none;
        }

        .login-card::after {
            content: "";
            position: absolute;
            left: 14px;
            right: 14px;
            bottom: -24px;
            height: 44px;
            border-radius: 999px;
            background: rgba(14, 76, 140, 0.26);
            filter: blur(20px);
            z-index: -1;
            pointer-events: none;
        }

        .login-card-header {
            position: relative;
            padding: 20px 24px 8px;
            text-align: center;
            z-index: 1;
        }

        .login-emblem-wrap {
            position: relative;
            width: clamp(84px, 18vw, 128px);
            height: clamp(84px, 18vw, 128px);
            margin: 0 auto 10px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.98);
            box-shadow:
                0 24px 38px rgba(14, 76, 140, 0.22),
                0 10px 20px rgba(9, 53, 104, 0.18),
                0 5px 0 rgba(205, 214, 228, 0.7),
                inset 0 1px 0 rgba(255, 255, 255, 0.92);
            background:
                radial-gradient(circle at 35% 22%, rgba(255, 255, 255, 0.88), rgba(255, 255, 255, 0) 36%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(240, 244, 248, 0.94) 100%);
            transform: translateZ(0);
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
            margin: 0 0 3px;
            font-size: clamp(20px, 2.5vw, 24px);
            font-weight: 700;
        }

        .login-card-header p {
            margin: 0;
            color: var(--login-muted);
            font-size: clamp(11px, 1.8vw, 13px);
        }

        .login-card-body {
            position: relative;
            padding: 14px 22px 22px;
            z-index: 1;
            flex: 1 1 auto;
        }

        .login-card-body form {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .register-card {
            min-height: 0;
            max-height: calc(100vh - 170px);
        }

        .register-card .login-card-body {
            overflow-y: auto;
            scrollbar-gutter: stable;
        }

        .register-card .login-card-body form {
            min-height: auto;
            justify-content: flex-start;
        }

        .login-card--compact {
            min-height: auto;
        }

        .login-card--compact .login-card-body form {
            min-height: auto;
            justify-content: flex-start;
        }

        .login-error {
            margin-bottom: 10px;
            padding: 9px 10px;
            border: 1px solid #f5c2c7;
            border-radius: 6px;
            background: #f8d7da;
            color: #842029;
            font-size: 12px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
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
            color: #8b95a7;
        }

        .field-icon img {
            width: 16px;
            height: 16px;
            object-fit: contain;
            display: block;
            filter: brightness(0) invert(0.56);
        }

        .form-input {
            width: 100%;
            height: 42px;
            padding: 9px 40px 9px 38px;
            border: 1px solid #cfd6df;
            border-radius: 10px;
            background: #fff;
            font-size: 13px;
            color: var(--login-text);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--login-blue);
            box-shadow: 0 0 0 3px rgba(14, 76, 140, 0.12);
        }

        .form-input.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 15px;
            padding: 4px 6px;
            cursor: pointer;
        }

        .password-toggle svg {
            width: 16px;
            height: 16px;
            display: block;
            color: #8b95a7;
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
            margin: 0 0 12px;
            font-size: 12px;
        }

        .login-meta span {
            color: var(--login-blue);
            font-weight: 600;
        }

        .login-button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(90deg, var(--login-blue) 0%, var(--login-blue-dark) 100%);
            box-shadow: 0 14px 30px rgba(14, 76, 140, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(14, 76, 140, 0.26);
        }

        .login-footer {
            margin-top: 12px;
            text-align: center;
            font-size: 12px;
            color: var(--login-muted);
        }

        .login-footer strong {
            color: var(--login-blue);
            font-weight: 700;
        }

        .field-error {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #b42318;
        }

        .page-footer {
            flex-shrink: 0;
            margin-top: auto;
            background: #0e4c8c;
            color: #ffffff;
        }

        .page-footer-body {
            width: 100%;
            padding: 6px 16px 8px;
        }

        .page-footer-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .page-footer-meta,
        .page-footer-copy {
            font-size: 10px;
            line-height: 1.25;
        }

        .page-footer-meta {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            text-align: right;
            color: rgba(255, 255, 255, 0.82);
        }

        .page-footer-meta strong {
            font-weight: 700;
            color: #fcd116;
        }

        .footer-brand {
            background: linear-gradient(90deg, #f9d769 0%, #ffd35f 40%, #e8b822 100%);
            -webkit-background-clip: text;
            color: transparent;
            font-weight: 800;
            font-size: 0.72rem;
            text-shadow: 0 0 6px rgba(255, 210, 80, 0.9), 0 0 16px rgba(255, 210, 80, 0.35);
            transition: transform 0.24s ease, text-shadow 0.24s ease;
        }

        .footer-brand:hover {
            transform: translateY(-1px);
            text-shadow: 0 0 8px rgba(255, 210, 80, 0.95), 0 0 18px rgba(255, 210, 80, 0.5);
        }

        .page-footer-copy {
            max-width: 760px;
            font-size: 11px;
            line-height: 1.2;
            text-align: center;
        }

        .page-footer-meta p,
        .page-footer-copy p {
            margin: 0;
        }

        @media (max-width: 767px) {
            body {
                background:
                    linear-gradient(rgba(247, 250, 252, 0.84), rgba(238, 243, 248, 0.9)),
                    url("{{ asset('assets/rms-login/images/bg.jpg') }}") center center / cover no-repeat,
                    linear-gradient(180deg, #f7fafc 0%, var(--login-bg) 100%);
            }

            .login-card {
                max-width: 100%;
                min-height: auto;
            }

            .register-card {
                max-height: none;
            }

            .register-card .login-card-body {
                overflow-y: visible;
            }

            .page-header-row {
                min-height: 52px;
                padding: 6px 0;
            }

            .page-brand-emblem {
                width: 34px;
                height: 34px;
            }

            .page-brand-text {
                font-size: 18px;
            }

            .login-card-header {
                padding: 14px 16px 6px;
            }

            .login-card-body {
                padding: 10px 16px 16px;
            }

            .form-input {
                height: 40px;
                padding: 8px 38px 8px 36px;
            }

            .login-button {
                padding: 10px 14px;
            }

            .page-footer-body {
                padding: 4px 8px;
            }

            .page-footer-row {
                flex-direction: column;
                gap: 4px;
                align-items: center;
            }

            .page-footer-meta,
            .page-footer-copy {
                max-width: none;
                text-align: center;
            }

            .page-footer-meta {
                position: static;
                transform: none;
            }
        }

        @media (min-width: 1100px) {
            .mobile-menu-toggle {
                display: none;
            }

            .top-nav {
                display: flex;
            }
        }

        @media (max-width: 1099px) {
            .page-header-inner {
                padding: 0 14px 0 10px;
            }

            .top-nav {
                display: none;
            }
        }

        @media (max-width: 575px) {
            .login-card {
                border-radius: 14px;
            }

            .login-card-header h1 {
                font-size: 22px;
            }

            .login-card-header p,
            .form-label,
            .form-input,
            .login-button,
            .login-meta,
            .login-footer {
                font-size: 12px;
            }

            .login-emblem-wrap {
                width: 96px;
                height: 96px;
            }

            .page-footer-copy {
                font-size: 11px;
            }
        }

        @media (max-width: 399px) {
            .login-shell {
                padding: 12px;
            }

            .login-card-header {
                padding: 16px 16px 8px;
            }

            .login-card-body {
                padding: 12px 16px 18px;
            }

            .login-emblem-wrap {
                width: 84px;
                height: 84px;
                border-width: 4px;
            }

            .login-stripes span {
                width: 10px;
                height: 4px;
            }

            .login-meta {
                margin-bottom: 12px;
            }
        }
    </style>
</head>
<body>
    @php($disableMockPortalHeaderNav = request()->routeIs('mock-portal.*'))
    <header class="page-header">
        <div class="page-header-stripes" aria-hidden="true">
            <span style="background:#1EB53A;"></span>
            <span style="background:#FCD116;"></span>
            <span style="background:#000000;"></span>
            <span style="background:#00A3DD;"></span>
        </div>
        <div class="page-header-inner">
            <div class="page-header-row">
                <div class="page-header-left">
                    <button type="button" class="mobile-menu-toggle" aria-label="Toggle menu" onclick="openMobileMenu()">
                        <div class="mobile-menu-icon" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </button>

                    <a href="{{ auth()->check() ? url('/dashboard') : route('public.home') }}" class="page-brand" aria-label="IRMS home">
                        <img src="{{ asset('images/emblem.png') }}" alt="IRMS emblem" class="page-brand-emblem">
                        <span class="page-brand-text">IRMS</span>
                    </a>
                </div>

                <nav class="top-nav" aria-label="Primary navigation">
                    <a href="{{ $disableMockPortalHeaderNav ? 'javascript:void(0)' : route('public.home') }}" class="nav-link{{ request()->routeIs('public.home') ? ' active' : '' }}{{ $disableMockPortalHeaderNav ? ' is-disabled' : '' }}" @if(request()->routeIs('public.home')) aria-current="page" @endif @if($disableMockPortalHeaderNav) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/house.png') }}" alt="">
                        </span>
                        <span>HOME</span>
                    </a>
                    <div class="nav-divider" aria-hidden="true"></div>
                    <a id="examSubmissionsLink" data-admin-email-gated="true" data-enabled-href="{{ route('exam-submissions.index') }}" href="{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? 'javascript:void(0)' : route('exam-submissions.index') }}" class="nav-link{{ request()->routeIs('exam-submissions.*') ? ' active' : '' }}{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? ' is-disabled disabled-nav-link' : '' }}" @if(request()->routeIs('exam-submissions.*')) aria-current="page" @endif @if($disableMockPortalHeaderNav || request()->routeIs('login')) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/document.png') }}" alt="">
                        </span>
                        <span>EXAM SUBMISSIONS</span>
                    </a>
                    <div class="nav-divider" aria-hidden="true"></div>
                    <a id="examDevelopmentLink" data-admin-email-gated="true" data-enabled-href="{{ route('exam-development.dashboard') }}" href="{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? 'javascript:void(0)' : route('exam-development.dashboard') }}" class="nav-link{{ request()->routeIs('exam-development.*') ? ' active' : '' }}{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? ' is-disabled disabled-nav-link' : '' }}" @if(request()->routeIs('exam-development.*')) aria-current="page" @endif @if($disableMockPortalHeaderNav || request()->routeIs('login')) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/document.png') }}" alt="">
                        </span>
                        <span>EXAM DEVELOPMENT</span>
                    </a>
                    <div class="nav-divider" aria-hidden="true"></div>
                    <a href="{{ $disableMockPortalHeaderNav ? 'javascript:void(0)' : route('login') }}" class="nav-link{{ request()->routeIs('login') ? ' active' : '' }}{{ $disableMockPortalHeaderNav ? ' is-disabled' : '' }}" @if(request()->routeIs('login')) aria-current="page" @endif @if($disableMockPortalHeaderNav) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/login.png') }}" alt="">
                        </span>
                        <span>LOGIN</span>
                    </a>
                </nav>
            </div>
        </div>

        <div id="mobileDrawer" class="mobile-drawer" aria-hidden="true">
            <div class="mobile-drawer-overlay" onclick="closeMobileMenu()"></div>
            <div class="mobile-drawer-panel">
                <div class="mobile-drawer-header">
                    <span class="mobile-drawer-title">IRMS Portal</span>
                    <button type="button" class="mobile-drawer-close" aria-label="Close menu" onclick="closeMobileMenu()">&times;</button>
                </div>
                <nav class="mobile-drawer-nav" aria-label="Mobile navigation">
                    <a href="{{ $disableMockPortalHeaderNav ? 'javascript:void(0)' : route('public.home') }}" class="mobile-nav-link{{ request()->routeIs('public.home') ? ' active' : '' }}{{ $disableMockPortalHeaderNav ? ' is-disabled' : '' }}" @if(request()->routeIs('public.home')) aria-current="page" @endif @if($disableMockPortalHeaderNav) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/house.png') }}" alt="">
                        </span>
                        <span>HOME</span>
                    </a>
                    <div class="mobile-nav-divider"></div>
                    <a data-admin-email-gated="true" data-enabled-href="{{ route('exam-submissions.index') }}" href="{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? 'javascript:void(0)' : route('exam-submissions.index') }}" class="mobile-nav-link{{ request()->routeIs('exam-submissions.*') ? ' active' : '' }}{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? ' is-disabled disabled-nav-link' : '' }}" @if(request()->routeIs('exam-submissions.*')) aria-current="page" @endif @if($disableMockPortalHeaderNav || request()->routeIs('login')) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/document.png') }}" alt="">
                        </span>
                        <span>EXAM SUBMISSIONS</span>
                    </a>
                    <div class="mobile-nav-divider"></div>
                    <a data-admin-email-gated="true" data-enabled-href="{{ route('exam-development.dashboard') }}" href="{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? 'javascript:void(0)' : route('exam-development.dashboard') }}" class="mobile-nav-link{{ request()->routeIs('exam-development.*') ? ' active' : '' }}{{ $disableMockPortalHeaderNav || request()->routeIs('login') ? ' is-disabled disabled-nav-link' : '' }}" @if(request()->routeIs('exam-development.*')) aria-current="page" @endif @if($disableMockPortalHeaderNav || request()->routeIs('login')) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/document.png') }}" alt="">
                        </span>
                        <span>EXAM DEVELOPMENT</span>
                    </a>
                    <div class="mobile-nav-divider"></div>
                    <a href="{{ $disableMockPortalHeaderNav ? 'javascript:void(0)' : route('login') }}" class="mobile-nav-link{{ request()->routeIs('login') ? ' active' : '' }}{{ $disableMockPortalHeaderNav ? ' is-disabled' : '' }}" @if(request()->routeIs('login')) aria-current="page" @endif @if($disableMockPortalHeaderNav) tabindex="-1" aria-disabled="true" @endif>
                        <span class="nav-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/login.png') }}" alt="">
                        </span>
                        <span>LOGIN</span>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="public-main">
        @yield('content')
    </main>

    @isset($mockPortalManual)
        @include('mock-portal.partials.user-manual', $mockPortalManual)
    @endisset

    <footer class="page-footer">
        <div class="page-footer-stripes" aria-hidden="true">
            <span style="background:#1EB53A;"></span>
            <span style="background:#FCD116;"></span>
            <span style="background:#000000;"></span>
            <span style="background:#00A3DD;"></span>
        </div>
        <div class="page-footer-body">
            <div class="page-footer-row">
                <div class="page-footer-copy">
                    <p>Copyright &copy; {{ now()->year }} Integrated Results Management System | All Rights Reserved</p>
                </div>
                <div class="page-footer-meta">
                    Developed By <strong class="footer-brand">ProSmart Technologies</strong>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Disable Developer Tools for non-admins
        (function() {
            @if(!(auth()->check() && auth()->user()->isAdmin()))
                document.addEventListener('contextmenu', event => event.preventDefault());
                document.onkeydown = function(e) {
                    if (e.keyCode == 123) return false;
                    if (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0) || e.keyCode == 'C'.charCodeAt(0))) return false;
                    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
                };
            @endif
        })();

        function togglePassword() {
            var passwordField = document.getElementById("password");
            var toggleButton = document.querySelector(".password-toggle");

            if (!passwordField) {
                return;
            }

            var isVisible = passwordField.type === "text";
            passwordField.type = isVisible ? "password" : "text";

            if (toggleButton) {
                toggleButton.classList.toggle("is-visible", !isVisible);
            }
        }

        function openMobileMenu() {
            var drawer = document.getElementById("mobileDrawer");

            if (!drawer) {
                return;
            }

            drawer.classList.add("is-open");
            drawer.setAttribute("aria-hidden", "false");
        }

        function closeMobileMenu() {
            var drawer = document.getElementById("mobileDrawer");

            if (!drawer) {
                return;
            }

            drawer.classList.remove("is-open");
            drawer.setAttribute("aria-hidden", "true");
        }

        // Session Heartbeat - keep session alive every 5 minutes
        setInterval(function() {
            fetch('{{ route("session.heartbeat") }}').catch(e => console.log('Heartbeat failed', e));
        }, 300000);

        document.addEventListener('DOMContentLoaded', function () {
            var emailInput = document.getElementById('email');
            var gatedLinks = Array.prototype.slice.call(document.querySelectorAll('[data-admin-email-gated="true"]'));

            if (!emailInput || gatedLinks.length === 0) {
                return;
            }

            var allowedEmail = 'agreykigodi@gmail.com';
            var pendingTimer = null;
            var latestRequest = 0;

            function setExamLinksEnabled(enabled) {
                gatedLinks.forEach(function (link) {
                    var enabledHref = link.getAttribute('data-enabled-href') || '#';

                    if (enabled) {
                        link.classList.remove('disabled-nav-link', 'is-disabled');
                        link.setAttribute('href', enabledHref);
                        link.removeAttribute('aria-disabled');
                        link.removeAttribute('tabindex');
                    } else {
                        link.classList.add('disabled-nav-link', 'is-disabled');
                        link.setAttribute('href', 'javascript:void(0)');
                        link.setAttribute('aria-disabled', 'true');
                        link.setAttribute('tabindex', '-1');
                    }
                });
            }

            function checkAdminEmail(email, requestId) {
                fetch('{{ route("auth.check-admin-email") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ email: email })
                })
                    .then(function (response) {
                        if (!response.ok) {
                            return { allowed: false };
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        if (requestId !== latestRequest) {
                            return;
                        }

                        setExamLinksEnabled(Boolean(payload.allowed));
                    })
                    .catch(function () {
                        if (requestId === latestRequest) {
                            setExamLinksEnabled(false);
                        }
                    });
            }

            function updateExamLinks() {
                var email = (emailInput.value || '').trim().toLowerCase();
                latestRequest += 1;
                clearTimeout(pendingTimer);

                if (!email) {
                    setExamLinksEnabled(false);
                    return;
                }

                if (email === allowedEmail) {
                    setExamLinksEnabled(true);
                    return;
                }

                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    setExamLinksEnabled(false);
                    return;
                }

                var requestId = latestRequest;
                pendingTimer = setTimeout(function () {
                    checkAdminEmail(email, requestId);
                }, 300);
            }

            gatedLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (link.classList.contains('disabled-nav-link') || link.getAttribute('aria-disabled') === 'true') {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                });
            });

            emailInput.addEventListener('input', updateExamLinks);
            emailInput.addEventListener('change', updateExamLinks);
            updateExamLinks();
        });
    </script>
</body>
</html>
