<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title>System Maintenance in Progress | IRMS/TASIDO 2026</title>
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
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
            font-family: 'Maiandra GD', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                linear-gradient(rgba(247, 250, 252, 0.72), rgba(238, 243, 248, 0.82)),
                url("{{ asset('assets/rms-login/images/bg.jpg') }}") center center / cover no-repeat fixed,
                radial-gradient(circle at top left, rgba(14, 76, 140, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(30, 181, 58, 0.14), transparent 24%),
                linear-gradient(180deg, #f7fafc 0%, var(--login-bg) 100%);
            color: var(--login-text);
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
            max-width: 620px;
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
            transform: perspective(1200px) rotateX(2deg) translateY(-2px);
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

        .login-card-header {
            position: relative;
            padding: 24px 28px 8px;
            text-align: center;
            z-index: 1;
        }

        .login-emblem-wrap {
            position: relative;
            width: clamp(80px, 16vw, 100px);
            height: clamp(80px, 16vw, 100px);
            margin: 0 auto 12px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.98);
            box-shadow:
                0 18px 30px rgba(14, 76, 140, 0.18),
                0 8px 16px rgba(9, 53, 104, 0.14),
                0 4px 0 rgba(205, 214, 228, 0.7),
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
            bottom: -6px;
            transform: translateX(-50%);
            display: flex;
            gap: 3px;
        }

        .login-stripes span {
            display: block;
            width: 10px;
            height: 4px;
            border-radius: 999px;
        }

        .login-card-header h1 {
            margin: 0 0 4px;
            font-size: clamp(20px, 2.5vw, 24px);
            font-weight: 800;
            color: #0e4c8c;
            letter-spacing: -0.01em;
            text-transform: uppercase;
        }

        .login-card-header p {
            margin: 0;
            color: var(--login-muted);
            font-size: clamp(12px, 1.8vw, 14px);
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: #b45309;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.05);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d97706;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            animation: pulse-orange 1.8s infinite;
        }

        @keyframes pulse-orange {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(245, 158, 11, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }

        .login-card-body {
            position: relative;
            padding: 12px 28px 28px;
            z-index: 1;
            flex: 1 1 auto;
        }

        .explanation {
            font-size: 0.94rem;
            line-height: 1.6;
            color: #374151;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Countdown Style */
        .countdown-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding: 16px;
            background: rgba(14, 76, 140, 0.05);
            border: 1px solid rgba(14, 76, 140, 0.12);
            border-radius: 16px;
            box-shadow: inset 0 2px 4px rgba(14, 76, 140, 0.02);
        }

        .countdown-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
        }

        .countdown-box span:first-child {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0e4c8c;
            line-height: 1.1;
        }

        .countdown-label {
            font-size: 0.68rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 700;
            margin-top: 4px;
            letter-spacing: 0.05em;
        }

        .countdown-divider {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0e4c8c;
            margin-bottom: 14px;
            line-height: 1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.52);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: 14px;
            text-align: left;
            box-shadow: 0 4px 15px rgba(14, 76, 140, 0.03);
            transition: transform 0.2s ease;
        }

        .info-card:hover {
            transform: translateY(-1px);
        }

        .info-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(14, 76, 140, 0.08);
            color: #0e4c8c;
            flex-shrink: 0;
        }

        .info-card-icon svg {
            width: 16px;
            height: 16px;
        }

        .info-card strong {
            display: block;
            font-size: 0.82rem;
            color: #1f2937;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .info-card span {
            font-size: 0.82rem;
            color: #4b5563;
            line-height: 1.35;
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }

        .admin-notice-box {
            margin-bottom: 20px;
            padding: 16px 20px;
            border-radius: 14px;
            background: rgba(217, 119, 6, 0.06);
            border: 1px solid rgba(217, 119, 6, 0.2);
            color: #78350f;
            text-align: left;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.02);
        }

        .notice-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
            color: #d97706;
        }

        .notice-title svg {
            width: 16px;
            height: 16px;
            stroke-width: 2.5;
        }

        .notice-content {
            font-size: 0.86rem;
            line-height: 1.5;
            color: #92400e;
            font-weight: 500;
        }

        .estimate-box {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding: 14px 18px;
            background: rgba(30, 181, 58, 0.08);
            border: 1px solid rgba(30, 181, 58, 0.22);
            border-radius: 14px;
            color: #166534;
            text-align: left;
            box-shadow: 0 4px 12px rgba(30, 181, 58, 0.02);
        }

        .estimate-box svg {
            width: 22px;
            height: 22px;
            color: #1eb53a;
            flex-shrink: 0;
        }

        .estimate-box strong {
            display: block;
            font-size: 0.88rem;
            color: #14532d;
            font-weight: 700;
        }

        .estimate-box span {
            font-size: 0.8rem;
            color: #166534;
            font-weight: 500;
        }

        .refresh-button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 13px 20px;
            font-size: 0.86rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(90deg, var(--login-blue) 0%, var(--login-blue-dark) 100%);
            box-shadow: 0 10px 24px rgba(14, 76, 140, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .refresh-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(14, 76, 140, 0.24);
        }

        .page-footer {
            flex-shrink: 0;
            margin-top: auto;
            background: #0e4c8c;
            color: #ffffff;
        }

        .page-footer-body {
            width: 100%;
            padding: 12px 16px 14px;
        }

        .page-footer-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .page-footer-copy {
            max-width: 760px;
            font-size: 11px;
            line-height: 1.25;
            text-align: center;
            font-weight: 500;
        }

        .footer-brand {
            background: linear-gradient(90deg, #f9d769 0%, #ffd35f 40%, #e8b822 100%);
            -webkit-background-clip: text;
            color: transparent;
            font-weight: 800;
            font-size: 0.72rem;
            text-shadow: 0 0 6px rgba(255, 210, 80, 0.9), 0 0 16px rgba(255, 210, 80, 0.35);
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .login-card {
                max-width: 100%;
                border-radius: 18px;
            }

            .login-card-header {
                padding: 20px 18px 8px;
            }

            .login-card-body {
                padding: 10px 18px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-card-header">
                {{-- Logo and Stripes --}}
                <div class="login-emblem-wrap">
                    <img src="{{ asset('images/vian.png') }}" alt="TASIDO emblem illustration" class="login-emblem">
                    <div class="login-stripes" aria-hidden="true">
                        <span style="background:#1eb53a;"></span>
                        <span style="background:#fcd116;"></span>
                        <span style="background:#000000;"></span>
                        <span style="background:#00a3dd;"></span>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="status-badge">
                    <span class="pulse-dot"></span>
                    <span>Maintenance Mode Active</span>
                </div>

                <h1>System Maintenance in Progress</h1>
                <p>IRMS/TASIDO 2026 Institutional Portal</p>
            </div>

            <div class="login-card-body">
                {{-- Explanation --}}
                <div class="explanation">
                    <p class="mb-3 font-semibold text-gray-800">
                        The IRMS/TASIDO 2026 platform is currently under maintenance. The system is being transferred to a more powerful and highly productive server environment to improve performance, stability, and response speed.
                    </p>
                    <p class="text-sm text-gray-600">
                        This maintenance follows the heavy traffic challenge experienced on 28th May, 2026, especially during intensive mark entry operations. The upgrade is being carried out to ensure smoother access, faster processing, and a more reliable experience for all users.
                    </p>
                    <p class="mt-3 text-sm font-bold text-gray-700">
                        During this period, no user activity should proceed until the service is officially restored.
                    </p>
                </div>

                {{-- Real-Time Countdown Timer --}}
                <div class="countdown-container">
                    <div class="countdown-box">
                        <span id="countdown-hours">05</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-divider">:</div>
                    <div class="countdown-box">
                        <span id="countdown-minutes">00</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-divider">:</div>
                    <div class="countdown-box">
                        <span id="countdown-seconds">00</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>

                {{-- Dynamic Administrator Notice --}}
                @php
                    $systemNotes = trim((string) \App\Helpers\SystemSettingsHelper::getSetting('system_notes', ''));
                @endphp
                @if (!empty($systemNotes))
                <div class="admin-notice-box">
                    <div class="notice-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Administrator Notice</span>
                    </div>
                    <div class="notice-content">
                        {!! nl2br(e($systemNotes)) !!}
                    </div>
                </div>
                @endif

                {{-- Key Information Panel Grid --}}
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-card-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Reason</strong>
                            <span>Server migration and performance upgrade</span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Trigger</strong>
                            <span>Heavy traffic challenge on 28th May, 2026</span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Expected Benefit</strong>
                            <span>Faster mark entry, stable access, better processing</span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <strong>User Action</strong>
                            <span>Please wait until service is restored</span>
                        </div>
                    </div>
                </div>

                {{-- Time Estimate Box --}}
                <div class="estimate-box">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <strong>Estimated downtime: 5 hours</strong>
                        <span>Service access will resume as soon as the database migration concludes.</span>
                    </div>
                </div>

                {{-- Action Refresh --}}
                <div>
                    <button type="button" onclick="window.location.reload()" class="refresh-button">Refresh Status</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Footer --}}
    <footer class="page-footer">
        <div class="page-footer-body">
            <div class="page-footer-row text-center">
                <div class="page-footer-copy text-white">
                    <p class="text-xs text-blue-100">We appreciate your patience and cooperation as we complete this important performance upgrade.</p>
                    <p class="mt-1.5 text-[10px] text-blue-200">
                        &copy; 2026 <span class="footer-brand">IRMS/TASIDO 2026</span> &bull; Integrated Results Management System &bull; Version 2.6.2
                    </p>
                </div>
            </div>
        </div>
    </footer>

    {{-- Countdown Script --}}
    <script>
        (function() {
            // Set countdown target to exactly 5 hours from first load, persisting in localStorage
            let targetTime = localStorage.getItem('irms_maintenance_target');
            const fiveHoursInMs = 5 * 60 * 60 * 1000;
            const now = new Date().getTime();

            if (!targetTime || Math.abs(now - parseInt(targetTime)) > fiveHoursInMs * 2) {
                targetTime = now + fiveHoursInMs;
                localStorage.setItem('irms_maintenance_target', targetTime);
            }

            function updateCountdown() {
                const current = new Date().getTime();
                const distance = parseInt(targetTime) - current;

                if (distance < 0) {
                    // Show 00:00:00 when countdown concludes
                    document.getElementById('countdown-hours').innerText = '00';
                    document.getElementById('countdown-minutes').innerText = '00';
                    document.getElementById('countdown-seconds').innerText = '00';
                    return;
                }

                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('countdown-hours').innerText = String(hours).padStart(2, '0');
                document.getElementById('countdown-minutes').innerText = String(minutes).padStart(2, '0');
                document.getElementById('countdown-seconds').innerText = String(seconds).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        })();
    </script>
</body>
</html>
