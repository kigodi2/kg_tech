<div class="adm-card">
    <div class="adm-card-head">
        <h3 class="adm-card-title"><i class="fa-solid fa-earth-africa"></i> TASIDO Academic Regions Standing</h3>
    </div>
    
    <div class="adm-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">Rank</th>
                        <th>Region Name</th>
                        <th class="text-center">Councils / Districts</th>
                        <th class="text-center">Primary Schools</th>
                        <th class="text-center">Total Candidates</th>
                        <th class="text-center">Region Average</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewData['regions'] ?? [] as $r)
                        <tr>
                            <td class="text-center">
                                <span class="badge @if($r->rank == 1) badge-green @elseif($r->rank == 4) badge-red @else badge-blue @endif" style="min-width: 28px; justify-content: center;">
                                    #{{ $r->rank }}
                                </span>
                            </td>
                            <td><strong>{{ strtoupper($r->name) }}</strong></td>
                            <td class="text-center">{{ $r->districts_count }}</td>
                            <td class="text-center">{{ $r->schools_count }}</td>
                            <td class="text-center">{{ number_format($r->registered) }}</td>
                            <td class="text-center" style="color: var(--tz-blue); font-weight: bold;">{{ number_format($r->average, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('results.psle.dashboard', ['view' => 'district-results', 'region_id' => $r->id]) }}" class="btn btn-action">
                                    <i class="fa-solid fa-map-location-dot"></i> View Districts
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No regional standings records sync found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
