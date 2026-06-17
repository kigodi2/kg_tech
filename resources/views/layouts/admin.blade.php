<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'IRMS Admin')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        input, select, textarea, button { font-family: inherit; }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100vw;
            min-height: 100vh;
            overflow-x: hidden;
            background: #0f1117;
            color: #f0f4f7;
            font-family: 'Maiandra GD', sans-serif;
        }
        :root {
            --tz-green: #1EB53A;
            --tz-yellow: #FCD116;
            --tz-blue: #00A3DD;
            --tz-card: #101518;
            --tz-text: #f0f4f7;
            --tz-muted: rgba(255,255,255,.45);
            --tz-gold: #BBA45E;
        }
        .um-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #0f1117;
            width: 100%;
            max-width: 100%;
        }
        .um-body-row {
            display: flex;
            flex: 1;
            width: 100%;
            max-width: 100%;
            min-height: 100vh;
            background: linear-gradient(180deg, #0d1b2a, #11202e);
        }
        .um-sidebar {
            width: 260px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: linear-gradient(180deg, #0d1b2a, #11202e);
            border-right: 1px solid rgba(187,164,94,.18);
            box-shadow: 16px 0 40px rgba(0,0,0,.22);
            z-index: 100;
            flex-shrink: 0;
        }
        .um-profile {
            padding: 20px;
            border-bottom: 1px solid rgba(187,164,94,.15);
            background: linear-gradient(135deg, rgba(187,164,94,.08), transparent);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .adm-brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--tz-green), #0f7a1e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .adm-brand-text {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--tz-text);
            line-height: 1.1;
        }
        .adm-brand-sub {
            font-size: .65rem;
            color: var(--tz-yellow);
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
        }
        .um-nav {
            padding: 14px 12px;
            flex: 1;
            overflow-y: auto;
        }
        .um-nav-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(187,164,94,.55);
            padding: 6px 8px 4px;
            margin-top: 10px;
        }
        .um-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 10px;
            color: rgba(255,255,255,.65);
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .18s;
            margin-bottom: 2px;
            width: 100%;
        }
        .um-nav a:hover,
        .um-nav a.active {
            background: rgba(187,164,94,.12);
            color: #f0e6c8;
        }
        .um-nav a.active {
            background: rgba(187,164,94,.18);
        }
        .nav-ico {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            background: rgba(255,255,255,.06);
            flex-shrink: 0;
        }
        .um-nav a:hover .nav-ico,
        .um-nav a.active .nav-ico {
            background: rgba(187,164,94,.2);
            color: var(--tz-gold);
        }
        .um-footer {
            padding: 16px;
            border-top: 1px solid rgba(187,164,94,.15);
        }
        .um-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 9px 14px;
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 10px;
            color: #fca5a5;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .18s;
            text-decoration: none;
        }
        .um-logout:hover {
            background: rgba(239,68,68,.2);
            color: #fff;
        }
        .um-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            max-width: 100%;
            margin-left: 260px;
            background: #0f1117;
            border-left: 1px solid rgba(187,164,94,.18);
        }
        .um-topbar {
            background: rgba(15,17,23,.95);
            border-bottom: 1px solid rgba(187,164,94,.15);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
            flex-wrap: wrap;
            gap: 12px;
            max-width: 100%;
        }
        .um-topbar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f0e6c8;
        }
        .um-topbar-sub {
            font-size: .8rem;
            color: rgba(255,255,255,.4);
            margin-top: 1px;
        }
        .adm-user {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(30,181,58,.07);
            border: 1px solid rgba(30,181,58,.16);
            padding: 4px 12px 4px 5px;
            border-radius: 22px;
        }
        .adm-user-av {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--tz-green), #0f7a1e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .75rem;
            font-weight: 800;
        }
        .adm-user-name {
            font-size: .78rem;
            font-weight: 700;
            color: var(--tz-text);
        }
        .adm-user-role {
            font-size: .62rem;
            color: var(--tz-yellow);
        }
        .um-content {
            padding: 28px;
            flex: 1;
        }
        .page-footer {
            background: #0f1117;
            color: #ffffff;
            border-top: 1px solid rgba(187,164,94,.15);
            margin-top: auto;
            width: 100%;
        }
        .page-footer-stripes {
            display: flex;
            width: 100%;
            height: 3px;
        }
        .page-footer-stripes span { display: block; width: 25%; }
        .page-footer-body { width: 100%; padding: 12px 24px; }
        .page-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            width: 100%;
        }
        .page-footer-copy,
        .page-footer-meta {
            font-size: .75rem;
            line-height: 1.45;
            color: rgba(255,255,255,.6);
        }
        .footer-brand {
            background: linear-gradient(90deg, #f9d769 0%, #ffd35f 40%, #e8b822 100%);
            -webkit-background-clip: text;
            color: transparent;
            font-weight: 800;
            font-size: .85rem;
        }
        @media (max-width: 1024px) {
            .um-body-row { flex-direction: column; background: #0f1117; }
            .um-sidebar {
                width: 100%;
                height: auto;
                max-height: none;
                position: static;
                box-shadow: none;
                border-right: none;
                border-bottom: 1px solid rgba(187,164,94,.18);
            }
            .um-main {
                margin-left: 0;
                border-left: none;
            }
        }
        @media (max-width: 640px) {
            .um-topbar { align-items: flex-start; flex-direction: column; }
            .um-content { padding: 18px; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $adminUser = auth()->user();
@endphp
<div class="um-shell">
    <div class="um-body-row">
        <aside class="um-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="um-profile">
                <div class="adm-brand-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="adm-brand-text">IRMS</div>
                    <div class="adm-brand-sub">Admin Panel</div>
                </div>
            </a>

            <nav class="um-nav">
                <div class="um-nav-label">Overview</div>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-gauge-high"></i></span> Dashboard
                </a>

                <div class="um-nav-label">Administration</div>
                <a href="{{ route('admin.manage-users') }}" class="{{ request()->is('admin/manage-users*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-gear"></i></span> Users & Roles
                </a>
                <a href="{{ route('mark-entry.psle.subject-panel-assignments.index') }}" class="{{ request()->is('mark-entry/psle/subject-panel-assignments*') || request()->is('admin/subject-panel-assignments*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-check"></i></span> Subject Panel Assignments
                </a>
                <a href="{{ route('admin.system-settings') }}" class="{{ request()->is('admin/system-settings*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-sliders-h"></i></span> System Settings
                </a>

                <div class="um-nav-label">Registration</div>
                <a href="{{ route('admin.registration.regions') }}" class="{{ request()->is('admin/registration/regions*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-map"></i></span> Regions
                </a>
                <a href="{{ route('admin.registration.schools') }}" class="{{ request()->is('admin/registration/schools*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-school"></i></span> Schools
                </a>
                <a href="{{ route('admin.registration.candidates') }}" class="{{ request()->is('admin/registration/candidates*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-graduate"></i></span> Candidates
                </a>
            </nav>

            <div class="um-footer">
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="um-logout">
                        <i class="fas fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="um-main">
            <header class="um-topbar">
                <div>
                    <div class="um-topbar-title">@yield('title', 'IRMS Admin')</div>
                    <div class="um-topbar-sub">Integrated Results Management System</div>
                </div>
                @auth
                    <div class="adm-user">
                        <div class="adm-user-av">{{ strtoupper(substr($adminUser?->name ?? 'A', 0, 1)) }}</div>
                        <div>
                            <div class="adm-user-name">{{ $adminUser?->name ?? 'Administrator' }}</div>
                            <div class="adm-user-role">System Administrator</div>
                        </div>
                    </div>
                @endauth
            </header>

            <section class="um-content">
                @yield('content')
            </section>

            <footer class="page-footer">
                <div class="page-footer-stripes" aria-hidden="true">
                    <span style="background:#1EB53A;"></span>
                    <span style="background:#FCD116;"></span>
                    <span style="background:#000000;"></span>
                    <span style="background:#00A3DD;"></span>
                </div>
                <div class="page-footer-body">
                    <div class="page-footer-row">
                        <div class="page-footer-copy">&copy; {{ now()->year }} IRMS - All Rights Reserved</div>
                        <div class="page-footer-meta">Developed by <span class="footer-brand">ProSmart Technologies</span></div>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
