@php
    $isStandalone = request()->routeIs('results.psle.reports.*');
    $filterAction = $isStandalone ? route('results.psle.reports.index') : route('results.psle.dashboard');
    $clearAction = $isStandalone 
        ? route('results.psle.reports.index', ['exam_year_id' => $examYear->id]) 
        : route('results.psle.dashboard', ['view' => 'reports', 'exam_year_id' => $examYear->id]);
@endphp
<div class="space-y-6" style="display: flex; flex-direction: column; gap: 24px; font-family: 'Maiandra GD', 'Segoe UI', sans-serif;">
    
    <!-- Top Filter Panel -->
    <div class="adm-card" style="background: rgba(16, 21, 24, 0.6); backdrop-filter: blur(12px);">
        <div class="adm-card-head" style="border-bottom-color: rgba(255,255,255,0.03);">
            <h3 class="adm-card-title"><i class="fa-solid fa-filter" style="color: var(--tz-yellow); margin-right: 8px;"></i> Filter & Locate Centres</h3>
        </div>
        <form method="GET" action="{{ $filterAction }}" class="adm-filters" style="border: none; padding: 20px; display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; width: 100%;">
            @if(!$isStandalone)
                <input type="hidden" name="view" value="reports">
            @endif
            <input type="hidden" name="exam_year_id" value="{{ $examYear->id }}">

            <div class="adm-filter-group" style="flex: 1; min-width: 200px;">
                <label class="adm-filter-label">Region</label>
                <select name="region_id" class="adm-select" onchange="this.form.submit()" style="cursor: pointer;">
                    <option value="">All Regions</option>
                    @foreach($tasidoRegions as $r)
                        <option value="{{ $r->id }}" @selected((string)$regionId === (string)$r->id)>{{ strtoupper($r->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="adm-filter-group" style="flex: 1; min-width: 200px;">
                <label class="adm-filter-label">District / Council</label>
                <select name="district_id" class="adm-select" onchange="this.form.submit()" style="cursor: pointer;">
                    <option value="">All Councils</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" @selected((string)$districtId === (string)$d->id)>{{ strtoupper($d->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="adm-filter-group" style="flex: 2; min-width: 280px;">
                <label class="adm-filter-label">Search Centre Code / Name</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or code..." class="adm-input">
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; flex-shrink: 0; margin-left: auto;">
                <button type="submit" class="btn btn-blue" style="height: 40px; padding: 0 20px; font-weight: 700; font-size: 0.85rem; border-radius: 8px; display: flex; align-items: center; gap: 6px; background: var(--tz-blue); color: #fff; border: none; cursor: pointer; transition: background 0.2s;">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
                @if(request()->filled('region_id') || request()->filled('district_id') || request()->filled('search'))
                    <a href="{{ $clearAction }}" class="btn" style="height: 40px; padding: 0 16px; font-weight: 700; font-size: 0.85rem; border-radius: 8px; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.06); color: var(--tz-text-muted); text-decoration: none; border: 1px solid rgba(255,255,255,0.08); transition: background 0.2s;">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Main Grid Section -->
    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        
        <!-- School PDF Reports Grid -->
        <div class="adm-card" style="background: rgba(16, 21, 24, 0.6); backdrop-filter: blur(12px); overflow: hidden; margin-bottom: 0;">
            <div class="adm-card-head" style="border-bottom-color: rgba(255,255,255,0.03);">
                <h3 class="adm-card-title" style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-school" style="color: var(--tz-blue);"></i> School-Level Statement Sheets</h3>
                <span style="font-size: 0.75rem; color: var(--tz-text-muted);">
                    Showing {{ $viewData['schools']->firstItem() ?: 0 }}-{{ $viewData['schools']->lastItem() ?: 0 }} of {{ $viewData['schools']->total() }} centres
                </span>
            </div>
            
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <th>Code</th>
                            <th>School Name</th>
                            <th>Location</th>
                            <th class="text-center">Registered</th>
                            <th class="text-center">Complete</th>
                            <th class="text-center">Missing</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viewData['schools'] as $school)
                            @php
                                $stats = $viewData['schoolStats'][$school->id] ?? ['registered' => 0, 'complete' => 0, 'missing' => 0, 'status' => 'No Marks'];
                            @endphp
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                <td style="font-weight: 700; color: var(--tz-yellow); font-size: 0.85rem; padding: 14px 20px;">{{ $school->code }}</td>
                                <td style="padding: 14px 20px;">
                                    <div style="font-weight: 700; color: #fff;">{{ strtoupper($school->name) }}</div>
                                    <div style="font-size: 0.75rem; color: var(--tz-text-muted);">Primary Education Centre</div>
                                </td>
                                <td style="padding: 14px 20px;">
                                    <div style="font-size: 0.85rem; color: #fff;">{{ strtoupper($school->district->name ?? '-') }}</div>
                                    <div style="font-size: 0.75rem; color: var(--tz-text-muted);">{{ strtoupper($school->region->name ?? '-') }}</div>
                                </td>
                                <td class="text-center font-bold style-table-cell" style="padding: 14px 20px;">
                                    <span class="badge badge-blue">{{ $stats['registered'] }}</span>
                                </td>
                                <td class="text-center font-bold style-table-cell" style="padding: 14px 20px;">
                                    @if($stats['complete'] > 0)
                                        <span class="badge badge-green">{{ $stats['complete'] }}</span>
                                    @else
                                        <span class="badge" style="background: rgba(255,255,255,0.04); color: var(--tz-text-muted); border: 1px solid rgba(255,255,255,0.06);">0</span>
                                    @endif
                                </td>
                                <td class="text-center font-bold style-table-cell" style="padding: 14px 20px;">
                                    @if($stats['missing'] > 0)
                                        <span class="badge" style="background: rgba(220,53,69,0.15); color: #ff6b6b; border: 1px solid rgba(220,53,69,0.25);">{{ $stats['missing'] }}</span>
                                    @else
                                        <span class="badge" style="background: rgba(30,181,58,0.04); color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.02);">0</span>
                                    @endif
                                </td>
                                <td class="text-center style-table-cell" style="padding: 14px 20px;">
                                    @if($stats['status'] === 'Ready')
                                        <span class="badge badge-green"><i class="fa-solid fa-circle-check" style="margin-right: 4px;"></i> Ready</span>
                                    @elseif($stats['status'] === 'In Progress')
                                        <span class="badge" style="background: rgba(252,209,22,0.1); color: #ffe675; border: 1px solid rgba(252,209,22,0.25);"><i class="fa-solid fa-spinner fa-spin" style="margin-right: 4px;"></i> In Progress</span>
                                    @else
                                        <span class="badge" style="background: rgba(220,53,69,0.1); color: #ff8b8b; border: 1px solid rgba(220,53,69,0.25);"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 4px;"></i> No Marks</span>
                                    @endif
                                </td>
                                <td class="text-right" style="padding: 14px 20px;">
                                    @if($stats['complete'] > 0)
                                        <a href="{{ route('results.psle.reports.school-export', ['school' => $school->id, 'exam_year_id' => $examYear->id, 'mode' => 'draft']) }}" target="_blank" class="btn" style="padding: 6px 12px; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; font-weight: 700; border-radius: 6px; text-decoration: none; background: var(--tz-green); color: #fff; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(30,181,58,0.2);" onmouseover="this.style.background='#28a745'" onmouseout="this.style.background='var(--tz-green)'">
                                            <i class="fa-solid fa-file-pdf"></i> Download PDF
                                        </a>
                                    @else
                                        <button disabled class="btn" style="padding: 6px 12px; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; font-weight: 700; border-radius: 6px; background: rgba(255,255,255,0.03); color: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.02); cursor: not-allowed;">
                                            <i class="fa-solid fa-file-pdf"></i> Download PDF
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 50px 20px; color: var(--tz-text-muted);">
                                    <div style="font-size: 2.2rem; margin-bottom: 12px; color: var(--tz-border);"><i class="fa-solid fa-school-flag"></i></div>
                                    <div style="font-size: 0.95rem; font-weight: 700;">No primary schools found matching the filtered scope.</div>
                                    <div style="font-size: 0.8rem; margin-top: 4px;">Try selecting another region or typing a different search term.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination -->
            @if($viewData['schools']->hasPages())
                <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">
                        Showing <strong>{{ $viewData['schools']->firstItem() }}</strong> to <strong>{{ $viewData['schools']->lastItem() }}</strong> of <strong>{{ $viewData['schools']->total() }}</strong> entries
                    </div>
                    <div style="display: flex; gap: 6px;">
                        {{-- Previous Page Link --}}
                        @if($viewData['schools']->onFirstPage())
                            <span style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.02); color: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.02); cursor: not-allowed;">« Previous</span>
                        @else
                            <a href="{{ $viewData['schools']->previousPageUrl() }}" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.08); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='rgba(187,164,94,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">« Previous</a>
                        @endif

                        {{-- Pagination Elements --}}
                        @php
                            $start = max(1, $viewData['schools']->currentPage() - 1);
                            $end = min($viewData['schools']->lastPage(), $viewData['schools']->currentPage() + 1);
                        @endphp

                        @if($start > 1)
                            <a href="{{ $viewData['schools']->url(1) }}" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.08); text-decoration: none;">1</a>
                            @if($start > 2)
                                <span style="padding: 8px 6px; color: var(--tz-text-muted);">...</span>
                            @endif
                        @endif

                        @for($i = $start; $i <= $end; $i++)
                            @if($i === $viewData['schools']->currentPage())
                                <span style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(187,164,94,0.22); color: #fff; border: 1px solid rgba(187,164,94,0.4); font-weight: 700;">{{ $i }}</span>
                            @else
                                <a href="{{ $viewData['schools']->url($i) }}" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.08); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='rgba(187,164,94,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">{{ $i }}</a>
                            @endif
                        @endfor

                        @if($end < $viewData['schools']->lastPage())
                            @if($end < $viewData['schools']->lastPage() - 1)
                                <span style="padding: 8px 6px; color: var(--tz-text-muted);">...</span>
                            @endif
                            <a href="{{ $viewData['schools']->url($viewData['schools']->lastPage()) }}" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.08); text-decoration: none;">{{ $viewData['schools']->lastPage() }}</a>
                        @endif

                        {{-- Next Page Link --}}
                        @if($viewData['schools']->hasMorePages())
                            <a href="{{ $viewData['schools']->nextPageUrl() }}" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.08); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='rgba(187,164,94,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">Next »</a>
                        @else
                            <span style="padding: 8px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.02); color: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.02); cursor: not-allowed;">Next »</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar Actions -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- District Bulk Export Card -->
            <div class="adm-card" style="background: rgba(16, 21, 24, 0.6); backdrop-filter: blur(12px);">
                <div class="adm-card-head" style="border-bottom-color: rgba(255,255,255,0.03);">
                    <h3 class="adm-card-title"><i class="fa-solid fa-file-zipper" style="color: var(--tz-yellow); margin-right: 8px;"></i> District ZIP Compiler</h3>
                </div>
                <div class="adm-card-body" style="padding: 20px;">
                    <p style="font-size: 0.85rem; color: var(--tz-text-muted); line-height: 1.5; margin-bottom: 20px;">
                        Select a target district/council to compile and pack individual school results PDFs into a single ZIP archive download.
                    </p>
                    
                    <form action="{{ route('results.psle.reports.district-school-results-export') }}" method="POST" target="_blank" style="display: flex; flex-direction: column; gap: 16px;">
                        @csrf
                        <input type="hidden" name="exam_year_id" value="{{ $examYear->id }}">
                        <input type="hidden" name="mode" value="draft">
                        
                        <div class="adm-filter-group">
                            <label class="adm-filter-label" style="color: var(--tz-yellow);">1. Select Target District</label>
                            <select name="district_id" required class="adm-select" style="background: rgba(255,255,255,0.04); border-color: rgba(187,164,94,0.2); cursor: pointer;">
                                <option value="">-- Choose District --</option>
                                @foreach($districts as $d)
                                    <option value="{{ $d->id }}" @selected((string)$districtId === (string)$d->id)>{{ strtoupper($d->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="adm-filter-group">
                            <label class="adm-filter-label" style="color: var(--tz-yellow);">2. Dynamic Export Settings</label>
                            <select name="format" class="adm-select" style="background: rgba(255,255,255,0.04); border-color: rgba(187,164,94,0.2); cursor: not-allowed;" disabled>
                                <option value="zip">ZIP of Individual school PDFs</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn" style="width: 100%; height: 44px; padding: 0 20px; font-weight: 700; font-size: 0.9rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--tz-yellow); color: var(--tz-black); border: none; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 14px rgba(252,209,22,0.2);" onmouseover="this.style.background='#ffe366'" onmouseout="this.style.background='var(--tz-yellow)'">
                            <i class="fa-solid fa-cloud-arrow-down"></i> Trigger Compilation ZIP
                        </button>
                    </form>
                </div>
            </div>

            <!-- Full Dedicated Workspace Link -->
            <div class="adm-card" style="background: linear-gradient(135deg, rgba(16,21,24,0.8), rgba(8,15,20,0.8)); border-color: rgba(187,164,94,0.15);">
                <div class="adm-card-head" style="border-bottom-color: rgba(187,164,94,0.08);">
                    <h3 class="adm-card-title" style="color: var(--tz-yellow);"><i class="fa-solid fa-circle-info"></i> Full PDF Workspace</h3>
                </div>
                <div class="adm-card-body" style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <p style="font-size: 0.8rem; color: var(--tz-text-muted); line-height: 1.5; margin: 0;">
                        To access the full, specialized district bulk PDF reports desk featuring ACSEE/PSLE joint summaries, barcodes, and dynamic configuration tools, open the legacy Reports workspace.
                    </p>
                    <a href="{{ route('results.psle.reports.index') }}" class="btn" style="width: 100%; height: 40px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px; background: rgba(187,164,94,0.12); color: #f0e6c8; text-decoration: none; border: 1px solid rgba(187,164,94,0.2); transition: all 0.2s;" onmouseover="this.style.background='rgba(187,164,94,0.22)'" onmouseout="this.style.background='rgba(187,164,94,0.12)'">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open PDF reports Desk
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</div>
