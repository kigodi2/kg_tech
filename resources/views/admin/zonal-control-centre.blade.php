<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonal Control Centre - Secretariat</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <style>
        :root{--tz-green:#1EB53A;--tz-yellow:#FCD116;--tz-blue:#00A3DD;--tz-bg:#0f1117;--tz-card:#101518;--tz-text:#f0f4f7;--tz-muted:#9ca3af;--tz-border:rgba(255,255,255,.08);--tz-gold:#BBA45E;}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Maiandra GD','Segoe UI',sans-serif;background:linear-gradient(180deg,#0d1b2a,#11202e);color:var(--tz-text);overflow-x:hidden}
        .zone-shell{display:flex;min-height:100vh;background:linear-gradient(180deg,#0d1b2a,#11202e)}
        .zone-sidebar{
            width:280px;
            background:linear-gradient(180deg,#0d1b2a,#11202e);
            border-right:1px solid rgba(187,164,94,.18);
            display:flex;
            flex-direction:column;
            position:fixed;
            top:0;
            left:0;
            height:100vh;
            z-index:100;
            box-shadow:16px 0 40px rgba(0,0,0,.22);
        }
        .zone-brand{
            padding:24px;
            border-bottom:1px solid rgba(187,164,94,.15);
            background:linear-gradient(135deg,rgba(187,164,94,.08),transparent);
        }
        .zone-brand-title{font-size:1.15rem;font-weight:800;letter-spacing:.02em;color:#f0e6c8}
        .zone-brand-sub{font-size:.68rem;color:var(--tz-yellow);font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-top:6px}
        .zone-nav{padding:18px 12px;display:flex;flex-direction:column;gap:6px;flex:1}
        .zone-nav a{
            display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;
            color:rgba(255,255,255,.68);text-decoration:none;font-size:.9rem;font-weight:600;transition:all .18s ease;
        }
        .zone-nav a:hover{background:rgba(187,164,94,.12);color:#f0e6c8;transform:translateX(2px)}
        .zone-nav i{
            width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;
            background:rgba(255,255,255,.06);font-size:.82rem;flex-shrink:0;
        }
        .zone-nav a:hover i{background:rgba(187,164,94,.2);color:var(--tz-gold)}
        .zone-sidebar-foot{padding:18px;border-top:1px solid rgba(187,164,94,.15)}
        .zone-back{
            display:flex;align-items:center;justify-content:center;gap:8px;width:100%;
            padding:11px 14px;border-radius:10px;text-decoration:none;
            border:1px solid rgba(187,164,94,.18);background:rgba(255,255,255,.03);color:var(--tz-text);
            transition:all .18s ease;font-size:.86rem;font-weight:700;
        }
        .zone-back:hover{background:rgba(187,164,94,.12);color:#f0e6c8;transform:translateY(-1px)}
        .zone-main{flex:1;margin-left:280px;min-width:0}
        .wrap{max-width:1540px;margin:0;padding:26px 26px 26px 30px}
        .top{
            display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;
            background:rgba(15,17,23,.92);border:1px solid rgba(187,164,94,.16);border-radius:18px;padding:18px 22px;
            box-shadow:0 16px 40px rgba(0,0,0,.22);
        }
        .title{font-size:1.55rem;font-weight:800;letter-spacing:.3px;color:#f0e6c8}
        .subtitle{font-size:.86rem;color:var(--tz-muted);margin-top:4px}
        .btn{
            display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid rgba(187,164,94,.18);border-radius:10px;
            color:var(--tz-text);text-decoration:none;background:rgba(255,255,255,.03);transition:all .18s ease;
        }
        .btn:hover{background:rgba(187,164,94,.12);color:#f0e6c8;transform:translateY(-1px)}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
        .card{
            background:linear-gradient(160deg, rgba(255,255,255,.03), rgba(255,255,255,.01));
            border:1px solid rgba(255,255,255,.06);
            border-radius:14px;
            padding:18px;
            position:relative;
            overflow:hidden;
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .card:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(0,0,0,.5);border-color:rgba(187,164,94,.18)}
        .card::after{
            content:'';
            position:absolute;
            right:-20px;
            bottom:-20px;
            width:72px;
            height:72px;
            border-radius:50%;
            background:rgba(255,255,255,.03);
        }
        .stat-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
        .stat-k{font-size:.69rem;color:var(--tz-muted);text-transform:uppercase;letter-spacing:1px;font-weight:700}
        .stat-v{font-size:1.95rem;font-weight:900;margin-top:8px;letter-spacing:-.6px}
        .stat-icon{
            width:34px;
            height:34px;
            border-radius:10px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:.9rem;
            flex-shrink:0;
        }
        .card.regions{background:linear-gradient(135deg,#111416,#161b1f);border-color:rgba(0,163,221,.15)}
        .card.districts{background:linear-gradient(135deg,#003d52,#004f6b)}
        .card.schools{background:linear-gradient(135deg,#0a3012,#0e3d17)}
        .card.candidates{background:linear-gradient(135deg,#3a2e00,#453600)}
        .card.regions .stat-icon{background:rgba(0,163,221,.18);color:#67d8ff}
        .card.districts .stat-icon{background:rgba(252,209,22,.18);color:#FCD116}
        .card.schools .stat-icon{background:rgba(30,181,58,.18);color:#6ae086}
        .card.candidates .stat-icon{background:rgba(187,164,94,.18);color:#f0e6c8}
        .panel{
            background:var(--tz-card);
            border:1px solid rgba(255,255,255,.06);
            border-radius:14px;
            margin-bottom:18px;
            overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,.2);
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .panel:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(0,0,0,.5);border-color:rgba(187,164,94,.16)}
        .panel-h{padding:16px 18px;border-bottom:1px solid rgba(255,255,255,.04);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .panel-t{font-size:1rem;font-weight:700}
        .section-search{display:flex;align-items:center;gap:10px;margin-left:auto;width:auto;max-width:620px;flex:1 1 520px;justify-content:flex-end;flex-wrap:wrap}
        .section-search .search-box{position:relative;flex:1 1 280px;min-width:240px;}
        .section-search .search-box i{
            position:absolute;left:10px;top:50%;transform:translateY(-50%);
            font-size:.75rem;color:var(--tz-muted)
        }
        .section-search input{
            width:100%;
            min-width:240px;
            height:42px;
            padding:0 12px 0 32px;
            border-radius:12px;
            border:1px solid rgba(255,255,255,.08);
            background:rgba(255,255,255,.03);
            color:var(--tz-text);
            font-size:.84rem;
            outline:none;
            transition:border-color .18s ease,box-shadow .18s ease,background .18s ease;
        }
        .section-search input:focus{border-color:rgba(0,163,221,.45);box-shadow:0 0 0 3px rgba(0,163,221,.12);background:rgba(255,255,255,.045)}
        .section-search .search-btn,.section-search .clear-btn{
            height:42px;padding:0 16px;border-radius:12px;border:1px solid rgba(255,255,255,.08);
            text-decoration:none;display:inline-flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:700;
            transition:all .18s ease;white-space:nowrap;flex:0 0 auto;
        }
        .section-search .search-btn{background:linear-gradient(135deg,rgba(0,163,221,.22),rgba(0,163,221,.34));color:#a8ecff}
        .section-search .clear-btn{background:rgba(255,255,255,.04);color:var(--tz-text)}
        .section-search .search-btn:hover,.section-search .clear-btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,0,0,.22)}
        .alerts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:14px}
        .alert{
            border:1px solid var(--tz-border);
            border-radius:12px;
            padding:14px;
            background:linear-gradient(160deg, rgba(255,255,255,.02), rgba(255,255,255,.01));
            transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .alert:hover{transform:translateY(-2px);border-color:rgba(187,164,94,.16);box-shadow:0 10px 24px rgba(0,0,0,.28)}
        .alert-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}
        .sev{
            display:inline-flex;
            align-items:center;
            gap:6px;
            font-size:.66rem;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:1px;
            padding:4px 8px;
            border-radius:999px;
            border:1px solid transparent;
        }
        .sev.critical{color:#fca5a5;background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3)}
        .sev.warning{color:#FCD116;background:rgba(252,209,22,.14);border-color:rgba(252,209,22,.28)}
        .sev.info{color:#67d8ff;background:rgba(0,163,221,.14);border-color:rgba(0,163,221,.28)}
        .alert-title{font-size:.96rem;font-weight:700;line-height:1.35;margin-bottom:4px;color:#f7fafc}
        .alert-icon{
            width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;
            background:rgba(255,255,255,.05);color:rgba(255,255,255,.7);flex-shrink:0
        }
        .aval{font-size:1.55rem;font-weight:900;margin:2px 0 4px;letter-spacing:-.4px}
        .adesc{font-size:.8rem;color:var(--tz-muted);line-height:1.45}
        .table-wrap{
            width:100%;
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }
        table{width:100%;border-collapse:collapse;min-width:720px}
        th,td{padding:11px 12px;border-bottom:1px solid rgba(255,255,255,.06);text-align:center}
        .text-left{ text-align:left !important; }
        th{font-size:.73rem;color:var(--tz-muted);text-transform:uppercase;letter-spacing:.8px}
        td{font-size:.88rem;transition:background .15s ease}
        tbody tr:hover td{background:rgba(187,164,94,.05)}
        .pwrap{padding:14px 16px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;overflow-x:auto}
        .pmeta{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .meta-chip{
            display:inline-flex;align-items:center;gap:8px;min-height:32px;padding:0 12px;border-radius:999px;
            border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.03);font-size:.82rem;color:var(--tz-text);white-space:nowrap
        }
        .meta-chip i{color:var(--tz-muted);font-size:.76rem}
        .pager{display:flex;align-items:center;gap:8px;list-style:none;min-width:max-content}
        .pager-btn,.pager-num{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:38px;
            height:38px;
            padding:0 13px;
            border-radius:12px;
            border:1px solid rgba(255,255,255,.08);
            text-decoration:none;
            color:var(--tz-text);
            font-size:.84rem;
            font-weight:700;
            background:rgba(255,255,255,.02);
            transition:all .18s ease;
            white-space:nowrap;
        }
        .pager-btn:hover,.pager-num:hover{background:rgba(187,164,94,.12);color:#f0e6c8;transform:translateY(-1px);box-shadow:0 10px 24px rgba(0,0,0,.24)}
        .pager-btn.disabled{opacity:.35;pointer-events:none}
        .pager-num.active{background:var(--tz-blue);border-color:var(--tz-blue);box-shadow:0 10px 22px rgba(0,163,221,.28)}
        @media (max-width:1100px){.stats{grid-template-columns:repeat(2,minmax(0,1fr));}.alerts{grid-template-columns:1fr}}
        @media (max-width:900px){
            .zone-sidebar{position:static;width:100%;height:auto;box-shadow:none;border-right:none;border-bottom:1px solid rgba(187,164,94,.18)}
            .zone-main{margin-left:0}
            .zone-nav{padding-bottom:12px}
            .panel-h{flex-direction:column;align-items:stretch;gap:10px}
            .section-search{flex-wrap:wrap}
            .section-search .search-box{width:100%}
            .section-search input{min-width:0}
        }
        @media (max-width:680px){
            .wrap{padding:16px}
            .stats{grid-template-columns:1fr}
            .title{font-size:1.25rem}
            .subtitle{font-size:.8rem}
            .btn,.zone-back{width:100%;justify-content:center}
            .section-search .search-btn,.section-search .clear-btn{flex:1 1 140px}
            th,td{padding:10px}
        }
    </style>
</head>
<body>
@php
    $mockPortalManual = [
        'manualId' => 'secretariatDashboardManual',
        'manualTitle' => 'Secretariat Portal Guide',
        'manualSummary' => 'This guide helps Secretariat users monitor the wider mock-registration operation across regions, districts, schools, and candidate trends.',
        'manualPdf' => '/system_overview.pdf',
        'manualSteps' => [
            ['title' => 'Review the overall control-centre summary', 'body' => 'Use the top dashboard totals to understand current regional, district, school, and candidate coverage before drilling into details.'],
            ['title' => 'Inspect regional and school tables carefully', 'body' => 'Use the available search and pagination tools to review the right region, district, or school before making conclusions from the data.'],
            ['title' => 'Track missing accounts and low activity', 'body' => 'Use counts and control-centre panels to identify schools without headteacher accounts or areas lagging behind in registration progress.'],
            ['title' => 'Use the portal for monitoring and escalation', 'body' => 'Secretariat users should use this space to supervise the national picture, escalate gaps, and coordinate follow-up with RAOs and DAOs.'],
            ['title' => 'Use downloadable guidance for support', 'body' => 'Share the general portal guide with field users when they need standardized instructions for login, registration, upload, and correction workflows.'],
        ],
        'manualNotes' => [
            '<strong>Important:</strong> Secretariat oversight should focus on consistency, accountability, and timely escalation of blocked areas.',
            '<strong>Download option:</strong> Use the system overview PDF for wider coordination and support.'
        ],
    ];
@endphp
    @php
        $buildVisiblePages = function ($paginator, int $radius = 1) {
            $current = max(1, (int) $paginator->currentPage());
            $last = max(1, (int) $paginator->lastPage());
            $start = max(1, $current - $radius);
            $end = min($last, $current + $radius);

            if (($end - $start) < ($radius * 2)) {
                $start = max(1, min($start, $last - ($radius * 2)));
                $end = min($last, max($end, 1 + ($radius * 2)));
            }

            return range($start, $end);
        };
    @endphp
    <div class="zone-shell">
        <aside class="zone-sidebar">
            <div class="zone-brand">
                <div class="zone-brand-title">ZONAL CONTROL CENTRE</div>
                <div class="zone-brand-sub">Secretariat Workspace</div>
            </div>
            <nav class="zone-nav">
                <a href="#priority-alerts"><i class="fas fa-bell"></i><span>Priority Alerts</span></a>
                <a href="#regional-followup"><i class="fas fa-map"></i><span>Regional Follow-up</span></a>
                <a href="#district-followup"><i class="fas fa-map-location-dot"></i><span>District Follow-up</span></a>
                <a href="#school-followup"><i class="fas fa-school"></i><span>School Follow-up</span></a>
            </nav>
            <div class="zone-sidebar-foot">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="zone-back"><i class="fas fa-right-from-bracket"></i> Logout</button>
                </form>
            </div>
        </aside>

        <main class="zone-main">
        <div class="wrap">
        <div class="top">
            <div>
                <div class="title">ZONAL CONTROL CENTRE</div>
                <div class="subtitle">Secretariat follow-up hub for Regions, Districts, Schools, and registration progress</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn"><i class="fas fa-right-from-bracket"></i> Logout</button>
            </form>
        </div>

        <div class="stats">
            <div class="card regions">
                <div class="stat-top">
                    <div class="stat-k">Regions</div>
                    <div class="stat-icon"><i class="fas fa-map"></i></div>
                </div>
                <div class="stat-v">{{ number_format($stats['regions']) }}</div>
            </div>
            <div class="card districts">
                <div class="stat-top">
                    <div class="stat-k">Districts</div>
                    <div class="stat-icon"><i class="fas fa-map-location-dot"></i></div>
                </div>
                <div class="stat-v">{{ number_format($stats['districts']) }}</div>
            </div>
            <div class="card schools">
                <div class="stat-top">
                    <div class="stat-k">Primary Schools</div>
                    <div class="stat-icon"><i class="fas fa-school"></i></div>
                </div>
                <div class="stat-v">{{ number_format($stats['schools']) }}</div>
            </div>
            <div class="card candidates">
                <div class="stat-top">
                    <div class="stat-k">Registered Candidates</div>
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                </div>
                <div class="stat-v">{{ number_format($stats['candidates']) }}</div>
            </div>
        </div>

        <section class="panel" id="priority-alerts">
            <div class="panel-h"><div class="panel-t"><i class="fas fa-bell"></i> Secretariat Priority Alerts</div></div>
            <div class="alerts">
                @foreach($alerts as $alert)
                    <article class="alert">
                        <div class="alert-top">
                            <div class="sev {{ strtolower($alert['severity']) }}">
                                <i class="fas {{ strtolower($alert['severity']) === 'critical' ? 'fa-triangle-exclamation' : (strtolower($alert['severity']) === 'warning' ? 'fa-circle-exclamation' : 'fa-circle-info') }}"></i>
                                {{ $alert['severity'] }}
                            </div>
                            <div class="alert-icon">
                                <i class="fas {{ strtolower($alert['severity']) === 'critical' ? 'fa-shield-halved' : (strtolower($alert['severity']) === 'warning' ? 'fa-bell' : 'fa-chart-line') }}"></i>
                            </div>
                        </div>
                        <div class="alert-title">{{ $alert['title'] }}</div>
                        <div class="aval">{{ number_format($alert['value']) }}</div>
                        <div class="adesc">{{ $alert['details'] ?: 'No additional details.' }}</div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="panel" id="regional-followup">
            <div class="panel-h">
                <div class="panel-t"><i class="fas fa-map"></i> Regional Follow-up</div>
                <form method="GET" class="section-search">
                    <input type="hidden" name="district_search" value="{{ $districtSearch }}">
                    <input type="hidden" name="school_search" value="{{ $schoolSearch }}">
                    <input type="hidden" name="district_page" value="{{ request('district_page', 1) }}">
                    <input type="hidden" name="school_page" value="{{ request('school_page', 1) }}">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="region_search" value="{{ $regionSearch }}" placeholder="Search region or code">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                    @if($regionSearch !== '')
                        <a href="{{ request()->fullUrlWithQuery(['region_search' => null, 'region_page' => null]) }}" class="clear-btn">Clear</a>
                    @endif
                </form>
            </div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th class="text-left">Region</th><th>Districts</th><th>Primary Schools</th><th>RAOs</th><th>DAOs</th></tr></thead>
                <tbody>
                @forelse($regions as $region)
                    <tr>
                        <td>{{ ($regions->firstItem() ?? 1) + $loop->index }}</td>
                        <td class="text-left">{{ $region->name }}</td>
                        <td>{{ number_format($region->districts_count) }}</td>
                        <td>{{ number_format($region->primary_schools_count) }}</td>
                        <td>{{ number_format($region->rao_count) }}</td>
                        <td>{{ number_format($region->dao_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No regional data available.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div class="pwrap">
                <div class="pmeta">
                    <span class="meta-chip"><i class="fas fa-layer-group"></i> Page {{ $regions->currentPage() }} of {{ $regions->lastPage() }}</span>
                    <span class="meta-chip"><i class="fas fa-table-list"></i> Showing {{ $regions->firstItem() ?? 0 }} to {{ $regions->lastItem() ?? 0 }} of {{ $regions->total() }} results</span>
                </div>
                <div class="pager">
                    <a href="{{ $regions->onFirstPage() ? '#' : $regions->previousPageUrl() }}" class="pager-btn {{ $regions->onFirstPage() ? 'disabled' : '' }}"><i class="fas fa-chevron-left"></i></a>
                    @foreach($buildVisiblePages($regions) as $p)
                        <a href="{{ $regions->url($p) }}" class="pager-num {{ $regions->currentPage() === $p ? 'active' : '' }}">{{ $p }}</a>
                    @endforeach
                    <a href="{{ $regions->hasMorePages() ? $regions->nextPageUrl() : '#' }}" class="pager-btn {{ $regions->hasMorePages() ? '' : 'disabled' }}"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </section>

        <section class="panel" id="district-followup">
            <div class="panel-h">
                <div class="panel-t"><i class="fas fa-map-location-dot"></i> District Follow-up</div>
                <form method="GET" class="section-search">
                    <input type="hidden" name="region_search" value="{{ $regionSearch }}">
                    <input type="hidden" name="school_search" value="{{ $schoolSearch }}">
                    <input type="hidden" name="region_page" value="{{ request('region_page', 1) }}">
                    <input type="hidden" name="school_page" value="{{ request('school_page', 1) }}">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="district_search" value="{{ $districtSearch }}" placeholder="Search district, code or region">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                    @if($districtSearch !== '')
                        <a href="{{ request()->fullUrlWithQuery(['district_search' => null, 'district_page' => null]) }}" class="clear-btn">Clear</a>
                    @endif
                </form>
            </div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th class="text-left">District</th><th class="text-left">Region</th><th>Primary Schools</th><th>Candidates</th></tr></thead>
                <tbody>
                @forelse($districts as $district)
                    <tr>
                        <td>{{ ($districts->firstItem() ?? 1) + $loop->index }}</td>
                        <td class="text-left">{{ $district->name }}</td>
                        <td class="text-left">{{ $district->region->name ?? 'N/A' }}</td>
                        <td>{{ number_format($district->primary_schools_count) }}</td>
                        <td>{{ number_format($district->candidates_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No district data available.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div class="pwrap">
                <div class="pmeta">
                    <span class="meta-chip"><i class="fas fa-layer-group"></i> Page {{ $districts->currentPage() }} of {{ $districts->lastPage() }}</span>
                    <span class="meta-chip"><i class="fas fa-table-list"></i> Showing {{ $districts->firstItem() ?? 0 }} to {{ $districts->lastItem() ?? 0 }} of {{ $districts->total() }} results</span>
                </div>
                <div class="pager">
                    <a href="{{ $districts->onFirstPage() ? '#' : $districts->previousPageUrl() }}" class="pager-btn {{ $districts->onFirstPage() ? 'disabled' : '' }}"><i class="fas fa-chevron-left"></i></a>
                    @foreach($buildVisiblePages($districts) as $p)
                        <a href="{{ $districts->url($p) }}" class="pager-num {{ $districts->currentPage() === $p ? 'active' : '' }}">{{ $p }}</a>
                    @endforeach
                    <a href="{{ $districts->hasMorePages() ? $districts->nextPageUrl() : '#' }}" class="pager-btn {{ $districts->hasMorePages() ? '' : 'disabled' }}"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </section>

        <section class="panel" id="school-followup">
            <div class="panel-h">
                <div class="panel-t"><i class="fas fa-school"></i> School Follow-up</div>
                <form method="GET" class="section-search">
                    <input type="hidden" name="region_search" value="{{ $regionSearch }}">
                    <input type="hidden" name="district_search" value="{{ $districtSearch }}">
                    <input type="hidden" name="region_page" value="{{ request('region_page', 1) }}">
                    <input type="hidden" name="district_page" value="{{ request('district_page', 1) }}">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="school_search" value="{{ $schoolSearch }}" placeholder="Search school, code, district or region">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                    @if($schoolSearch !== '')
                        <a href="{{ request()->fullUrlWithQuery(['school_search' => null, 'school_page' => null]) }}" class="clear-btn">Clear</a>
                    @endif
                </form>
            </div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th class="text-left">Centre Number</th><th class="text-left">School</th><th class="text-left">Ownership</th><th class="text-left">District</th><th class="text-left">Region</th><th>Headteacher Account</th><th>Candidates</th></tr></thead>
                <tbody>
                @forelse($schools as $school)
                    <tr>
                        <td>{{ ($schools->firstItem() ?? 1) + $loop->index }}</td>
                        <td class="text-left">{{ $school->code ?: ($school->registration_number ?: 'N/A') }}</td>
                        <td class="text-left">{{ $school->name }}</td>
                        <td class="text-left">{{ $school->ownership ?? 'N/A' }}</td>
                        <td class="text-left">{{ $school->district->name ?? 'N/A' }}</td>
                        <td class="text-left">{{ $school->region->name ?? 'N/A' }}</td>
                        <td>{{ $school->headteacher_count > 0 ? 'Available' : 'Missing' }}</td>
                        <td>{{ number_format($school->candidates_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No school data available.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div class="pwrap">
                <div class="pmeta">
                    <span class="meta-chip"><i class="fas fa-layer-group"></i> Page {{ $schools->currentPage() }} of {{ $schools->lastPage() }}</span>
                    <span class="meta-chip"><i class="fas fa-table-list"></i> Showing {{ $schools->firstItem() ?? 0 }} to {{ $schools->lastItem() ?? 0 }} of {{ $schools->total() }} results</span>
                </div>
                <div class="pager">
                    <a href="{{ $schools->onFirstPage() ? '#' : $schools->previousPageUrl() }}" class="pager-btn {{ $schools->onFirstPage() ? 'disabled' : '' }}"><i class="fas fa-chevron-left"></i></a>
                    @foreach($buildVisiblePages($schools) as $p)
                        <a href="{{ $schools->url($p) }}" class="pager-num {{ $schools->currentPage() === $p ? 'active' : '' }}">{{ $p }}</a>
                    @endforeach
                    <a href="{{ $schools->hasMorePages() ? $schools->nextPageUrl() : '#' }}" class="pager-btn {{ $schools->hasMorePages() ? '' : 'disabled' }}"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </section>
    </div>
    </main>
    </div>
@if(request()->routeIs('mock-portal.secretariat.dashboard'))
    @include('mock-portal.partials.user-manual', $mockPortalManual)
@endif
</body>
</html>
