<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Regional table card -->
    <div class="adm-card">
        <div class="adm-card-head">
            <h3 class="adm-card-title"><i class="fa-solid fa-map-location-dot"></i> Regional Completion Standings</h3>
        </div>
        <div class="adm-card-body" style="padding: 0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Region Name</th>
                            <th class="text-center">Primary Schools</th>
                            <th class="text-center">Registered Candidates</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viewData['regionalSummary'] ?? [] as $r)
                            <tr>
                                <td><strong>{{ strtoupper($r->name) }}</strong></td>
                                <td class="text-center">{{ $r->schools_count }}</td>
                                <td class="text-center">{{ number_format($r->candidates_count) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('results.psle.dashboard', ['view' => 'school-results', 'region_id' => $r->id]) }}" class="btn btn-action">
                                        <i class="fa-solid fa-folder-open"></i> Schools
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">No regional data sync found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subject coverage breakdown -->
    <div class="adm-card">
        <div class="adm-card-head">
            <h3 class="adm-card-title"><i class="fa-solid fa-book-open"></i> Subject Entry Completeness</h3>
        </div>
        <div class="adm-card-body" style="padding: 20px;">
            @forelse($viewData['subjectCompleteness'] ?? [] as $sub)
                <div style="margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                        <span><strong>{{ $sub->code }}</strong> - {{ $sub->name }}</span>
                        <span style="color: var(--tz-yellow); font-weight: bold;">
                            {{ number_format($sub->marks_count) }} entered
                            @if($metrics['registered'] > $sub->marks_count)
                                <span style="color: #ef4444; font-size: 0.75rem; font-weight: normal; margin-left: 6px;">
                                    ({{ number_format($metrics['registered'] - $sub->marks_count) }} missing)
                                </span>
                            @endif
                        </span>
                    </div>
                    <!-- Completeness bar -->
                    <div style="height: 6px; background: rgba(255,255,255,0.06); border-radius: 3px; overflow: hidden;">
                        @php
                            $percent = $sub->marks_count === $metrics['registered'] ? 100 : min(99.9, floor(($sub->marks_count / $metrics['registered']) * 100 * 10) / 10);
                        @endphp
                        <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, var(--tz-blue), var(--tz-green)); border-radius: 3px;"></div>
                    </div>
                </div>
            @empty
                <div class="text-center" style="color: var(--tz-text-muted);">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                    <p>No subject marks synched in raw marks yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Integration links cards -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
    <a href="{{ route('public.results.psle.regions', ['examYear' => $examYear->year_label ?? 2026]) }}" target="_blank" class="qa-item">
        <i class="fa-solid fa-globe" style="color: #67d8ff;"></i>
        <div class="qa-item-title">Public Results Portal</div>
        <div style="font-size: 0.8rem; color: var(--tz-text-muted);">Browse the official published results breakdown from the public side.</div>
        <span class="qa-item-badge">Protected View <i class="fa-solid fa-up-right-from-square"></i></span>
    </a>
    
    <a href="{{ route('evaluations.psle.index') }}" target="_blank" class="qa-item">
        <i class="fa-solid fa-square-poll-vertical" style="color: #6ae086;"></i>
        <div class="qa-item-title">Evaluations System</div>
        <div style="font-size: 0.8rem; color: var(--tz-text-muted);">Inspect zonal, regional, and district evaluations & rank statistics.</div>
        <span class="qa-item-badge">Protected View <i class="fa-solid fa-up-right-from-square"></i></span>
    </a>

    <a href="{{ route('results.psle.dashboard', ['view' => 'reports']) }}" class="qa-item">
        <i class="fa-solid fa-file-pdf" style="color: #bba45e;"></i>
        <div class="qa-item-title">PDF Reports & ZIPs</div>
        <div style="font-size: 0.8rem; color: var(--tz-text-muted);">Generate individual primary school candidate statement sheets and PDF registers.</div>
        <span class="qa-item-badge">Reports Exporter <i class="fa-solid fa-chevron-right"></i></span>
    </a>
</div>
