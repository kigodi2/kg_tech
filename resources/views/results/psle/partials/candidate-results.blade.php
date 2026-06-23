<div class="adm-card">
    <!-- Filter Bar -->
    <div class="adm-filters">
        <form action="{{ route('results.psle.dashboard') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
            <input type="hidden" name="view" value="candidate-results">
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
            
            <div class="adm-filter-group">
                <label class="adm-filter-label">School</label>
                <select name="school_id" onchange="this.form.submit()" class="adm-select" {{ empty($schools) ? 'disabled' : '' }}>
                    <option value="">-- All Schools --</option>
                    @foreach($schools as $s)
                        <option value="{{ $s->id }}" {{ $schoolId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="adm-filter-group" style="min-width: 200px;">
                <label class="adm-filter-label">Search Candidate</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="CNO, Name, PREM" class="adm-input">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Candidate Table -->
    <div class="adm-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>CNO</th>
                        <th>Full Name</th>
                        <th class="text-center">Gender</th>
                        <th class="text-center">KIS</th>
                        <th class="text-center">ENG</th>
                        <th class="text-center">MAT</th>
                        <th class="text-center">SCI</th>
                        <th class="text-center">CIV</th>
                        <th class="text-center">SOC</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">WASTANI wa Alama</th>
                        <th class="text-center">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $candidates = $viewData['candidates'] ?? null;
                    @endphp
                    @forelse($candidates ? $candidates->items() : [] as $c)
                        <tr>
                            <td><strong style="color: var(--tz-yellow);">{{ $c->cno }}</strong></td>
                            <td>{{ strtoupper($c->full_name) }}</td>
                            <td class="text-center">{{ $c->gender }}</td>
                            <td class="text-center">{{ $c->kiswahili ?? '-' }}</td>
                            <td class="text-center">{{ $c->english ?? '-' }}</td>
                            <td class="text-center">{{ $c->mathematics ?? '-' }}</td>
                            <td class="text-center">{{ $c->science ?? '-' }}</td>
                            <td class="text-center">{{ $c->civic ?? '-' }}</td>
                            <td class="text-center">{{ $c->social ?? '-' }}</td>
                            <td class="text-center"><strong>{{ $c->total }}</strong></td>
                            <td class="text-center" style="color: var(--tz-blue); font-weight: bold;">{{ number_format($c->average, 2) }}</td>
                            <td class="text-center">
                                <span class="badge @if($c->grade === 'A') badge-green @elseif($c->grade === 'B' || $c->grade === 'C') badge-blue @else badge-red @endif">
                                    {{ $c->grade }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-5">
                                <i class="fa-solid fa-user-slash" style="font-size: 2rem; color: rgba(255,255,255,0.06); margin-bottom: 12px; display: block;"></i>
                                No candidates matched the filters or query search parameters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($candidates && $candidates->hasPages())
            <div class="adm-pagination">
                <div class="pagination-info">
                    Showing {{ $candidates->firstItem() }} to {{ $candidates->lastItem() }} of {{ $candidates->total() }} candidates
                </div>
                <div class="pagination-links">
                    {{-- Previous Page --}}
                    @if ($candidates->onFirstPage())
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $candidates->appends(request()->except('page'))->previousPageUrl() }}" class="page-link"><i class="fa-solid fa-chevron-left"></i></a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach (range(max(1, $candidates->currentPage() - 2), min($candidates->lastPage(), $candidates->currentPage() + 2)) as $page)
                        @if ($page == $candidates->currentPage())
                            <span class="page-link active">{{ $page }}</span>
                        @else
                            <a href="{{ $candidates->appends(request()->except('page'))->url($page) }}" class="page-link">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page --}}
                    @if ($candidates->hasMorePages())
                        <a href="{{ $candidates->appends(request()->except('page'))->nextPageUrl() }}" class="page-link"><i class="fa-solid fa-chevron-right"></i></a>
                    @else
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
