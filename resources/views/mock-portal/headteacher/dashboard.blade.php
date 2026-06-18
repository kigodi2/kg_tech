<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TASIDO 2026 - Headteacher Portal</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
</head>
<body>
@php
    $mockPortalManual = [
        'manualId' => 'headteacherDashboardManual',
        'manualTitle' => 'Headteacher Portal Guide',
        'manualSummary' => 'Use this guide to move from school access to safe candidate registration, upload, review, and correction.',
        'manualPdf' => '/headteacher_guide.pdf',
        'manualSteps' => [
            ['title' => 'Verify the school shown on the dashboard', 'body' => 'Before doing any registration work, confirm the centre number, region, district, and school name displayed on this page.'],
            ['title' => 'Monitor the countdown and deadline', 'body' => 'Use the registration timer to complete all candidate work before the window closes. Late submissions may be blocked automatically.'],
            ['title' => 'Open Candidate Management', 'body' => 'Use the Upload Candidates action to access the candidate page for adding, editing, uploading, and reviewing all Standard VII pupils.'],
            ['title' => 'Prefer manual add for small omissions', 'body' => 'If one or two pupils were omitted, use Add Candidate instead of uploading a full file again unless a full upload is truly necessary.'],
            ['title' => 'Check all candidate pages', 'body' => 'If some pupils do not appear immediately, review the total count and navigate through the pagination before concluding that records are missing.'],
        ],
        'manualNotes' => [
            '<strong>Best practice:</strong> Keep a checked class list after every successful registration session.',
            '<strong>Download option:</strong> Use the PDF guide for offline school reference.'
        ],
    ];
