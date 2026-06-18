<div class="adm-card">
    <!-- Filter Bar -->
    <div class="adm-filters">
        <form action="{{ route('results.psle.dashboard') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
            <input type="hidden" name="view" value="audit">
            <input type="hidden" name="exam_year_id" value="{{ $examYear->id }}">
            
            <div class="adm-filter-group">
                <label class="adm-filter-label">Filter Action Type</label>
                <select name="action_filter" onchange="this.form.submit()" class="adm-select">
                    <option value="">-- All Actions --</option>
                    <option value="psle_results_validate" {{ request('action_filter') === 'psle_results_validate' ? 'selected' : '' }}>Validation Check Runs</option>
                    <option value="psle_results_draft_run" {{ request('action_filter') === 'psle_results_draft_run' ? 'selected' : '' }}>Draft Computations</option>
                    <option value="psle_results_final_run" {{ request('action_filter') === 'psle_results_final_run' ? 'selected' : '' }}>Final Computations</option>
                    <option value="psle_results_rollback" {{ request('action_filter') === 'psle_results_rollback' ? 'selected' : '' }}>Snapshot Rollbacks</option>
                    <option value="psle_results_view_overview" {{ request('action_filter') === 'psle_results_view_overview' ? 'selected' : '' }}>Overview Portal Views</option>
                </select>
            </div>
            
            <div class="adm-filter-group" style="flex: 3; display: flex; align-items: flex-end; justify-content: flex-end;">
                <a href="{{ route('results.psle.dashboard', ['view' => 'audit']) }}" class="btn btn-outline"><i class="fa-solid fa-rotate-left"></i> Reset Filter</a>
            </div>
        </form>
    </div>

    <!-- Audit Log Table -->
    <div class="adm-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Admin User</th>
                        <th>Action Code</th>
                        <th>Detailed Log Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $logs = $viewData['logs'] ?? null;
                    @endphp
                    @forelse($logs ? $logs->items() : [] as $log)
                        <tr>
                            <td style="white-space: nowrap; color: var(--tz-text-muted);">{{ $log->created_at }}</td>
                            <td><strong>{{ $log->user->name ?? 'System Admin' }}</strong></td>
                            <td>
                                <span class="badge @if(str_contains($log->action, 'final')) badge-success @elseif(str_contains($log->action, 'rollback')) badge-red @elseif(str_contains($log->action, 'validate')) badge-blue @else badge-yellow @endif">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>{{ $log->details }}</td>
                            <td style="font-family: monospace;">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fa-solid fa-receipt" style="font-size: 2rem; color: rgba(255,255,255,0.06); margin-bottom: 12px; display: block;"></i>
                                No audit log records found matching the action filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        @if($logs && $logs->hasPages())
            <div class="adm-pagination">
                <div class="pagination-info">
                    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} audit events
                </div>
                <div class="pagination-links">
                    {{-- Previous Page --}}
                    @if ($logs->onFirstPage())
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $logs->appends(request()->except('page'))->previousPageUrl() }}" class="page-link"><i class="fa-solid fa-chevron-left"></i></a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach (range(max(1, $logs->currentPage() - 2), min($logs->lastPage(), $logs->currentPage() + 2)) as $page)
                        @if ($page == $logs->currentPage())
                            <span class="page-link active">{{ $page }}</span>
                        @else
                            <a href="{{ $logs->appends(request()->except('page'))->url($page) }}" class="page-link">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page --}}
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->appends(request()->except('page'))->nextPageUrl() }}" class="page-link"><i class="fa-solid fa-chevron-right"></i></a>
                    @else
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
