@extends('layouts.auth-rms')

@section('title', 'IRMS Home')

@section('content')
<style>
    :root {
        --tz-green: #1EB53A;
        --tz-yellow: #FCD116;
        --tz-blue: #00A3DD;
        --tz-text: #f0f4f7;
        --tz-muted: rgba(255, 255, 255, .45);
    }

    .public-main {
        background:
            radial-gradient(circle at 50% 26%, rgba(14, 165, 233, 0.13), transparent 34%),
            radial-gradient(circle at 50% 52%, rgba(6, 182, 212, 0.07), transparent 40%),
            linear-gradient(180deg, #071014 0%, #050b0f 100%) !important;
        font-family: 'Maiandra GD', sans-serif;
        color: var(--tz-text);
        padding: clamp(24px, 4vw, 48px) clamp(16px, 3vw, 32px);
        min-height: 100vh;
        width: 100%;
    }

    .public-home-shell {
        max-width: 1080px !important;
        margin: 0 auto;
        display: grid;
        gap: 48px !important;
    }

    /* Hero Section - Elevated 3D Panel */
    .hero-section {
        position: relative;
        isolation: isolate;
        border: 1.5px solid rgba(56, 189, 248, 0.32) !important;
        border-radius: 22px !important;
        background:
            linear-gradient(145deg, rgba(14, 37, 54, 0.96), rgba(6, 19, 30, 0.96)) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            inset 0 -10px 24px rgba(0, 0, 0, 0.32),
            0 18px 45px rgba(0, 0, 0, 0.48),
            0 0 55px rgba(14, 165, 233, 0.16) !important;
        padding: 48px 56px !important;
        overflow: visible !important; /* To allow pseudo backing glow to bleed out */
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    /* Radial Backlight projected directly behind the hero card */
    .hero-section::before {
        content: "" !important;
        position: absolute !important;
        inset: -28px !important;
        z-index: -1 !important;
        border-radius: 32px !important;
        background:
            radial-gradient(circle at 35% 25%, rgba(14, 165, 233, 0.20), transparent 48%),
            radial-gradient(circle at 70% 75%, rgba(6, 182, 212, 0.14), transparent 55%) !important;
        filter: blur(22px) !important;
        opacity: 0.85 !important;
        pointer-events: none !important;
        transition: all 220ms ease !important;
    }

    .hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(300px, 0.85fr) !important;
        gap: 36px !important;
        align-items: center;
    }

    .hero-markers {
        display: inline-flex;
        gap: 5px;
        margin-bottom: 16px;
    }

    .hero-markers span {
        width: 34px;
        height: 4px;
        border-radius: 999px;
        display: block;
    }

    .hero-eyebrow {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--tz-blue);
        margin-bottom: 8px;
    }

    .hero-copy h1.hero-title {
        font-size: clamp(1.9rem, 3.8vw, 2.7rem) !important;
        font-weight: 900 !important;
        color: #f0e6c8 !important;
        margin: 0 0 16px !important;
        letter-spacing: -0.5px !important;
        line-height: 1.15 !important;
    }

    .hero-copy p.hero-desc {
        font-size: 0.95rem !important;
        color: rgba(255, 255, 255, 0.6) !important; /* Slightly brighter for premium contrast */
        line-height: 1.7 !important;
        margin: 0 0 28px !important;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .hero-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .hero-btn:hover {
        text-decoration: none;
    }

    .hero-btn-primary {
        background: linear-gradient(135deg, #00A3DD, #006fa3);
        color: #ffffff;
        border: 1px solid rgba(0, 163, 221, 0.2);
    }

    .hero-btn-primary:hover {
        background: linear-gradient(135deg, #00b4f0, #0081be);
        box-shadow: 0 8px 24px rgba(0, 163, 221, 0.3);
        transform: translateY(-1px);
    }

    .hero-btn-secondary {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--tz-text);
    }

    .hero-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    /* Access Notice Sidebar Panel */
    .hero-aside {
        border: 1px solid rgba(56, 189, 248, 0.22) !important;
        background: rgba(5, 15, 24, 0.72) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.06),
            0 16px 34px rgba(0, 0, 0, 0.36) !important;
        backdrop-filter: blur(10px) !important;
        border-radius: 14px !important;
        padding: 24px !important;
    }

    .hero-aside h2 {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #f0e6c8;
        margin: 0 0 10px;
    }

    .hero-aside p {
        font-size: 0.8rem;
        color: var(--tz-muted);
        line-height: 1.55;
        margin: 0 0 16px;
    }

    .hero-stat-list {
        display: grid;
        gap: 12px;
    }

    .hero-stat {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px !important;
        border-radius: 10px !important;
        background: rgba(255, 255, 255, 0.035) !important;
        border: 1px solid rgba(56, 189, 248, 0.16) !important;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .hero-stat:hover {
        border-color: rgba(56, 189, 248, 0.35) !important;
        background: rgba(255, 255, 255, 0.055) !important;
    }

    .hero-stat-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 163, 221, 0.1);
        border: 1px solid rgba(0, 163, 221, 0.15);
        font-size: 0.85rem;
        color: var(--tz-blue);
        flex-shrink: 0;
    }

    .hero-stat-info {
        flex: 1;
    }

    .hero-stat strong {
        display: block;
        color: #f0e6c8;
        font-size: 0.8rem;
        margin-bottom: 2px;
    }

    .hero-stat span {
        display: block;
        color: var(--tz-muted);
        font-size: 0.75rem;
        line-height: 1.45;
    }

    /* Card System & Sections */
    .section-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--tz-blue);
        margin-bottom: 20px !important;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .module-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px !important;
    }

    .module-card {
        position: relative !important;
        border: 1px solid rgba(56, 189, 248, 0.20) !important;
        border-radius: 18px !important;
        background:
            linear-gradient(145deg, rgba(13, 34, 50, 0.94), rgba(8, 24, 36, 0.94)) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.055),
            0 14px 30px rgba(0, 0, 0, 0.35) !important;
        padding: 28px 24px !important;
        transition:
            transform 220ms ease,
            box-shadow 220ms ease,
            border-color 220ms ease !important;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .module-card:hover {
        transform: translateY(-6px) scale(1.012) !important;
        border-color: rgba(56, 189, 248, 0.55) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            0 22px 46px rgba(0, 0, 0, 0.48),
            0 0 36px rgba(14, 165, 233, 0.18) !important;
    }

    .module-card-icon {
        width: 48px !important;
        height: 48px !important;
        border-radius: 12px !important;
        background: rgba(14, 165, 233, 0.10) !important;
        border: 1px solid rgba(56, 189, 248, 0.24) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.06),
            0 0 20px rgba(14, 165, 233, 0.10) !important;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px !important;
        color: #67d8ff !important;
        font-size: 1.2rem !important;
        flex-shrink: 0;
        transition: border-color 220ms ease, box-shadow 220ms ease !important;
    }

    .module-card:hover .module-card-icon {
        border-color: rgba(56, 189, 248, 0.55) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.10),
            0 0 26px rgba(14, 165, 233, 0.24) !important;
    }

    .module-card h3 {
        margin: 0 0 8px;
        font-size: 1rem;
        font-weight: 700;
        color: #f0e6c8;
    }

    .module-card p {
        margin: 0;
        color: var(--tz-muted);
        font-size: 0.8rem;
        line-height: 1.55;
    }

    /* Access Summary Section */
    .info-grid {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 20px !important;
    }

    .info-card {
        background: linear-gradient(145deg, rgba(13, 34, 50, 0.94), rgba(8, 24, 36, 0.94)) !important;
        border: 1px solid rgba(56, 189, 248, 0.16) !important;
        border-radius: 18px !important;
        padding: 28px 24px !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.055),
            0 14px 30px rgba(0, 0, 0, 0.35) !important;
    }

    .info-card h3 {
        margin: 0 0 16px;
        font-size: 1rem;
        font-weight: 700;
        color: #f0e6c8;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding-bottom: 10px;
    }

    .info-list {
        display: grid;
        gap: 14px;
    }

    .info-list-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .info-list-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(30, 181, 58, 0.10) !important;
        border: 1px solid rgba(30, 181, 58, 0.25) !important;
        box-shadow: 0 0 15px rgba(30, 181, 58, 0.08) !important;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6ae086;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .info-list-info {
        flex: 1;
    }

    .info-list-item strong {
        display: block;
        color: #f0e6c8;
        font-size: 0.85rem;
        margin-bottom: 2px;
    }

    .info-list-item span {
        display: block;
        color: var(--tz-muted);
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .faq-stack {
        display: grid;
        gap: 12px;
    }

    .faq-item {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 12px;
        padding: 16px !important;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .faq-item:hover {
        border-color: rgba(56, 189, 248, 0.2) !important;
        background: rgba(255, 255, 255, 0.05) !important;
    }

    .faq-item h3 {
        margin: 0 0 6px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #f0e6c8;
    }

    .faq-item p {
        margin: 0;
        color: var(--tz-muted);
        font-size: 0.78rem;
        line-height: 1.5;
    }

    /* Localized Navbar refinements on Home */
    .nav-link:hover {
        color: #eaf8ff !important;
        text-shadow: 0 0 16px rgba(14, 165, 233, 0.35) !important;
    }

    /* Responsiveness and Safeties */
    @media (max-width: 768px) {
        .public-main {
            padding: clamp(16px, 3vw, 24px) 16px !important;
        }

        .public-home-shell {
            gap: 32px !important;
        }

        .hero-section {
            padding: 34px 24px !important;
            max-width: calc(100vw - 32px) !important;
        }

        .hero-section::before {
            inset: -18px !important;
            filter: blur(16px) !important;
        }

        .hero-grid {
            grid-template-columns: 1fr !important;
            gap: 28px !important;
        }

        .module-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }

        .info-grid {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }

        .module-card:hover {
            transform: none !important;
        }
    }

    @media (max-width: 600px) {
        .module-grid {
            grid-template-columns: 1fr !important;
        }
    }

    /* Prefers Reduced Motion support */
    @media (prefers-reduced-motion: reduce) {
        .hero-section,
        .module-card,
        .nav-link,
        .hero-stat,
        .faq-item {
            transition: none !important;
        }

        .module-card:hover,
        .hero-section:hover,
        .hero-stat:hover {
            transform: none !important;
        }
    }
</style>

<main class="public-main">
    <div class="public-home-shell">
        
        {{-- Hero Section --}}
        <section class="hero-section">
            <div class="hero-grid">
                <div class="hero-copy">
                    <div class="hero-markers" aria-hidden="true">
                        <span style="background:#1EB53A;"></span>
                        <span style="background:#FCD116;"></span>
                        <span style="background:#000000;"></span>
                        <span style="background:#00A3DD;"></span>
                    </div>
                    <div class="hero-eyebrow">Official Entry Workspace</div>
                    <h1 class="hero-title">Integrated Results Management System</h1>
                    <p class="hero-desc">This is the official public entry page for IRMS. Authorized officers may proceed to login to access protected examination workflows, including registration, mark entry, result processing, evaluations, and administrative operations.</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="hero-btn hero-btn-primary">
                            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
                            <span>Proceed to Login</span>
                        </a>
                        <a href="#modules" class="hero-btn hero-btn-secondary">
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                            <span>View System Overview</span>
                        </a>
                    </div>
                </div>

                <aside class="hero-aside">
                    <h2>Access Notice</h2>
                    <p>This workspace is visible to guests for orientation. Internal system functions are protected and require a secure authentication session.</p>
                    <div class="hero-stat-list">
                        <div class="hero-stat">
                            <div class="hero-stat-icon"><i class="fas fa-shield-halved" aria-hidden="true"></i></div>
                            <div class="hero-stat-info">
                                <strong>Protected Internal Modules</strong>
                                <span>Dashboards, candidate registrations, and mark entries are locked behind secure authentication.</span>
                            </div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-icon"><i class="fas fa-route" aria-hidden="true"></i></div>
                            <div class="hero-stat-info">
                                <strong>Public Navigation</strong>
                                <span>Home opens this entrance page, while logins redirect to role-specific administrative views.</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        {{-- Core IRMS Modules --}}
        <section id="modules" style="scroll-margin-top: 80px;">
            <div class="section-label"><i class="fas fa-cubes"></i> Core IRMS Modules</div>
            <div class="module-grid">
                <article class="module-card">
                    <div class="module-card-icon">
                        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                    </div>
                    <h3>Exam Registration</h3>
                    <p>Manage regions, districts, schools, and Standard VII / Form VI candidate data in a structured mock environment.</p>
                </article>
                
                <article class="module-card">
                    <div class="module-card-icon">
                        <i class="fas fa-pen-ruler" aria-hidden="true"></i>
                    </div>
                    <h3>Mark Entry</h3>
                    <p>Controlled capture, secure uploads, moderation, and automated validation for active exam years.</p>
                </article>
                
                <article class="module-card">
                    <div class="module-card-icon">
                        <i class="fas fa-shield-check" aria-hidden="true"></i>
                    </div>
                    <h3>Validation & Controls</h3>
                    <p>Multi-level audit reviews, outlier resolution, and verification steps to ensure complete data integrity.</p>
                </article>
                
                <article class="module-card">
                    <div class="module-card-icon">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </div>
                    <h3>Results Processing</h3>
                    <p>GPA computation, division allocation, and score snapshots generated via robust processing algorithms.</p>
                </article>
                
                <article class="module-card">
                    <div class="module-card-icon">
                        <i class="fas fa-file-chart-column" aria-hidden="true"></i>
                    </div>
                    <h3>Reports & Exports</h3>
                    <p>Executive summaries, performance analytics, PDF exports, and regional council-wise rankings.</p>
                </article>
                
                <article class="module-card">
                    <div class="module-card-icon">
                        <i class="fas fa-gears" aria-hidden="true"></i>
                    </div>
                    <h3>Administration</h3>
                    <p>Comprehensive tools for user management, automated SQLite backup scheduling, and global system configuration.</p>
                </article>
            </div>
        </section>

        {{-- Access Control Summary --}}
        <section>
            <div class="section-label"><i class="fas fa-shield-halved"></i> Access Control Summary</div>
            <div class="info-grid">
                <div class="info-card">
                    <h3>Guest Entry Summary</h3>
                    <div class="info-list">
                        <div class="info-list-item">
                            <div class="info-list-icon"><i class="fas fa-house" aria-hidden="true"></i></div>
                            <div class="info-list-info">
                                <strong>Public Entrance</strong>
                                <span>Guests can view this page to get oriented before proceeding to sign in.</span>
                            </div>
                        </div>
                        <div class="info-list-item">
                            <div class="info-list-icon"><i class="fas fa-user-lock" aria-hidden="true"></i></div>
                            <div class="info-list-info">
                                <strong>Login Redirection</strong>
                                <span>Unauthenticated requests targeting internal workspaces are routed to the login form.</span>
                            </div>
                        </div>
                        <div class="info-list-item">
                            <div class="info-list-icon"><i class="fas fa-right-from-bracket" aria-hidden="true"></i></div>
                            <div class="info-list-info">
                                <strong>Secure Session Invalidation</strong>
                                <span>Logging out terminates session keys instantly and clears Mark Entry Officer cache.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3>Important Notices</h3>
                    <div class="faq-stack">
                        <div class="faq-item">
                            <h3>Authorized access only</h3>
                            <p>IRMS is intended for approved administrative officers operating within registered exam boundaries.</p>
                        </div>
                        <div class="faq-item">
                            <h3>Entrance page purpose</h3>
                            <p>This layout offers a polished dashboard overview to authenticate before doing officer work.</p>
                        </div>
                        <div class="faq-item">
                            <h3>Uncompromised Security</h3>
                            <p>Route guards, role constraint middleware, and database encryption remain fully intact.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</main>
@endsection
