<div class="adm-card">
    <div class="adm-card-head">
        <h3 class="adm-card-title"><i class="fa-solid fa-map-location-dot"></i> Councils / Districts Academic Rankings</h3>
    </div>
    
    <div class="adm-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="text-center">Rank</th>
                        <th>District Name</th>
                        <th>Parent Region</th>
                        <th class="text-center">Primary Schools</th>
                        <th class="text-center">Total Candidates</th>
                        <th class="text-center">District Average</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewData['districts'] ?? [] as $d)
                        <tr>
                            <td class="text-center">
                                <span class="badge @if($d->rank <= 3) badge-green @elseif($d->rank > 10) badge-red @else badge-blue @endif" style="min-width: 28px; justify-content: center;">
                                    #{{ $d->rank }}
                                </span>
                            </td>
                            <td><strong>{{ strtoupper($d->name) }}</strong></td>
                            <td>{{ strtoupper($d->region_name) }}</td>
                            <td class="text-center">{{ $d->schools_count }}</td>
                            <td class="text-center">{{ number_format($d->registered) }}</td>
                            <td class="text-center" style="color: var(--tz-blue); font-weight: bold;">{{ number_format($d->average, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('results.psle.dashboard', ['view' => 'school-results', 'district_id' => $d->id]) }}" class="btn btn-action">
                                    <i class="fa-solid fa-school"></i> View Schools
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No council or district rankings records sync found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
