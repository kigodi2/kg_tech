<?php

namespace App\Services\Schools;

use App\Models\District;
use App\Models\Region;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class NectaPsle2025SchoolSyncService
{
    private const BASE_URL = 'https://onlinesys.necta.go.tz/results/2025/psle/psle.htm';
    public const SOURCE_SYSTEM = 'NECTA_PSLE_2025';
    private ?Collection $regionLinks = null;

    public function syncRegisteredRegions(?Collection $regions = null): array
    {
        $regions = ($regions ?: Region::query()->orderBy('name')->get())->values();

        $summary = [
            'regions_processed' => 0,
            'districts_synced' => 0,
            'schools_synced' => 0,
            'skipped_regions' => [],
            'failed_regions' => [],
        ];

        foreach ($regions as $region) {
            try {
                $result = $this->syncRegion($region);
            } catch (RuntimeException $exception) {
                $result = [
                    'status' => 'failed',
                    'region' => $region->name,
                    'districts_synced' => 0,
                    'schools_synced' => 0,
                    'message' => $exception->getMessage(),
                ];
            }

            if ($result['status'] === 'processed') {
                $summary['regions_processed']++;
                $summary['districts_synced'] += $result['districts_synced'];
                $summary['schools_synced'] += $result['schools_synced'];
                continue;
            }

            if ($result['status'] === 'skipped') {
                $summary['skipped_regions'][] = $result['region'];
                continue;
            }

            $summary['failed_regions'][] = [
                'region' => $result['region'],
                'error' => $result['message'],
            ];
        }

        return $summary;
    }

    public function syncRegion(Region $region): array
    {
        $regionLinks = $this->loadRegionLinks();
        $link = $regionLinks->get($this->normalizeName($region->name));

        if (! $link) {
            return [
                'status' => 'skipped',
                'region' => $region->name,
                'districts_synced' => 0,
                'schools_synced' => 0,
                'message' => 'No exact NECTA page match found for region.',
            ];
        }

        $regionUrl = $this->absoluteUrl($link['href'], self::BASE_URL);
        Log::info('NECTA PSLE sync: region start', ['region' => $region->name, 'url' => $regionUrl]);
        $districtLinks = $this->extractLinks($this->fetchPage($regionUrl));

        $districtCount = 0;
        $schoolCount = 0;

        foreach ($districtLinks as $districtLink) {
            $district = $this->upsertDistrict($region, $districtLink);
            $districtCount++;

            $districtUrl = $this->absoluteUrl($districtLink['href'], $regionUrl);
            Log::info('NECTA PSLE sync: district fetch', [
                'region' => $region->name,
                'district' => $district->name,
                'url' => $districtUrl,
            ]);

            $schoolRows = $this->extractSchools($this->fetchPage($districtUrl));

            foreach ($schoolRows as $schoolRow) {
                $this->upsertSchool($region, $district, $schoolRow);
                $schoolCount++;
            }
        }

        Log::info('NECTA PSLE sync: region complete', [
            'region' => $region->name,
            'districts_synced' => $districtCount,
            'schools_synced' => $schoolCount,
        ]);

        return [
            'status' => 'processed',
            'region' => $region->name,
            'districts_synced' => $districtCount,
            'schools_synced' => $schoolCount,
            'message' => 'Region synced successfully.',
        ];
    }

    private function fetchPage(string $url): string
    {
        Log::info('NECTA PSLE sync: fetch page', ['url' => $url]);

        $response = Http::connectTimeout(10)
            ->timeout(45)
            ->retry(3, 1000)
            ->withHeaders([
                'User-Agent' => 'IRMS PSLE Sync/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
                'Connection' => 'keep-alive',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Failed to fetch NECTA page: {$url}");
        }

        return $response->body();
    }

    private function extractLinks(string $html): array
    {
        preg_match_all('/<a\s+href="([^"]+)">\s*([^<]+?)\s*<\/a>/i', $html, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(function (array $match) {
                return [
                    'href' => trim(html_entity_decode($match[1])),
                    'label' => trim(html_entity_decode($match[2])),
                ];
            })
            ->filter(fn (array $row) => $row['href'] !== '' && $row['label'] !== '')
            ->values()
            ->all();
    }

    private function extractSchools(string $html): array
    {
        return collect($this->extractLinks($html))
            ->map(function (array $row) {
                if (! preg_match('/^(.*)\s-\s([A-Z0-9]+)\s*$/', $row['label'], $matches)) {
                    return null;
                }

                return [
                    'name' => trim($matches[1]),
                    'code' => trim($matches[2]),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function upsertDistrict(Region $region, array $districtLink): District
    {
        $name = trim($districtLink['label']);
        $href = trim($districtLink['href']);
        preg_match('/distr_(\d+)\.htm/i', $href, $matches);
        $derivedCode = 'PSLE25-' . ($matches[1] ?? Str::slug($name, ''));

        return District::query()->firstOrCreate(
            [
                'region_id' => $region->id,
                'name' => $name,
            ],
            [
                'code' => $derivedCode,
                'description' => 'Synced from NECTA PSLE 2025 school registration pages.',
                'status' => 'active',
            ]
        );
    }

    private function upsertSchool(Region $region, District $district, array $schoolRow): School
    {
        return School::query()->updateOrCreate(
            [
                'code' => $schoolRow['code'],
            ],
            [
                'registration_number' => $schoolRow['code'],
                'source_system' => self::SOURCE_SYSTEM,
                'name' => trim($schoolRow['name']),
                'district_id' => $district->id,
                'region_id' => $region->id,
                'school_type' => School::TYPE_PRIMARY,
                'education_level' => 'PRIMARY',
                'is_active' => true,
            ]
        );
    }

    private function absoluteUrl(string $href, string $baseUrl): string
    {
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        $base = rtrim(Str::beforeLast($baseUrl, '/'), '/');

        return $base . '/' . ltrim($href, '/');
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(trim($value)));
    }

    private function loadRegionLinks(): Collection
    {
        if ($this->regionLinks instanceof Collection) {
            return $this->regionLinks;
        }

        $this->regionLinks = collect($this->extractLinks($this->fetchPage(self::BASE_URL)))
            ->keyBy(fn (array $row) => $this->normalizeName($row['label']));

        return $this->regionLinks;
    }
}
