<?php

namespace App\Console\Commands;

use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditDuplicateSchools extends Command
{
    protected $signature = 'schools:audit-duplicates
        {--region= : Region ID or name}
        {--limit=20 : Maximum duplicate groups to display per section}';

    protected $description = 'Audit duplicate schools by normalized name and by code';

    public function handle(): int
    {
        $limit = max((int) $this->option('limit'), 1);
        $region = $this->resolveRegion((string) $this->option('region'));

        $base = DB::table('schools as s')
            ->leftJoin('regions as r', 'r.id', '=', 's.region_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id');

        if ($region) {
            $base->where('s.region_id', $region->id);
        }

        $schools = (clone $base)
            ->selectRaw('s.id')
            ->selectRaw('COALESCE(s.code, "") as code')
            ->selectRaw('COALESCE(s.name, "") as name')
            ->selectRaw('COALESCE(r.name, "-") as region_name')
            ->selectRaw('COALESCE(dc.name, d.name, "-") as council_name')
            ->selectRaw('COALESCE(s.is_active, 0) as is_active')
            ->orderBy('s.name')
            ->get();

        $byNormalizedName = $schools
            ->groupBy(fn ($row) => $this->normalizeName((string) $row->name))
            ->filter(fn ($group, $key) => $key !== '' && $group->count() > 1)
            ->sortByDesc(fn ($group) => $group->count())
            ->values();

        $byCode = $schools
            ->groupBy(fn ($row) => strtoupper(trim((string) $row->code)))
            ->filter(fn ($group, $key) => $key !== '' && $group->count() > 1)
            ->sortByDesc(fn ($group) => $group->count())
            ->values();

        $scopeLabel = $region ? strtoupper((string) $region->name) : 'ALL REGIONS';

        $this->info('School duplicate audit');
        $this->line('Scope: ' . $scopeLabel);
        $this->line('Schools scanned: ' . number_format($schools->count()));
        $this->line('Duplicate name groups: ' . number_format($byNormalizedName->count()));
        $this->line('Duplicate code groups: ' . number_format($byCode->count()));
        $this->newLine();

        $this->renderNameDuplicates($byNormalizedName, $limit);
        $this->newLine();
        $this->renderCodeDuplicates($byCode, $limit);

        return ($byNormalizedName->isNotEmpty() || $byCode->isNotEmpty())
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function renderNameDuplicates($groups, int $limit): void
    {
        $this->warn('Duplicate names');

        if ($groups->isEmpty()) {
            $this->line('No duplicate normalized school names found.');
            return;
        }

        foreach ($groups->take($limit) as $group) {
            $sample = $group->first();
            $sampleName = strtoupper((string) $sample->name);
            $sampleCode = strtoupper(trim((string) $sample->code));
            $label = $sampleCode !== '' ? "{$sampleCode} - {$sampleName}" : $sampleName;
            $this->line('- ' . $label . ' (' . $group->count() . ' records)');
            $rows = $group->map(fn ($row) => [
                'ID' => $row->id,
                'School' => $row->code !== ''
                    ? strtoupper(trim((string) $row->code)) . ' - ' . ($row->name !== '' ? $row->name : '-')
                    : ($row->name !== '' ? $row->name : '-'),
                'Region' => $row->region_name,
                'Council' => $row->council_name,
                'Active' => (int) $row->is_active === 1 ? 'YES' : 'NO',
            ])->all();
            $this->table(['ID', 'School', 'Region', 'Council', 'Active'], $rows);
        }
    }

    private function renderCodeDuplicates($groups, int $limit): void
    {
        $this->warn('Duplicate codes');

        if ($groups->isEmpty()) {
            $this->line('No duplicate school codes found.');
            return;
        }

        foreach ($groups->take($limit) as $group) {
            $sample = $group->first();
            $this->line('- ' . strtoupper((string) $sample->code) . ' (' . $group->count() . ' records)');
            $rows = $group->map(fn ($row) => [
                'ID' => $row->id,
                'School' => $row->code !== ''
                    ? strtoupper(trim((string) $row->code)) . ' - ' . ($row->name !== '' ? $row->name : '-')
                    : ($row->name !== '' ? $row->name : '-'),
                'Region' => $row->region_name,
                'Council' => $row->council_name,
                'Active' => (int) $row->is_active === 1 ? 'YES' : 'NO',
            ])->all();
            $this->table(['ID', 'School', 'Region', 'Council', 'Active'], $rows);
        }
    }

    private function resolveRegion(string $regionInput): ?Region
    {
        $regionInput = trim($regionInput);
        if ($regionInput === '') {
            return null;
        }

        if (ctype_digit($regionInput)) {
            return Region::query()->find((int) $regionInput);
        }

        return Region::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($regionInput)])
            ->first();
    }

    private function normalizeName(string $name): string
    {
        $normalized = Str::upper(trim($name));
        $normalized = preg_replace('/\s+/', ' ', $normalized ?? '') ?? '';
        return $normalized;
    }
}