@endphp
<style>
* { box-sizing: border-box; }
body, html { margin: 0; padding: 0; min-height: 100vh; background: #0b1014; font-family: 'Maiandra GD', sans-serif; overflow-x: hidden; }
:root{--tz-green:#1EB53A;--tz-yellow:#FCD116;--tz-blue:#00A3DD;--tz-text:#f0f4f7;--tz-muted:rgba(255,255,255,.45);}

/* Hero */
.hero-topbar { background: rgba(11,16,20,0.95); border-bottom: 1px solid rgba(187,164,94,0.15); padding: 12px 32px; display: flex; align-items: center; justify-content: space-between; }
.hero-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.hero-brand-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #00A3DD, #006fa3); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; }
.hero-brand-text { font-size: 1.05rem; font-weight: 800; color: #f0e6c8; line-height: 1.1; }
.hero-brand-sub { font-size: 0.6rem; color: var(--tz-yellow); font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }

.hero-user { display: flex; align-items: center; gap: 8px; background: rgba(30,181,58,0.07); border: 1px solid rgba(30,181,58,0.16); padding: 4px 12px 4px 5px; border-radius: 22px; }
.hero-user-av { width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg, var(--tz-green), #0f7a1e); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.75rem; font-weight: 800; }
.hero-user-name { font-size: 0.78rem; font-weight: 700; color: var(--tz-text); }
.hero-user-role { font-size: 0.62rem; color: var(--tz-yellow); }

.flag-bar { display: flex; height: 4px; width: 100%; }
.flag-bar span { display: block; flex: 1; }

/* Hero Banner */
.hero-banner { background: linear-gradient(135deg, #050e15 0%, #0d1b2a 50%, #0a1520 100%); padding: 60px 32px; text-align: center; border-bottom: 1px solid rgba(187,164,94,0.1); position: relative; overflow: hidden; }
.hero-banner::before { content: ''; position: absolute; top: -100px; left: 50%; transform: translateX(-50%); width: 600px; height: 600px; border-radius: 50%; background: radial-gradient(circle, rgba(0,163,221,0.08) 0%, transparent 70%); pointer-events: none; }
.hero-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--tz-yellow); margin-bottom: 12px; }
.hero-title { font-size: 2.4rem; font-weight: 900; color: #f0e6c8; margin: 0 0 12px; letter-spacing: -0.5px; }
.hero-subtitle { font-size: 1rem; color: var(--tz-muted); max-width: 560px; margin: 0 auto 28px; line-height: 1.6; }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; background: rgba(30,181,58,0.12); color: #6ae086; border: 1px solid rgba(30,181,58,0.25); border-radius: 20px; font-size: 0.8rem; font-weight: 700; }

/* Info Cards */
.info-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-top: 1px solid rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.04); background: #080f15; }
.info-card { padding: 24px 28px; border-right: 1px solid rgba(255,255,255,0.04); transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; }
.info-card:hover { background-color: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.12); transform: translateY(-1px); box-shadow: 0 12px 28px rgba(0,0,0,0.18); }
.info-card:last-child { border-right: none; }
.info-card-label { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 6px; }
.info-card-value { font-size: 1.05rem; font-weight: 700; color: #f0e6c8; }
.info-card-sub { font-size: 0.75rem; color: var(--tz-muted); margin-top: 3px; }

/* School Panel */
.school-section { max-width: 900px; margin: 48px auto; padding: 0 32px; }
.section-label { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--tz-blue); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.school-card { background: linear-gradient(135deg, #0d1b2a, #111e29); border: 1px solid rgba(0,163,221,0.2); border-radius: 16px; padding: 28px 32px; display: flex; align-items: center; justify-content: space-between; gap: 20px; text-decoration: none; transition: all 0.2s ease; cursor: pointer; }
.school-card:hover { border-color: rgba(0,163,221,0.5); transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,163,221,0.14); }
.school-icon { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, rgba(0,163,221,0.2), rgba(0,163,221,0.05)); border: 1px solid rgba(0,163,221,0.25); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #67d8ff; flex-shrink: 0; }
.school-info { flex: 1; }
.school-code { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--tz-blue); margin-bottom: 4px; }
.school-name { font-size: 1.15rem; font-weight: 700; color: #f0e6c8; margin-bottom: 4px; }
.school-meta { font-size: 0.8rem; color: var(--tz-muted); }
.school-arrow { font-size: 1.2rem; color: rgba(255,255,255,0.25); transition: all 0.2s ease; }
.school-card:hover .school-arrow { color: #67d8ff; transform: translateX(4px); }

/* Window Info */
.window-bar { max-width: 900px; margin: 0 auto 40px; padding: 0 32px; }
.window-panel { background: rgba(30,181,58,0.08); border: 1px solid rgba(30,181,58,0.2); border-radius: 12px; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.window-text { font-size: 0.9rem; color: #6ae086; font-weight: 600; }
.window-days { font-size: 0.8rem; color: rgba(30,181,58,0.7); }

/* Footer */
.page-footer { background: #080f15; border-top: 1px solid rgba(255,255,255,0.05); padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.75rem; color: rgba(255,255,255,0.4); }
.page-footer-stripes { display: flex; width: 100%; height: 4px; margin-bottom: 10px; overflow: hidden; border-radius: 8px; }
.page-footer-stripes span { display: block; flex: 1 1 0; min-width: 0; }
.page-footer strong { color: #BBA45E; }
.footer-brand {
    background: linear-gradient(90deg, #f9d769 0%, #ffd35f 40%, #e8b822 100%);
    -webkit-background-clip: text;
    color: transparent;
    font-weight: 800;
    font-size: 0.85rem;
    text-shadow: 0 0 6px rgba(255, 210, 80, 0.9), 0 0 16px rgba(255, 210, 80, 0.35);
    transition: transform 0.24s ease, text-shadow 0.24s ease;
}
.footer-brand:hover {
    transform: translateY(-1px);
    text-shadow: 0 0 8px rgba(255, 210, 80, 0.95), 0 0 18px rgba(255, 210, 80, 0.5);
}

/* Logout */
.logout-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px; color: #fca5a5; font-size: 0.8rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s; }
.logout-btn:hover { background: rgba(239,68,68,0.2); color: #fff; }

@media (max-width: 768px) {
    .hero-topbar { flex-direction: column; gap: 16px; padding: 16px; text-align: center; }
    .hero-topbar > div { width: 100%; justify-content: center; }
    .info-strip { grid-template-columns: 1fr 1fr; }
    .hero-title { font-size: 1.7rem; }
    .school-card { flex-direction: column; text-align: center; padding: 20px; }
    .school-arrow { display: none; }
    .hero-banner { padding: 40px 20px; }
}
@media (max-width: 480px) {
    .info-strip { grid-template-columns: 1fr; }
    .hero-title { font-size: 1.4rem; }
    .hero-subtitle { font-size: 0.9rem; }
}
</style>

{{-- Top navigation bar --}}
<div class="hero-topbar">
    <div class="hero-brand" href="{{ route('mock-portal.school.dashboard') }}">
        <div class="hero-brand-icon"><i class="fas fa-graduation-cap"></i></div>
        <div>
            <div class="hero-brand-text">TASIDO 2026</div>
            <div class="hero-brand-sub">Headteacher Portal</div>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap: 14px;">
        <div class="hero-user">
            <div class="hero-user-av">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div>
                <div class="hero-user-name">{{ $user->name }}</div>
                <div class="hero-user-role">Headteacher</div>
            </div>
        </div>
        <form method="POST" action="/logout" style="margin:0;">
            @csrf
            <button type="submit" class="logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</button>
        </form>
    </div>
</div>

{{-- Tanzania flag stripe --}}
<div class="flag-bar" aria-hidden="true">
    <span style="background:#1EB53A;"></span>
    <span style="background:#FCD116;"></span>
    <span style="background:#000000;"></span>
    <span style="background:#00A3DD;"></span>
</div>

{{-- Hero Banner --}}
<div class="hero-banner">
    <div class="hero-eyebrow">STANDARD VII MOCK EXAMINATION REGISTRATION</div>
    <h1 class="hero-title">TASIDO 2026 Portal</h1>
    <p class="hero-subtitle">Welcome, <strong style="color:#f0e6c8;">{{ $user->name }}</strong>. Select your school below to manage your candidate registrations for the mock examination.</p>
    <span class="hero-badge"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Registration Window Open</span>
</div>

{{-- Info strip --}}
<div class="info-strip">
    <div class="info-card">
        <div class="info-card-label">Centre Number</div>
        <div class="info-card-value">{{ $school->code }}</div>
        <div class="info-card-sub">Your school identifier</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">Region</div>
        <div class="info-card-value">{{ $school->region->name ?? 'N/A' }}</div>
        <div class="info-card-sub">Administrative region</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">District</div>
        <div class="info-card-value">{{ $school->district->name ?? 'N/A' }}</div>
        <div class="info-card-sub">Local council/district</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">Status</div>
        <div class="info-card-value" style="color:#6ae086;">Active</div>
        <div class="info-card-sub">Portal registration open</div>
    </div>
</div>

{{-- Registration window status --}}
<div class="window-bar" style="margin-top: 40px;">
    <div class="window-panel">
        <div>
            <div class="window-text">
                <i class="fas fa-clock"></i> Time Remaining: 
                <span id="countdown-timer" style="font-family: monospace; font-size: 1.05rem; font-weight: bold; margin: 0 5px;">{{ $daysRemaining }}d 00h 00m 00s</span>
            </div>
            <div class="window-days">Deadline: <strong>{{ $registrationDeadline }}</strong>. Upload your candidate CSV before the window closes.</div>
        </div>
        <a href="{{ route('mock-portal.school.candidate') }}" style="background: linear-gradient(135deg, #00A3DD, #006fa3); color: #fff; padding: 10px 22px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-upload"></i> Upload Candidates
        </a>
    </div>
</div>

<script>
    (function() {
        const deadline = {{ $deadlineTimestamp }};
        const timerEl = document.getElementById('countdown-timer');

        function updateTimer() {
            const now = new Date().getTime();
            const diff = deadline - now;

            if (diff <= 0) {
                if (timerEl) timerEl.innerHTML = "EXPIRED";
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            if (timerEl) {
                timerEl.innerHTML = `${days}d ${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            }
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    })();
</script>

{{-- School card --}}
<div class="school-section">
    <div class="section-label"><i class="fas fa-school"></i> Your Registered School</div>
    <a href="{{ route('mock-portal.school.candidate') }}" class="school-card">
        <div class="school-icon"><i class="fas fa-school"></i></div>
        <div class="school-info">
            <div class="school-code">Centre No. {{ $school->code }}</div>
            <div class="school-name">{{ $school->name }}</div>
            <div class="school-meta">
                {{ $school->region->name ?? '' }}
                @if($school->district) &bull; {{ $school->district->name }} @endif
                &bull; Primary School
            </div>
        </div>
        <div class="school-arrow"><i class="fas fa-arrow-right"></i></div>
    </a>
</div>

{{-- Footer --}}
<footer class="page-footer">
    <div class="page-footer-stripes" aria-hidden="true">
        <span style="background:#1eb53a"></span>
        <span style="background:#fcd116"></span>
        <span style="background:#000"></span>
        <span style="background:#fcd116"></span>
        <span style="background:#00a3dd"></span>
    </div>
    <div>Copyright &copy; {{ now()->year }} Standard VII Mock TASIDO 2026 Registration Portal | All Rights Reserved</div>
    <div>Developed By <strong class="footer-brand">ProSmart Technologies</strong></div>
</footer>

@include('mock-portal.partials.user-manual', $mockPortalManual)
</body>
</html>
