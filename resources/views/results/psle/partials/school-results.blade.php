<div class="adm-card">
    <!-- Filter Bar -->
    <div class="adm-filters">
        <form action="{{ route('results.psle.dashboard') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
            <input type="hidden" name="view" value="school-results">
            <input type="hidden" name="exam_year_id" value="{{ $examYear->id }}">
            
            <div class="adm-filter-group">
                <label class="adm-filter-label">Region</label>
                <select name="region_id" onchange="this.form.submit()" class="adm-select">
                    <option value="">-- All Regions --</option>
                    @foreach($tasidoRegions as $r)
                        <option value="{{ $r->id }}" {{ $regionId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="adm-filter-group">
                <label class="adm-filter-label">District</label>
                <select name="district_id" onchange="this.form.submit()" class="adm-select" {{ empty($districts) ? 'disabled' : '' }}>
                    <option value="">-- All Districts --</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="adm-filter-group" style="min-width: 250px;">
                <label class="adm-filter-label">Search Primary School</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="School Code or Name" class="adm-input">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </div>
        </form>
    </div>

    <!-- School Table -->
    <div class="adm-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>School Name</th>
                        <th>District</th>
                        <th>Region</th>
                        <th class="text-center">Registered</th>
                        <th class="text-center">Complete</th>
                        <th class="text-center">School Avg</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $schools = $viewData['schools'] ?? null;
                    @endphp
                    @forelse($schools ? $schools->items() : [] as $s)
                        <tr>
                            <td><strong style="color: var(--tz-yellow);">{{ $s->code }}</strong></td>
                            <td><strong>{{ strtoupper($s->name) }}</strong></td>
                            <td>{{ strtoupper($s->district_name) }}</td>
                            <td>{{ strtoupper($s->region_name) }}</td>
                            <td class="text-center">{{ $s->registered }}</td>
                            <td class="text-center">{{ $s->complete }}</td>
                            <td class="text-center" style="color: var(--tz-blue); font-weight: bold;">{{ number_format($s->average, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $s->status === 'Complete' ? 'badge-green' : 'badge-yellow' }}">
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <a href="{{ route('results.psle.dashboard', ['view' => 'candidate-results', 'school_id' => $s->id]) }}" class="btn btn-action" style="padding: 4px 8px; font-size: 0.75rem;">
                                        <i class="fa-solid fa-user-graduate"></i> Candidates
                                    </a>
                                    <!-- Direct single-school PDF Statement Sheet download on-the-fly -->
                                    <a href="{{ route('results.psle.reports.school-export', ['school' => $s->id, 'exam_year_id' => $examYear->id, 'mode' => 'published']) }}" target="_blank" class="btn btn-action" style="padding: 4px 8px; font-size: 0.75rem; background: rgba(187,164,94,.1); color: var(--tz-gold);">
                                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa-solid fa-school-flag" style="font-size: 2rem; color: rgba(255,255,255,0.06); margin-bottom: 12px; display: block;"></i>
                                No primary schools matched filter or query parameters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($schools && $schools->hasPages())
            <div class="adm-pagination">
                <div class="pagination-info">
                    Showing {{ $schools->firstItem() }} to {{ $schools->lastItem() }} of {{ $schools->total() }} schools
                </div>
                <div class="pagination-links">
                    {{-- Previous Page --}}
                    @if ($schools->onFirstPage())
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $schools->appends(request()->except('page'))->previousPageUrl() }}" class="page-link"><i class="fa-solid fa-chevron-left"></i></a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach (range(max(1, $schools->currentPage() - 2), min($schools->lastPage(), $schools->currentPage() + 2)) as $page)
                        @if ($page == $schools->currentPage())
                            <span class="page-link active">{{ $page }}</span>
                        @else
                            <a href="{{ $schools->appends(request()->except('page'))->url($page) }}" class="page-link">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page --}}
                    @if ($schools->hasMorePages())
                        <a href="{{ $schools->appends(request()->except('page'))->nextPageUrl() }}" class="page-link"><i class="fa-solid fa-chevron-right"></i></a>
                    @else
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
