@extends('layout')

@section('content')
<style>
    .registration-shell {
        width: 100%;
        padding: 28px 28px 8px;
        background:
            radial-gradient(circle at top right, rgba(53, 92, 154, 0.08), transparent 24%),
            linear-gradient(180deg, #f4f7fb 0%, #eef3f9 100%);
    }

    .registration-stack {
        display: grid;
        gap: 22px;
    }

    .registration-hero {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 30px 30px 28px;
        background:
            linear-gradient(135deg, rgba(20, 48, 88, 0.97) 0%, rgba(37, 82, 145, 0.94) 58%, rgba(16, 116, 95, 0.9) 100%);
        color: #ffffff;
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.14);
    }

    .registration-hero::before,
    .registration-hero::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
    }

    .registration-hero::before {
        width: 320px;
        height: 320px;
        right: -80px;
        top: -140px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 70%);
    }

    .registration-hero::after {
        width: 260px;
        height: 260px;
        left: -80px;
        bottom: -120px;
        background: radial-gradient(circle, rgba(252, 209, 22, 0.2) 0%, rgba(252, 209, 22, 0) 68%);
    }

    .registration-hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.8fr);
        gap: 22px;
        align-items: stretch;
    }

    .registration-kicker {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.78);
    }

    .registration-kicker::before {
        content: "";
        width: 36px;
        height: 4px;
        border-radius: 999px;
        background: linear-gradient(90deg, #1EB53A 0%, #FCD116 55%, #00A3DD 100%);
    }

    .registration-hero h1 {
        margin: 0 0 12px;
        font-size: clamp(2rem, 3.8vw, 3.1rem);
        line-height: 1.02;
        letter-spacing: -0.04em;
        color: #ffffff;
    }

    .registration-hero p {
        margin: 0;
        max-width: 720px;
        color: rgba(255, 255, 255, 0.88);
        font-size: 1rem;
        line-height: 1.8;
    }

    .registration-hero-highlights {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 22px;
    }

    .registration-chip {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.88rem;
        font-weight: 600;
    }

    .registration-chip i {
        color: #fcd116;
    }

    .registration-hero-aside {
        display: grid;
        gap: 12px;
    }

    .hero-panel {
        border-radius: 22px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(6px);
    }

    .hero-panel h2 {
        margin: 0 0 8px;
        font-size: 1.02rem;
        color: #ffffff;
    }

    .hero-panel p {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        line-height: 1.7;
    }

    .hero-mini-stats {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    .hero-mini-stat {
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        padding: 11px 12px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.08);
    }

    .hero-mini-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
    }

    .hero-mini-stat strong {
        display: block;
        color: #ffffff;
        font-size: 0.92rem;
    }

    .hero-mini-stat span {
        display: block;
        color: rgba(255, 255, 255, 0.76);
        font-size: 0.8rem;
        margin-top: 2px;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 20px 20px 18px;
        color: #ffffff;
        min-height: 164px;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.1);
    }

    .stat-card::after {
        content: "";
        position: absolute;
        inset: auto -36px -44px auto;
        width: 130px;
        height: 130px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
    }

    .stat-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .stat-card-label {
        display: block;
        font-size: 0.86rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        color: rgba(255, 255, 255, 0.8);
        text-transform: uppercase;
    }

    .stat-card-value {
        display: block;
        margin-top: 6px;
        font-size: 3rem;
        font-weight: 800;
        line-height: 0.92;
        letter-spacing: -0.05em;
    }

    .stat-card-icon {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.13);
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.92);
    }

    .stat-card-link {
        position: absolute;
        left: 20px;
        bottom: 18px;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    .stat-card-link:hover {
        color: #ffffff;
        text-decoration: none;
        transform: translateX(2px);
    }

    .section-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.75fr) minmax(320px, 0.85fr);
        gap: 20px;
        align-items: start;
    }

    .panel {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(203, 213, 225, 0.9);
        border-radius: 24px;
        padding: 22px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
    }

    .panel-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }

    .panel-title {
        margin: 0;
        font-size: 1.38rem;
        color: #162033;
    }

    .panel-subtitle {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 0.92rem;
        line-height: 1.7;
    }

    .overview-list {
        display: grid;
        gap: 12px;
    }

    .overview-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 16px 18px;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.95) 0%, rgba(241, 245, 249, 0.92) 100%);
        border: 1px solid rgba(226, 232, 240, 0.95);
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .overview-row:hover {
        transform: translateY(-1px);
        border-color: rgba(148, 163, 184, 0.7);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
    }

    .overview-main {
        display: grid;
        grid-template-columns: 46px minmax(0, 1fr);
        gap: 14px;
        align-items: center;
    }

    .overview-icon {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #355c9a 0%, #4d77bc 100%);
        color: #ffffff;
        font-size: 1rem;
    }

    .overview-name {
        display: block;
        color: #1e293b;
        font-size: 1.05rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .overview-meta {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: 0.82rem;
    }

    .overview-value {
        text-align: right;
        color: #1e293b;
        font-size: 0.96rem;
        font-weight: 700;
    }

    .panel-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
        color: #355c9a;
        font-size: 0.92rem;
        font-weight: 700;
        text-decoration: none;
    }

    .panel-link:hover {
        color: #274b82;
        text-decoration: none;
    }

    .mini-panel-stack {
        display: grid;
        gap: 18px;
    }

    .exam-type-list,
    .quick-stat-list,
    .action-list {
        display: grid;
        gap: 12px;
    }

    .exam-type-item,
    .quick-stat-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 14px 14px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid rgba(226, 232, 240, 0.95);
    }

    .exam-type-label strong {
        display: block;
        color: #1f2937;
        font-size: 0.94rem;
    }

    .exam-type-label span,
    .quick-stat-label {
        display: block;
        color: #64748b;
        font-size: 0.8rem;
        margin-top: 2px;
    }

    .exam-type-value,
    .quick-stat-value {
        color: #111827;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.05em;
    }

    .quick-stat-item {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .quick-stat-value {
        font-size: 1rem;
        line-height: 1.2;
        color: #1f2937;
    }

    .action-list a {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        min-height: 58px;
        padding: 0 16px;
        border-radius: 18px;
        color: #ffffff;
        text-decoration: none;
        font-weight: 700;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .action-list a:hover {
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
    }

    .action-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        font-size: 1rem;
    }

    .empty-state {
        padding: 26px 18px;
        border-radius: 18px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed rgba(191, 219, 254, 0.95);
    }

    @media (max-width: 1200px) {
        .registration-hero-grid,
        .section-grid,
        .stat-grid {
            grid-template-columns: 1fr 1fr;
        }

        .section-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .registration-shell {
            padding: 20px 16px 0;
        }

        .registration-hero-grid,
        .stat-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .registration-hero,
        .panel {
            padding: 20px;
            border-radius: 22px;
        }

        .stat-card {
            min-height: 150px;
        }

        .overview-row,
        .exam-type-item,
        .quick-stat-item {
            grid-template-columns: 1fr;
            text-align: left;
        }

        .overview-value,
        .exam-type-value,
        .quick-stat-value {
            text-align: left;
        }

        .action-list a {
            grid-template-columns: 42px minmax(0, 1fr);
        }

        .action-list a i:last-child {
            display: none;
        }
    }
</style>

<div x-data="registrationDashboard()" x-init="init()" class="registration-shell">
    <div class="registration-stack">
        <section class="registration-hero">
            <div class="registration-hero-grid">
                <div>
                    <div class="registration-kicker">Registration Control Centre</div>
                    <h1>Registration Management Dashboard</h1>
                    <p>Monitor registration coverage across regions, districts, schools, and candidates from one structured operational view. This page keeps the same underlying registration endpoints and actions, but presents them with clearer hierarchy and faster scanability.</p>
                    <div class="registration-hero-highlights">
                        <div class="registration-chip">
                            <i class="fas fa-map-location-dot" aria-hidden="true"></i>
                            <span>Regions and districts overview</span>
                        </div>
                        <div class="registration-chip">
                            <i class="fas fa-school" aria-hidden="true"></i>
                            <span>School registration monitoring</span>
                        </div>
                        <div class="registration-chip">
                            <i class="fas fa-user-graduate" aria-hidden="true"></i>
                            <span>Candidate registration tracking</span>
                        </div>
                    </div>
                </div>

                <aside class="registration-hero-aside">
                    <div class="hero-panel">
                        <h2>Operational Focus</h2>
                        <p>Use this dashboard to review registration progress, identify gaps in the hierarchy, and move quickly into the relevant management screen.</p>
                        <div class="hero-mini-stats">
                            <div class="hero-mini-stat">
                                <div class="hero-mini-stat-icon"><i class="fas fa-sitemap" aria-hidden="true"></i></div>
                                <div>
                                    <strong>Hierarchy Coverage</strong>
                                    <span>Track region-to-school registration structure in one place.</span>
                                </div>
                            </div>
                            <div class="hero-mini-stat">
                                <div class="hero-mini-stat-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
                                <div>
                                    <strong>Live Registration Totals</strong>
                                    <span>Cards and side panels update from the same registration endpoints already in use.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="stat-grid">
            <article class="stat-card" style="background:linear-gradient(135deg,#2453c9 0%,#2e67dd 100%);">
                <div class="stat-card-head">
                    <div>
                        <span class="stat-card-label">Total Regions</span>
                        <span class="stat-card-value" x-text="statistics.regions">0</span>
                    </div>
                    <div class="stat-card-icon"><i class="fas fa-globe-africa" aria-hidden="true"></i></div>
                </div>
                <a href="/admin/registration/regions" class="stat-card-link">
                    <span>Manage Regions</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="stat-card" style="background:linear-gradient(135deg,#7b2ff2 0%,#9d36e6 100%);">
                <div class="stat-card-head">
                    <div>
                        <span class="stat-card-label">Total Districts</span>
                        <span class="stat-card-value" x-text="statistics.districts">0</span>
                    </div>
                    <div class="stat-card-icon"><i class="fas fa-map" aria-hidden="true"></i></div>
                </div>
                <a href="/admin/registration/districts" class="stat-card-link">
                    <span>Manage Districts</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="stat-card" style="background:linear-gradient(135deg,#178d45 0%,#1fa34c 100%);">
                <div class="stat-card-head">
                    <div>
                        <span class="stat-card-label">Total Schools</span>
                        <span class="stat-card-value" x-text="statistics.schools">0</span>
                    </div>
                    <div class="stat-card-icon"><i class="fas fa-school" aria-hidden="true"></i></div>
                </div>
                <a href="/admin/registration/schools" class="stat-card-link">
                    <span>Manage Schools</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="stat-card" style="background:linear-gradient(135deg,#d5530b 0%,#eb6209 100%);">
                <div class="stat-card-head">
                    <div>
                        <span class="stat-card-label">Total Candidates</span>
                        <span class="stat-card-value" x-text="statistics.candidates">0</span>
                    </div>
                    <div class="stat-card-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                </div>
                <a href="/admin/registration/candidates" class="stat-card-link">
                    <span>Manage Candidates</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        </section>

        <section class="section-grid">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Regions Overview</h2>
                        <p class="panel-subtitle">Review the registration hierarchy by region and move directly into district-level administration.</p>
                    </div>
                </div>

                <div class="overview-list">
                    <template x-for="region in regions.slice(0, 5)" :key="region.id">
                        <div class="overview-row" @click="selectRegion(region)">
                            <div class="overview-main">
                                <div class="overview-icon"><i class="fas fa-earth-africa" aria-hidden="true"></i></div>
                                <div>
                                    <span class="overview-name" x-text="region.name"></span>
                                    <span class="overview-meta" x-text="'Code: ' + region.code"></span>
                                </div>
                            </div>
                            <div class="overview-value" x-text="region.districts_count + ' districts'"></div>
                        </div>
                    </template>

                    <template x-if="regions.length === 0">
                        <div class="empty-state">No regions have been added yet.</div>
                    </template>
                </div>

                <a href="/admin/registration/regions" class="panel-link">
                    <span>View all regions</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="mini-panel-stack">
                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Candidates by Exam Type</h2>
                            <p class="panel-subtitle">Current candidate distribution across supported examination categories.</p>
                        </div>
                    </div>

                    <div class="exam-type-list">
                        <div class="exam-type-item">
                            <div class="exam-type-label">
                                <strong>PSLE</strong>
                                <span>Primary</span>
                            </div>
                            <div class="exam-type-value" x-text="examTypeStats.PSLE || 0">0</div>
                        </div>
                        <div class="exam-type-item">
                            <div class="exam-type-label">
                                <strong>CSEE</strong>
                                <span>Secondary</span>
                            </div>
                            <div class="exam-type-value" x-text="examTypeStats.CSEE || 0">0</div>
                        </div>
                        <div class="exam-type-item">
                            <div class="exam-type-label">
                                <strong>ACSEE</strong>
                                <span>Advanced</span>
                            </div>
                            <div class="exam-type-value" x-text="examTypeStats.ACSEE || 0">0</div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Quick Stats</h2>
                            <p class="panel-subtitle">Key registration ratios derived from the existing system totals.</p>
                        </div>
                    </div>

                    <div class="quick-stat-list">
                        <div class="quick-stat-item">
                            <div class="quick-stat-label">Average schools per district</div>
                            <div class="quick-stat-value" x-text="statistics.districts ? (statistics.schools / statistics.districts).toFixed(1) : '0.0'">0.0</div>
                        </div>
                        <div class="quick-stat-item">
                            <div class="quick-stat-label">Average candidates per school</div>
                            <div class="quick-stat-value" x-text="statistics.schools ? (statistics.candidates / statistics.schools).toFixed(1) : '0.0'">0.0</div>
                        </div>
                        <div class="quick-stat-item">
                            <div class="quick-stat-label">Candidates registered</div>
                            <div class="quick-stat-value" x-text="statistics.candidates">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-grid">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Recent Districts</h2>
                        <p class="panel-subtitle">A quick view of district entries and the school counts currently attached to each one.</p>
                    </div>
                </div>

                <div class="overview-list">
                    <template x-for="district in districts.slice(0, 5)" :key="district.id">
                        <div class="overview-row">
                            <div class="overview-main">
                                <div class="overview-icon" style="background:linear-gradient(135deg,#7b2ff2 0%,#9d36e6 100%);"><i class="fas fa-location-dot" aria-hidden="true"></i></div>
                                <div>
                                    <span class="overview-name" x-text="district.name"></span>
                                    <span class="overview-meta" x-text="district.region_name"></span>
                                </div>
                            </div>
                            <div class="overview-value" x-text="district.schools_count + ' schools'"></div>
                        </div>
                    </template>

                    <template x-if="districts.length === 0">
                        <div class="empty-state">No districts have been added yet.</div>
                    </template>
                </div>

                <a href="/admin/registration/districts" class="panel-link">
                    <span>View all districts</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Quick Actions</h2>
                        <p class="panel-subtitle">Move directly into the most common registration administration tasks.</p>
                    </div>
                </div>

                <div class="action-list">
                    <a href="/admin/registration/regions" style="background:linear-gradient(135deg,#2453c9 0%,#2e67dd 100%);">
                        <div class="action-icon"><i class="fas fa-globe-africa" aria-hidden="true"></i></div>
                        <span>Add Region</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="/admin/registration/schools" style="background:linear-gradient(135deg,#178d45 0%,#1fa34c 100%);">
                        <div class="action-icon"><i class="fas fa-school" aria-hidden="true"></i></div>
                        <span>Add School</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="/admin/registration/candidates" style="background:linear-gradient(135deg,#d5530b 0%,#eb6209 100%);">
                        <div class="action-icon"><i class="fas fa-user-plus" aria-hidden="true"></i></div>
                        <span>Register Candidate</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="/admin/registration/candidates-by-district" style="background:linear-gradient(135deg,#7b2ff2 0%,#9d36e6 100%);">
                        <div class="action-icon"><i class="fas fa-file-import" aria-hidden="true"></i></div>
                        <span>Import by District</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
function registrationDashboard() {
    return {
        regions: [],
        districts: [],
        candidates: [],
        statistics: { regions: 0, districts: 0, schools: 0, candidates: 0 },
        examTypeStats: { PSLE: 0, CSEE: 0, ACSEE: 0 },

        async init() {
            await Promise.all([
                this.loadRegions(),
                this.loadDistricts(),
                this.loadCandidates()
            ]);

            await this.calculateStats();
        },

        async loadRegions() {
            try {
                const response = await fetch('/admin/api/regions');
                const data = await response.json();
                this.regions = data.data || [];
            } catch (error) {
                console.error('Error loading regions:', error);
            }
        },

        async loadDistricts() {
            try {
                const response = await fetch('/admin/api/districts');
                const data = await response.json();
                this.districts = data.data || [];
            } catch (error) {
                console.error('Error loading districts:', error);
            }
        },

        async loadCandidates() {
            try {
                const response = await fetch('/admin/api/candidates');
                const data = await response.json();
                this.candidates = data.data || [];
            } catch (error) {
                console.error('Error loading candidates:', error);
            }
        },

        async calculateStats() {
            this.statistics.regions = this.regions.length;
            this.statistics.districts = this.districts.length;
            this.statistics.candidates = this.candidates.length;

            try {
                const schoolResponse = await fetch('/admin/api/schools');
                const schoolData = await schoolResponse.json();
                this.statistics.schools = (schoolData.data || []).length;
            } catch (error) {
                console.error('Error loading schools:', error);
                this.statistics.schools = 0;
            }

            this.examTypeStats = {
                PSLE: this.candidates.filter(c => c.exam_type === 'PSLE').length,
                CSEE: this.candidates.filter(c => c.exam_type === 'CSEE').length,
                ACSEE: this.candidates.filter(c => c.exam_type === 'ACSEE').length,
            };

            document.dispatchEvent(new CustomEvent('statsUpdated', { detail: this.statistics }));
        },

        selectRegion(region) {
            window.location.href = '/admin/registration/districts?region=' + region.id;
        }
    };
}
</script>
@endsection
