<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Services\Schools\NectaPsle2025SchoolSyncService;
use Illuminate\Console\Command;

class SyncPsle2025SchoolsFromNecta extends Command
{
    protected $signature = 'necta:sync-psle-2025-schools {--region=* : Region code or name to limit the sync}';

    protected $description = 'Sync PSLE 2025 schools from official NECTA pages for regions already registered in IRMS';

    public function handle(NectaPsle2025SchoolSyncService $service): int
    {
        $regions = Region::query()->orderBy('name');
        $selectedRegions = collect($this->option('region'))
            ->filter()
            ->map(fn (string $value) => strtoupper(trim($value)));

        if ($selectedRegions->isNotEmpty()) {
            $regions->where(function ($query) use ($selectedRegions) {
                foreach ($selectedRegions as $value) {
                    $query->orWhereRaw('UPPER(code) = ?', [$value])
                        ->orWhereRaw('UPPER(name) = ?', [$value]);
                }
            });
        }

        $regionCollection = $regions->get();

        if ($regionCollection->isEmpty()) {
            $this->error('No matching registered regions found in IRMS.');
            return self::FAILURE;
        }

        $summary = [
            'regions_processed' => 0,
            'districts_synced' => 0,
            'schools_synced' => 0,
            'skipped_regions' => [],
            'failed_regions' => [],
        ];

        foreach ($regionCollection as $region) {
            $this->line("Syncing region: {$region->name}");

            try {
                $result = $service->syncRegion($region);
            } catch (\Throwable $exception) {
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
                $this->info("Processed {$result['region']}: {$result['districts_synced']} districts, {$result['schools_synced']} schools");
                continue;
            }

            if ($result['status'] === 'skipped') {
                $summary['skipped_regions'][] = $result['region'];
                $this->warn("Skipped {$result['region']}: {$result['message']}");
                continue;
            }

            $summary['failed_regions'][] = [
                'region' => $result['region'],
                'error' => $result['message'],
            ];
            $this->error("Failed {$result['region']}: {$result['message']}");
        }

        $this->newLine();
        $this->info('NECTA PSLE 2025 school sync completed.');
        $this->line('Regions processed: ' . $summary['regions_processed']);
        $this->line('Districts synced: ' . $summary['districts_synced']);
        $this->line('Schools synced: ' . $summary['schools_synced']);

        if (! empty($summary['skipped_regions'])) {
            $this->warn('Skipped regions with no exact NECTA page match: ' . implode(', ', $summary['skipped_regions']));
        }

        if (! empty($summary['failed_regions'])) {
            $this->warn('Failed regions:');
            foreach ($summary['failed_regions'] as $failedRegion) {
                $this->line("- {$failedRegion['region']}: {$failedRegion['error']}");
            }
        }

        return empty($summary['failed_regions']) ? self::SUCCESS : self::FAILURE;
    }
}
