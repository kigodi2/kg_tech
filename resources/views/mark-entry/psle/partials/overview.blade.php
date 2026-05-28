                <div class="adm-breadcrumb">
                    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>PSLE</span>
                </div>

                <div class="adm-page-header">
                    <h1 class="adm-page-title">PSLE Mark Entry Portal</h1>
                    <p class="adm-page-desc">Manage PSLE subject-wise mark entry, validation, missing marks, outliers, review, submission, and reports.</p>
                </div>

                <!-- Summary Cards -->
                <div class="adm-stats">
                    <div class="adm-stat">
                        <div class="adm-stat-label">TASIDO Regions</div>
                        <div class="adm-stat-value" style="color: #fff;">{{ isset($isAdmin) && $isAdmin ? '4' : '1' }}</div>
                        <i class="fas fa-map-location-dot adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Registered Candidates</div>
                        <div class="adm-stat-value" style="color: var(--tz-green);">{{ number_format($candidateCount ?? 0) }}</div>
                        <i class="fas fa-users adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">PSLE Subjects</div>
                        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ count($psleSubjects ?? []) }}</div>
                        <i class="fas fa-book-open adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Marks Entered</div>
                        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ number_format($enteredMarksCount ?? 0) }}</div>
                        <i class="fas fa-check-double adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Missing Marks</div>
                        <div class="adm-stat-value" style="color: #ff7b7b;">{{ number_format($missingMarksCount ?? 0) }}</div>
                        <i class="fas fa-circle-exclamation adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Outliers / Extreme</div>
                        <div class="adm-stat-value" style="color: #ffb74d;">{{ number_format($outlierCount ?? 0) }}</div>
                        <i class="fas fa-chart-line adm-stat-icon"></i>
                    </div>
                </div>

                <!-- Context Filters -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Mark Entry Scope</div>
                    </div>
                    <form method="GET" action="{{ url()->current() }}" class="adm-filters">
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Exam Year</label>
                            <select name="exam_year_id" class="adm-select" onchange="this.form.submit()">
                                @foreach($examYears ?? [] as $yr)
                                    <option value="{{ $yr->id }}" {{ ($activeFilters['exam_year_id'] ?? '') == $yr->id ? 'selected' : '' }}>{{ $yr->year_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Region</label>
                            <select name="region_id" class="adm-select" onchange="this.form.submit()" {{ !empty($allowedRegionId) ? 'disabled' : '' }}>
                                @if(empty($allowedRegionId))
                                    <option value="">All Regions</option>
                                @endif
                                @foreach($regions ?? [] as $reg)
                                    <option value="{{ $reg->id }}" {{ ($activeFilters['region_id'] ?? '') == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                                @endforeach
                            </select>
                            @if(!empty($allowedRegionId))
                                <input type="hidden" name="region_id" value="{{ $allowedRegionId }}">
                            @endif
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">District / Council</label>
                            <select name="district_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Districts</option>
                                @foreach($districts ?? [] as $dist)
                                    <option value="{{ $dist->id }}" {{ ($activeFilters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Primary School</label>
                            <select name="school_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Schools</option>
                                @foreach($schools ?? [] as $sch)
                                    <option value="{{ $sch->id }}" {{ ($activeFilters['school_id'] ?? '') == $sch->id ? 'selected' : '' }}>{{ $sch->code }} - {{ $sch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Subject</label>
                            <select name="subject_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                @foreach($psleSubjects as $subj)
                                    <option value="{{ $subj->id }}" {{ ($activeFilters['subject_id'] ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->code }} - {{ $subj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group" style="display:flex; align-items:flex-end; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex:1; height:40px;"><i class="fas fa-filter"></i> Apply</button>
                            <a href="{{ url()->current() }}" class="btn btn-outline" style="height:40px;" title="Reset Filters"><i class="fas fa-undo"></i></a>
                        </div>
                    </form>
                </div>

                @php
                    $panelReturnedMarks = $returnedPanelMarks ?? collect();
                @endphp
                @if($panelReturnedMarks->isNotEmpty())
                <div class="adm-card" style="border-color:rgba(251,146,60,.25);">
                    <div class="adm-card-head">
                        <div class="adm-card-title"><i class="fas fa-rotate-left" style="color:#fb923c;"></i> Returned for Correction</div>
                        <span class="badge badge-yellow">{{ $panelReturnedMarks->count() }} recent</span>
                    </div>
                    <div class="adm-card-body table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Index No.</th>
                                    <th>Candidate</th>
                                    <th>School</th>
                                    <th>Subject</th>
                                    <th>Returned By</th>
                                    <th>Returned Date</th>
                                    <th>Correction Reason</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($panelReturnedMarks as $returned)
                                @php
                                    $raw = $returned->rawMark;
                                    $rawBatch = $raw ? $raw->batch : null;
                                    $rawSubject = $raw ? $raw->subject : null;
                                @endphp
                                <tr>
                                    <td style="font-family:monospace;color:var(--tz-yellow);">{{ $raw->candidate_index_number ?? '—' }}</td>
                                    <td>{{ $raw->full_name ?? '—' }}</td>
                                    <td>{{ $rawBatch->school->name ?? '—' }}</td>
                                    <td>{{ $rawSubject->name ?? '—' }}</td>
                                    <td>{{ $returned->verifiedBy?->name ?? 'Panel Leader' }}</td>
                                    <td>{{ $returned->returned_at?->format('d M Y H:i') ?? '—' }}</td>
                                    <td style="max-width:280px;color:#fbbf24;">{{ $returned->return_reason }}</td>
                                    <td class="text-right">
                                        @if($rawBatch)
                                            <a class="btn btn-action" href="{{ url('/mark-entry/psle?view=entry-sheet&school_id=' . $rawBatch->school_id . '&subject_id=' . $raw->subject_id . '&exam_year_id=' . $returned->exam_year_id) }}">Correct</a>
                                        @else
                                            <span class="badge badge-outline">Unavailable</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            <!-- Quick Actions -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">Quick Actions</div>
                </div>
                <div class="adm-card-body">
                    <div class="qa-grid">
                        @if(!$isAdmin && !$isReo)
                        <a href="{{ url('/mark-entry/psle?view=start-entry') }}" class="qa-item">
                            <i class="fas fa-keyboard"></i>
                            <span class="qa-item-title">Start Mark Entry</span>
                        </a>
                        <a href="{{ url('/mark-entry/psle?view=bulk-import') }}" class="qa-item">
                            <i class="fas fa-file-csv"></i>
                            <span class="qa-item-title">Bulk Import</span>
                        </a>
                        @endif
                        @if(isset($isAdmin) && $isAdmin)
                        <a href="{{ url('/mark-entry/psle?view=user-management') }}" class="qa-item">
                            <i class="fas fa-users-cog"></i>
                            <span class="qa-item-title">Manage Users</span>
                        </a>
                        <a href="{{ route('mark-entry.psle.subject-panel-assignments.index') }}" class="qa-item">
                            <i class="fas fa-user-shield"></i>
                            <span class="qa-item-title">Subject Panel Assignments</span>
                        </a>
                        @endif

                        @if(isset($isAdmin) && $isAdmin || (isset($isReo) && $isReo))
                        <a href="{{ url('/mark-entry/psle?view=assignments') }}" class="qa-item">
                            <i class="fas fa-user-check"></i>
                            <span class="qa-item-title">Manage Assignments</span>
                        </a>
                        @endif

                        <a href="{{ url('/mark-entry/psle?view=missing-marks') }}" class="qa-item">
                            <i class="fas fa-search-minus"></i>
                            <span class="qa-item-title">Missing Marks</span>
                        </a>
                        <a href="{{ url('/mark-entry/psle?view=validation-errors') }}" class="qa-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="qa-item-title">Validation Errors</span>
                        </a>
                        <a href="{{ url('/mark-entry/psle?view=outliers') }}" class="qa-item">
                            <i class="fas fa-chart-line"></i>
                            <span class="qa-item-title">Outliers / Extreme</span>
                        </a>
                        <a href="{{ url('/mark-entry/psle?view=reports') }}" class="qa-item">
                            <i class="fas fa-file-pdf"></i>
                            <span class="qa-item-title">Reports</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Live Performance Ranking Card -->
            <div class="adm-card" id="performance-ranking-card">
                <div class="adm-card-head" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="adm-card-title">
                        <i class="fas fa-trophy" style="color: var(--tz-yellow); margin-right: 8px;"></i>
                        Live Mark Entry Performance Ranking
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span id="ranking-updated-at" style="font-size: 0.72rem; color: var(--tz-text-muted);">Last updated: Loading...</span>
                        <span class="badge badge-blue" style="font-size: 0.68rem;">
                            <i class="fas fa-rotate fa-spin" style="margin-right: 4px;"></i> Live Poll: 60s
                        </span>
                    </div>
                </div>
                
                <div class="adm-card-body" style="padding: 20px;">
                    <!-- Top 3 Highlights -->
                    <div id="ranking-top-three" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                        <!-- Will be dynamically populated -->
                    </div>

                    <!-- Full Ranking Table -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th width="60" class="text-center">Rank</th>
                                    <th>Officer</th>
                                    <th class="text-center">Marks Entered</th>
                                    <th class="text-center">Progress / Contribution</th>
                                    <th class="text-center">Schools</th>
                                    <th class="text-center">Subjects</th>
                                    <th class="text-center">Last Activity</th>
                                    <th class="text-center">Movement</th>
                                </tr>
                            </thead>
                            <tbody id="ranking-table-body">
                                <!-- Will be dynamically populated -->
                            </tbody>
                        </table>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--tz-text-muted); margin-top: 15px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;">
                        <i class="fas fa-circle-info" style="margin-right: 4px;"></i> Ranking is based on successfully saved PSLE marks within the selected scope.
                    </div>
                </div>
            </div>

            <script>
            $(document).ready(function() {
                let previousRanks = new Map(); // Store user_id -> rank

                function fetchPerformanceRankings() {
                    const searchParams = new URLSearchParams(window.location.search);
                    // Ensure we preserve simulated role if present in the page URL
                    if (!searchParams.has('exam_year_id')) {
                        searchParams.set('exam_year_id', '{{ $activeFilters["exam_year_id"] ?? "" }}');
                    }
                    if (!searchParams.has('region_id')) {
                        searchParams.set('region_id', '{{ $activeFilters["region_id"] ?? "" }}');
                    }
                    if (!searchParams.has('district_id')) {
                        searchParams.set('district_id', '{{ $activeFilters["district_id"] ?? "" }}');
                    }
                    if (!searchParams.has('school_id')) {
                        searchParams.set('school_id', '{{ $activeFilters["school_id"] ?? "" }}');
                    }
                    if (!searchParams.has('subject_id')) {
                        searchParams.set('subject_id', '{{ $activeFilters["subject_id"] ?? "" }}');
                    }

                    // Show loading state if table body is empty
                    if ($('#ranking-table-body').children().length === 0) {
                        showRankingSkeleton();
                    }

                    $.ajax({
                        url: '/api/mark-entry/psle/performance-rankings?' + searchParams.toString(),
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                updateRankingUI(response.rankings);
                            } else {
                                showRankingError("Unable to refresh ranking. Retrying automatically.");
                            }
                        },
                        error: function() {
                            showRankingError("Unable to refresh ranking. Retrying automatically.");
                        }
                    });
                }

                function showRankingSkeleton() {
                    let skeletonHtml = '';
                    for (let i = 0; i < 3; i++) {
                        skeletonHtml += `
                            <tr class="ranking-skeleton-row">
                                <td class="text-center"><div style="height: 18px; width: 18px; background: rgba(255,255,255,0.05); border-radius: 50%; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 120px; background: rgba(255,255,255,0.05); border-radius: 4px; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 60px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 50px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 40px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 40px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 80px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                                <td><div style="height: 16px; width: 40px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 auto; animation: pulse 1.5s infinite;"></div></td>
                            </tr>
                        `;
                    }
                    $('#ranking-table-body').html(skeletonHtml);
                }

                function showRankingError(message) {
                    $('#ranking-updated-at').text(message).css('color', '#ff7b7b');
                }

                function updateRankingUI(rankings) {
                    // Last updated time
                    const now = new Date();
                    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    $('#ranking-updated-at').text('Last updated: ' + timeString).css('color', 'var(--tz-text-muted)');

                    if (!rankings || rankings.length === 0) {
                        $('#ranking-top-three').html('');
                        $('#ranking-table-body').html(`
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px; color: var(--tz-text-muted);">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash empty-icon"></i>
                                        <div class="empty-title">No Performance Activity</div>
                                        <div class="empty-desc">No performance activity has been recorded yet for this scope.</div>
                                    </div>
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    // 1. Populating Top 3 highlight panel
                    let topThreeHtml = '';
                    const topThree = rankings.slice(0, 3);
                    
                    const medals = {
                        1: { icon: 'fa-trophy', color: 'var(--tz-yellow)', border: 'rgba(252,209,22,0.22)', bg: 'rgba(252,209,22,0.04)', badge: 'Current Leader' },
                        2: { icon: 'fa-medal', color: '#c0c0c0', border: 'rgba(192,192,192,0.18)', bg: 'rgba(192,192,192,0.03)', badge: 'Top Performer' },
                        3: { icon: 'fa-medal', color: '#cd7f32', border: 'rgba(205,127,50,0.18)', bg: 'rgba(205,127,50,0.03)', badge: 'Top Performer' }
                    };

                    topThree.forEach(function(r) {
                        const medal = medals[r.rank];
                        const pctLabel = r.is_contribution ? 'Contribution' : 'Completion';
                        
                        topThreeHtml += `
                            <div style="background: ${medal.bg}; border: 1px solid ${medal.border}; border-radius: 12px; padding: 16px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; gap: 8px;">
                                <div style="position: absolute; right: 10px; bottom: -10px; font-size: 4.5rem; font-weight: 800; color: rgba(255,255,255,0.025); line-height: 1;">#${r.rank}</div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <span class="badge" style="background: ${medal.bg}; color: ${medal.color}; border: 1px solid ${medal.border}; font-size: 0.62rem; margin-bottom: 6px;">
                                            <i class="fas ${medal.icon}" style="margin-right: 4px;"></i> ${medal.badge}
                                        </span>
                                        <div style="font-weight: 800; font-size: 0.95rem; color: #fff; text-transform: uppercase;">${r.name}</div>
                                        <div style="font-size: 0.72rem; color: var(--tz-text-muted);">${r.region_name}</div>
                                    </div>
                                </div>
                                <div style="margin-top: 10px;">
                                    <div style="font-size: 1.45rem; font-weight: 800; color: #fff; line-height: 1.1;">
                                        ${r.marks_entered.toLocaleString()} <span style="font-size: 0.75rem; font-weight: 600; color: var(--tz-text-muted);">marks</span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--tz-text-muted); margin-top: 2px;">
                                        ${pctLabel}: <strong style="color: ${r.is_contribution ? 'var(--tz-blue)' : 'var(--tz-green)'};">${r.completion_percentage}%</strong>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#ranking-top-three').html(topThreeHtml);

                    // 2. Populating Table
                    let tableRowsHtml = '';
                    
                    rankings.forEach(function(r) {
                        const pctLabel = r.is_contribution ? 'Contribution' : 'Completion';
                        const pctBadge = r.is_contribution ? 'badge-blue' : 'badge-green';

                        // Calculate movement
                        let movementHtml = '';
                        const prevRank = previousRanks.get(r.user_id);
                        if (prevRank === undefined) {
                            movementHtml = `<span class="badge badge-blue" style="font-size:0.62rem;"><i class="fas fa-star" style="margin-right:2px;"></i> New</span>`;
                        } else if (r.rank < prevRank) {
                            const diff = prevRank - r.rank;
                            movementHtml = `<span style="color:var(--tz-green); font-weight:700;"><i class="fas fa-caret-up" style="margin-right:4px;"></i> Up ${diff}</span>`;
                        } else if (r.rank > prevRank) {
                            const diff = r.rank - prevRank;
                            movementHtml = `<span style="color:#ff7b7b; font-weight:700;"><i class="fas fa-caret-down" style="margin-right:4px;"></i> Down ${diff}</span>`;
                        } else {
                            movementHtml = `<span style="color:var(--tz-text-muted);">&mdash; Same</span>`;
                        }

                        // Save rank in map for next refresh
                        previousRanks.set(r.user_id, r.rank);

                        // Active badge logic
                        const lastActiveDisplay = r.last_activity_display;
                        let activeIndicator = lastActiveDisplay;
                        if (lastActiveDisplay.includes('second') || (lastActiveDisplay.includes('minute') && parseInt(lastActiveDisplay) < 5)) {
                            activeIndicator = `<span class="badge badge-green" style="font-size: 0.65rem;" title="${lastActiveDisplay}"><i class="fas fa-circle" style="font-size:0.45rem; animation: pulse 1.2s infinite; margin-right:4px;"></i> Active now</span>`;
                        }

                        tableRowsHtml += `
                            <tr data-user-id="${r.user_id}">
                                <td class="text-center" style="white-space: nowrap;">
                                    <span class="badge ${r.rank === 1 ? 'badge-yellow' : 'badge-outline'}" style="font-size: 0.75rem; border-radius: 50%; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800;">
                                        ${r.rank}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #fff; text-transform: uppercase;">${r.name}</div>
                                    <div style="font-size: 0.72rem; color: var(--tz-text-muted);">${r.region_name} &bull; ${r.role_label}</div>
                                </td>
                                <td class="text-center" style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: var(--tz-blue); white-space: nowrap;">
                                    ${r.marks_entered.toLocaleString()}
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <span class="badge ${pctBadge}" style="font-size: 0.7rem;">
                                        ${r.completion_percentage}% ${pctLabel}
                                    </span>
                                </td>
                                <td class="text-center" style="font-weight: 600;">${r.schools_touched}</td>
                                <td class="text-center" style="font-weight: 600;">${r.subjects_touched}</td>
                                <td class="text-center" style="font-size: 0.8rem; color: var(--tz-text-muted); white-space: nowrap;">
                                    ${activeIndicator}
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    ${movementHtml}
                                </td>
                            </tr>
                        `;
                    });
                    $('#ranking-table-body').html(tableRowsHtml);
                }

                // Initialize and start polling
                fetchPerformanceRankings();
                
                // 60-second polling interval
                const rankingInterval = setInterval(function() {
                    if (!document.hidden) {
                        fetchPerformanceRankings();
                    }
                }, 60000);

                // If tab visibility changes, fetch immediately when tab becomes active
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        fetchPerformanceRankings();
                    }
                });

                // Pulse animation keyframe definition (appended to document once)
                if (!$('#ranking-pulse-animation').length) {
                    $('<style id="ranking-pulse-animation">')
                        .prop('type', 'text/css')
                        .html(`
                            @keyframes pulse {
                                0% { opacity: 0.4; }
                                50% { opacity: 0.8; }
                                100% { opacity: 0.4; }
                            }
                        `)
                        .appendTo('head');
                }
            });
            </script>

            <!-- PSLE Subjects Section -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">PSLE Subjects Status</div>
                    <div class="adm-table-tools">
                        <div class="adm-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search subjects..." class="adm-search-input">
                        </div>
                    </div>
                </div>
                <div class="adm-card-body table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Subject</th>
                                <th class="text-center">Candidates</th>
                                <th class="text-center">Entered</th>
                                <th class="text-center">Missing</th>
                                <th class="text-center">Outliers</th>
                                <th class="text-center">Status</th>
                                @if(!$isAdmin && !$isReo)
                                <th class="text-right">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($psleSubjects as $subject)
                            @php
                                $stats = $subjectStats[$subject->id] ?? ['entered' => 0, 'missing' => 0, 'outliers' => 0];
                                $progress = $candidateCount > 0 ? ($stats['entered'] / $candidateCount) * 100 : 0;
                            @endphp
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">{{ $subject->code }}</td>
                                <td><strong>{{ $subject->name }}</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-blue);">{{ number_format($stats['entered']) }}</td>
                                <td class="text-center" style="color: #fca5a5;">{{ number_format($stats['missing']) }}</td>
                                <td class="text-center" style="color: #fde047;">{{ number_format($stats['outliers']) }}</td>
                                <td class="text-center">
                                    @if($progress >= 100)
                                        <span class="badge badge-green">Completed</span>
                                    @elseif($progress > 0)
                                        <span class="badge badge-yellow">In Progress</span>
                                    @else
                                        <span class="badge badge-blue">Pending</span>
                                    @endif
                                </td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right">
                                    <a href="{{ url('/mark-entry/psle?view=start-entry&subject_id=' . $subject->id . (isset($activeFilters['school_id']) ? '&school_id='.$activeFilters['school_id'] : '') . (isset($activeFilters['district_id']) ? '&district_id='.$activeFilters['district_id'] : '')) }}" class="btn btn-action" title="Go to Entry Sheet">Enter Marks</a>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">01</td>
                                <td><strong>Kiswahili</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-text-muted);">0</td>
                                <td class="text-center" style="color: #fca5a5;">0</td>
                                <td class="text-center" style="color: #fde047;">0</td>
                                <td class="text-center"><span class="badge badge-blue">Pending</span></td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right"><button class="btn btn-action disabled" title="Coming Next">Enter Marks</button></td>
                                @endif
                            </tr>
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">02</td>
                                <td><strong>English Language</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-text-muted);">0</td>
                                <td class="text-center" style="color: #fca5a5;">0</td>
                                <td class="text-center" style="color: #fde047;">0</td>
                                <td class="text-center"><span class="badge badge-blue">Pending</span></td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right"><button class="btn btn-action disabled" title="Coming Next">Enter Marks</button></td>
                                @endif
                            </tr>
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">03</td>
                                <td><strong>Maarifa ya Jamii</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-text-muted);">0</td>
                                <td class="text-center" style="color: #fca5a5;">0</td>
                                <td class="text-center" style="color: #fde047;">0</td>
                                <td class="text-center"><span class="badge badge-blue">Pending</span></td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right"><button class="btn btn-action disabled" title="Coming Next">Enter Marks</button></td>
                                @endif
                            </tr>
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">04</td>
                                <td><strong>Hisabati</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-text-muted);">0</td>
                                <td class="text-center" style="color: #fca5a5;">0</td>
                                <td class="text-center" style="color: #fde047;">0</td>
                                <td class="text-center"><span class="badge badge-blue">Pending</span></td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right"><button class="btn btn-action disabled" title="Coming Next">Enter Marks</button></td>
                                @endif
                            </tr>
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">05</td>
                                <td><strong>Sayansi na Teknolojia</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-text-muted);">0</td>
                                <td class="text-center" style="color: #fca5a5;">0</td>
                                <td class="text-center" style="color: #fde047;">0</td>
                                <td class="text-center"><span class="badge badge-blue">Pending</span></td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right"><button class="btn btn-action disabled" title="Coming Next">Enter Marks</button></td>
                                @endif
                            </tr>
                            <tr>
                                <td style="font-family: monospace; color: var(--tz-yellow);">06</td>
                                <td><strong>Uraia na Maadili</strong></td>
                                <td class="text-center">{{ number_format($candidateCount ?? 0) }}</td>
                                <td class="text-center" style="color: var(--tz-text-muted);">0</td>
                                <td class="text-center" style="color: #fca5a5;">0</td>
                                <td class="text-center" style="color: #fde047;">0</td>
                                <td class="text-center"><span class="badge badge-blue">Pending</span></td>
                                @if(!$isAdmin && !$isReo)
                                <td class="text-right"><button class="btn btn-action disabled" title="Coming Next">Enter Marks</button></td>
                                @endif
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Regional Progress Section -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">Regional Progress</div>
                </div>
                <div class="adm-card-body table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Region</th>
                                <th class="text-center">Schools</th>
                                <th class="text-center">Candidates</th>
                                <th class="text-center">Entered</th>
                                <th class="text-center">Missing</th>
                                <th class="text-center">Outliers</th>
                                <th>Progress</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overviewRegionalProgress ?? collect() as $rp)
                            @if(str_contains(strtoupper((string)($rp->region ?? '')), 'UNASSIGNED') || str_contains(strtoupper((string)($rp->region ?? '')), 'CSEE') || str_contains(strtoupper((string)($rp->region ?? '')), 'ACSEE'))
                                @continue
                            @endif
                            <tr>
                                <td><strong>{{ $rp->region }}</strong></td>
                                <td class="text-center">{{ number_format($rp->schools) }}</td>
                                <td class="text-center">{{ number_format($rp->candidates) }}</td>
                                <td class="text-center" style="color: var(--tz-green);">{{ number_format($rp->marks_entered) }}</td>
                                <td class="text-center" style="color: #fca5a5;">{{ number_format($rp->missing_marks) }}</td>
                                <td class="text-center" style="color: #fde047;">{{ number_format($rp->outliers) }}</td>
                                <td style="width: 150px;">
                                    <div style="background: rgba(255,255,255,0.1); border-radius: 4px; height: 6px; overflow: hidden;">
                                        <div style="width: {{ $rp->progress }}%; background: var(--tz-green); height: 100%;"></div>
                                    </div>
                                    <div style="font-size: 0.65rem; text-align: right; margin-top: 4px; color: var(--tz-text-muted);">{{ $rp->progress }}%</div>
                                </td>
                                <td class="text-center">
                                    @if($rp->status === 'Completed')
                                        <span class="badge badge-green">Completed</span>
                                    @elseif($rp->status === 'In Progress')
                                        <span class="badge badge-yellow">In Progress</span>
                                    @else
                                        <span class="badge badge-blue">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 32px; color: var(--tz-text-muted);">No regional progress data found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">Recent Activity Log</div>
                </div>
                <div class="adm-card-body">
                    @php
                        $activityIconMap = [
                            'mark_saved' => ['icon' => 'fa-pen', 'bg' => 'rgba(0,163,221,.12)', 'color' => 'var(--tz-blue)'],
                            'mark_updated' => ['icon' => 'fa-pen-to-square', 'bg' => 'rgba(0,163,221,.12)', 'color' => 'var(--tz-blue)'],
                            'mark_cleared' => ['icon' => 'fa-eraser', 'bg' => 'rgba(248,113,113,.12)', 'color' => '#f87171'],
                            'marks_imported' => ['icon' => 'fa-file-import', 'bg' => 'rgba(34,211,238,.12)', 'color' => '#22d3ee'],
                            'subject_submitted' => ['icon' => 'fa-paper-plane', 'bg' => 'rgba(251,191,36,.12)', 'color' => '#fbbf24'],
                            'marks_locked' => ['icon' => 'fa-lock', 'bg' => 'rgba(34,197,94,.12)', 'color' => '#22c55e'],
                            'validation_completed' => ['icon' => 'fa-circle-check', 'bg' => 'rgba(34,197,94,.12)', 'color' => '#22c55e'],
                            'validation_failed' => ['icon' => 'fa-triangle-exclamation', 'bg' => 'rgba(248,113,113,.12)', 'color' => '#f87171'],
                            'outlier_flagged' => ['icon' => 'fa-chart-line', 'bg' => 'rgba(250,204,21,.12)', 'color' => '#facc15'],
                            'missing_marks_detected' => ['icon' => 'fa-circle-exclamation', 'bg' => 'rgba(244,63,94,.12)', 'color' => '#fb7185'],
                            'moderation_action' => ['icon' => 'fa-user-shield', 'bg' => 'rgba(168,85,247,.12)', 'color' => '#c084fc'],
                        ];
                    @endphp

                    @forelse($recentActivities ?? collect() as $activity)
                        @php
                            $style = $activityIconMap[$activity->event_type] ?? ['icon' => 'fa-clipboard-list', 'bg' => 'rgba(148,163,184,.12)', 'color' => '#94a3b8'];
                            $roleLabel = $activity->user?->role?->name ?? $activity->user?->role?->code ?? $activity->user?->portal_role;
                        @endphp
                        <div style="display:flex; align-items:flex-start; gap:12px; border:1px solid rgba(255,255,255,.08); background:rgba(255,255,255,.035); border-radius:12px; padding:14px; margin-bottom:10px;">
                            <div style="height:36px; width:36px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:{{ $style['bg'] }}; color:{{ $style['color'] }}; flex-shrink:0;">
                                <i class="fas {{ $style['icon'] }}"></i>
                            </div>
                            <div style="min-width:0; flex:1;">
                                <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                    <div style="font-size:.92rem; font-weight:800; color:#fff;">{{ $activity->title }}</div>
                                    <div style="font-size:.72rem; color:var(--tz-text-muted); white-space:nowrap;">{{ $activity->created_at?->diffForHumans() }}</div>
                                </div>
                                @if($activity->description)
                                    <div style="margin-top:5px; font-size:.82rem; color:rgba(255,255,255,.66); line-height:1.45;">{{ $activity->description }}</div>
                                @endif
                                <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:7px; align-items:center; font-size:.72rem; color:var(--tz-text-muted);">
                                    <span>{{ $activity->user?->name ?? 'System' }}{{ $roleLabel ? ' (' . $roleLabel . ')' : '' }}</span>
                                    @if($activity->school)
                                        <span>&bull; {{ $activity->school->name }}</span>
                                    @elseif($activity->district)
                                        <span>&bull; {{ $activity->district->name }}</span>
                                    @elseif($activity->region)
                                        <span>&bull; {{ $activity->region->name }}</span>
                                    @endif
                                    @if($activity->subject)
                                        <span>&bull; {{ $activity->subject->name }}</span>
                                    @endif
                                    @if($activity->affected_marks > 0)
                                        <span>&bull; {{ number_format($activity->affected_marks) }} marks</span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge" style="background:{{ $style['bg'] }}; color:{{ $style['color'] }}; border-color:transparent; white-space:nowrap;">
                                {{ \Illuminate\Support\Str::headline($activity->event_type) }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list empty-icon"></i>
                            <div class="empty-title">No Activity Recorded</div>
                            <div class="empty-desc">PSLE mark entry audit logs and submissions will appear here once the entry process begins.</div>
                        </div>
                    @endforelse
                </div>
            </div>
