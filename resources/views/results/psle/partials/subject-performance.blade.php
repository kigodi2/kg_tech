<div class="adm-card">
    <div class="adm-card-head">
        <h3 class="adm-card-title"><i class="fa-solid fa-book-open"></i> Subject Standing & Grade Distributions</h3>
    </div>
    
    <div class="adm-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th class="text-center">Candidates Assessed</th>
                        <th class="text-center">Highest Mark</th>
                        <th class="text-center">Lowest Mark</th>
                        <th class="text-center">Subject Average</th>
                        <th class="text-center">A</th>
                        <th class="text-center">B</th>
                        <th class="text-center">C</th>
                        <th class="text-center">D</th>
                        <th class="text-center">E</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewData['performance'] ?? [] as $p)
                        <tr>
                            <td><strong>{{ strtoupper($p->name) }}</strong></td>
                            <td class="text-center">{{ number_format($p->candidates) }}</td>
                            <td class="text-center" style="color: var(--tz-green); font-weight: bold;">{{ $p->highest }}/50</td>
                            <td class="text-center" style="color: var(--tz-red);">{{ $p->lowest }}/50</td>
                            <td class="text-center" style="color: var(--tz-blue); font-weight: bold;">{{ number_format($p->average, 2) }}</td>
                            <td class="text-center"><span class="badge badge-green">{{ number_format($p->a) }}</span></td>
                            <td class="text-center"><span class="badge badge-blue">{{ number_format($p->b) }}</span></td>
                            <td class="text-center"><span class="badge badge-blue">{{ number_format($p->c) }}</span></td>
                            <td class="text-center"><span class="badge badge-yellow">{{ number_format($p->d) }}</span></td>
                            <td class="text-center"><span class="badge badge-red">{{ number_format($p->e) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">No subject performance records found in raw marks.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
