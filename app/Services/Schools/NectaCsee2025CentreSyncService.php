<?php

namespace App\Services\Schools;

use App\Models\Region;
use App\Models\School;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NectaCsee2025CentreSyncService
{
    private const BASE_URL = 'https://onlinesys.necta.go.tz/results/2025/csee/index.htm';
    public const SOURCE_SYSTEM = 'NECTA_CSEE_2025';

    public function __construct(
        private readonly CseeSchoolParticularsImportService $particularsImportService
    ) {
    }

    public function syncCentres(): array
    {
        $html = $this->fetchPage(self::BASE_URL);
        $centres = $this->extractCentres($html);

        $synced = 0;

        foreach ($centres as $centre) {
            $this->upsertCentre($centre);
            $synced++;
        }

        $particularsSummary = $this->particularsImportService->importMissingParticulars();

        return [
            'source' => self::SOURCE_SYSTEM,
            'centres_synced' => $synced,
            'particulars_updated' => $particularsSummary['schools_updated'] ?? 0,
        ];
    }

    private function fetchPage(string $url): string
    {
        Log::info('NECTA CSEE sync: fetch page', ['url' => $url]);

        $response = Http::connectTimeout(10)
            ->timeout(45)
            ->retry(3, 1000)
            ->withHeaders([
                'User-Agent' => 'IRMS CSEE Sync/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
                'Connection' => 'keep-alive',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to fetch NECTA CSEE page: {$url}");
        }

        return $response->body();
    }

    private function extractCentres(string $html): array
    {
        preg_match_all('/<a\s+href="([^"]+)">\s*([^<]+?)\s*<\/a>/i', $html, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(function (array $match) {
                $label = trim(html_entity_decode($match[2]));

                if (! preg_match('/^([PS][A-Z0-9]+)\s+(.+?)(?:\s+CENTRE)?$/i', $label, $centreMatch)) {
                    return null;
                }

                return [
                    'code' => strtoupper(trim($centreMatch[1])),
                    'name' => $this->normalizeCentreName($centreMatch[2]),
                    'full_label' => strtoupper($label),
                    'href' => trim(html_entity_decode($match[1])),
                ];
            })
            ->filter()
            ->unique('code')
            ->values()
            ->all();
    }

    private function upsertCentre(array $centre): School
    {
        $existingSynced = School::query()->where('code', $centre['code'])->first();
        $resolved = $this->particularsImportService->resolveAttributes($centre['code'], $centre['name'], $existingSynced?->id);

        return School::query()->updateOrCreate(
            [
                'code' => $centre['code'],
            ],
            [
                'registration_number' => $resolved['registration_number'],
                'source_system' => self::SOURCE_SYSTEM,
                'name' => $centre['name'],
                'region_id' => $resolved['region_id'] ?? $this->fallbackRegion()->id,
                'district_id' => $resolved['district_id'],
                'council_id' => $resolved['council_id'],
                'ownership' => $resolved['ownership'],
                'school_type' => School::TYPE_SECONDARY,
                'education_level' => 'SECONDARY',
                'is_active' => true,
            ]
        );
    }

    private function fallbackRegion(): Region
    {
        return Region::query()->firstOrCreate(
            ['code' => 'CSEE-UNK'],
            [
                'name' => 'UNASSIGNED CSEE CENTRES',
                'description' => 'Fallback region used for CSEE centre sync records pending region enrichment.',
                'is_active' => true,
            ]
        );
    }

    private function normalizeCentreName(string $name): string
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if (str_contains($normalized, ' SEMINARY')) {
            return $normalized;
        }

        if (str_ends_with($normalized, ' SECONDARY SCHOOL')) {
            return $normalized;
        }

        if (str_ends_with($normalized, ' SCHOOL')) {
            return $normalized;
        }

        return $normalized . ' SECONDARY SCHOOL';
    }
}
