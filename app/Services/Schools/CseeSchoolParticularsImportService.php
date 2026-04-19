<?php

namespace App\Services\Schools;

use App\Models\District;
use App\Models\Region;
use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CseeSchoolParticularsImportService
{
    private ?int $fallbackRegionId = null;
    private ?Collection $referenceSchools = null;
    private ?Collection $referenceByCode = null;
    private ?Collection $referenceByRegistrationNumber = null;
    private ?Collection $referenceByNormalizedName = null;

    public function importMissingParticulars(): array
    {
        $processed = 0;
        $updated = 0;

        School::query()
            ->where('source_system', NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
            ->where(function ($query) {
                $query->whereNull('district_id')
                    ->orWhereNull('registration_number')
                    ->orWhere('ownership', 'GOVERNMENT');

                if ($this->fallbackRegionId()) {
                    $query->orWhere('region_id', $this->fallbackRegionId());
                }
            })
            ->orderBy('id')
            ->chunkById(200, function ($schools) use (&$processed, &$updated) {
                foreach ($schools as $school) {
                    $processed++;

                    if ($this->applyReferenceData($school)) {
                        $updated++;
                    }
                }
            });

        return [
            'source' => NectaCsee2025CentreSyncService::SOURCE_SYSTEM,
            'schools_processed' => $processed,
            'schools_updated' => $updated,
        ];
    }

    public function importFromCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return [
                'source' => NectaCsee2025CentreSyncService::SOURCE_SYSTEM,
                'rows_processed' => 0,
                'rows_updated' => 0,
                'rows_failed' => 0,
                'errors' => ['CSV file is empty.'],
            ];
        }

        $normalizedHeader = collect($header)
            ->map(fn ($value) => str_replace(' ', '_', strtolower(trim((string) $value))))
            ->values()
            ->all();

        $processed = 0;
        $updated = 0;
        $created = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row, fn ($value) => trim((string) $value) !== ''))) {
                continue;
            }

            $processed++;
            $record = $this->mapCsvRow($normalizedHeader, $row);

            try {
                $result = $this->applyCsvRow($record);
                $updated++;

                if ($result['created'] ?? false) {
                    $created++;
                }
            } catch (\Throwable $exception) {
                $failed++;
                $errors[] = "Row {$processed}: {$exception->getMessage()}";
            }
        }

        fclose($handle);

        return [
            'source' => NectaCsee2025CentreSyncService::SOURCE_SYSTEM,
            'rows_processed' => $processed,
            'rows_updated' => $updated,
            'rows_created' => $created,
            'rows_replaced' => max(0, $updated - $created),
            'rows_skipped' => 0,
            'rows_failed' => $failed,
            'errors' => $errors,
            'skips' => [],
        ];
    }

    public function resolveAttributes(string $code, string $name, ?int $existingSchoolId = null): array
    {
        $referenceSchool = $this->findReferenceSchool($code, $name);

        return [
            'registration_number' => $this->resolveRegistrationNumber($code, $existingSchoolId),
            'district_id' => $referenceSchool?->district_id,
            'council_id' => $referenceSchool?->council_id,
            'region_id' => $referenceSchool?->region_id,
            'ownership' => $referenceSchool?->ownership ?: 'GOVERNMENT',
        ];
    }

    public function applyReferenceData(School $school): bool
    {
        $attributes = $this->resolveAttributes($school->code, $school->name, $school->id);

        $updates = [
            'registration_number' => $attributes['registration_number'],
        ];

        foreach (['district_id', 'council_id', 'region_id', 'ownership'] as $field) {
            if (! is_null($attributes[$field])) {
                $updates[$field] = $attributes[$field];
            }
        }

        if ($this->isFallbackRegion((int) $school->region_id) && ! is_null($attributes['region_id'])) {
            $updates['region_id'] = $attributes['region_id'];
        }

        $dirty = false;

        foreach ($updates as $field => $value) {
            if ($school->{$field} !== $value) {
                $school->{$field} = $value;
                $dirty = true;
            }
        }

        if ($dirty) {
            $school->save();
        }

        return $dirty;
    }

    private function findReferenceSchool(string $code, string $name): ?School
    {
        $normalizedName = $this->normalizedLookupName($name);
        $this->loadReferenceMaps();

        return $this->referenceByNormalizedName?->get($normalizedName)
            ?? $this->referenceByCode?->get($code)
            ?? $this->referenceByRegistrationNumber?->get($code);
    }

    private function resolveRegistrationNumber(string $code, ?int $existingSchoolId = null): ?string
    {
        $conflictExists = School::query()
            ->where('registration_number', $code)
            ->when($existingSchoolId, fn ($query) => $query->where('id', '!=', $existingSchoolId))
            ->exists();

        return $conflictExists ? null : $code;
    }

    private function normalizedLookupName(string $name): string
    {
        return Str::upper(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
    }

    private function isFallbackRegion(int $regionId): bool
    {
        $fallbackRegionId = $this->fallbackRegionId();

        return $fallbackRegionId ? $regionId === $fallbackRegionId : false;
    }

    private function fallbackRegionId(): ?int
    {
        if ($this->fallbackRegionId !== null) {
            return $this->fallbackRegionId;
        }

        $this->fallbackRegionId = School::query()
            ->where('source_system', NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
            ->whereHas('region', fn ($query) => $query->where('code', 'CSEE-UNK'))
            ->value('region_id');

        return $this->fallbackRegionId;
    }

    private function ensureFallbackRegionId(): int
    {
        $fallbackRegionId = $this->fallbackRegionId();

        if ($fallbackRegionId) {
            return $fallbackRegionId;
        }

        $region = Region::query()->firstOrCreate(
            ['code' => 'CSEE-UNK'],
            [
                'name' => 'UNASSIGNED CSEE CENTRES',
                'description' => 'Fallback region used for CSEE centre sync records pending region enrichment.',
                'is_active' => true,
            ]
        );

        $this->fallbackRegionId = (int) $region->id;

        return $this->fallbackRegionId;
    }

    private function mapCsvRow(array $header, array $row): array
    {
        $record = [];

        foreach ($header as $index => $column) {
            $record[$column] = trim((string) ($row[$index] ?? ''));
        }

        return $record;
    }

    private function applyCsvRow(array $record): array
    {
        $matchValue = ($record['code'] ?? '') ?: ($record['registration_number'] ?? '') ?: ($record['name'] ?? '') ?: null;

        if (! $matchValue) {
            throw new \RuntimeException('Missing code, registration_number, or name for row matching.');
        }

        $code = trim((string) ($record['code'] ?? ''));
        $registrationNumber = trim((string) ($record['registration_number'] ?? ''));
        $name = trim((string) ($record['name'] ?? ''));

        $school = $this->findCsvMatchSchool($code, $registrationNumber, $name);

        if (! $school) {
            $school = $this->createSchoolFromCsvRecord($record, $code, $registrationNumber, $name, $matchValue);
            $dirty = true;
            $created = true;
        } else {
            $dirty = false;
            $created = false;
        }

        if ($school->source_system !== NectaCsee2025CentreSyncService::SOURCE_SYSTEM) {
            $school->source_system = NectaCsee2025CentreSyncService::SOURCE_SYSTEM;
            $dirty = true;
        }

        if (! empty($record['ownership'])) {
            $ownership = strtoupper($record['ownership']);

            if (! in_array($ownership, ['GOVERNMENT', 'NON-GOVERNMENT'], true)) {
                throw new \RuntimeException("Invalid ownership '{$record['ownership']}'.");
            }

            if ($school->ownership !== $ownership) {
                $school->ownership = $ownership;
                $dirty = true;
            }
        }

        $regionId = $this->resolveRegionId($record);
        if ($regionId && (int) $school->region_id !== $regionId) {
            $school->region_id = $regionId;
            $dirty = true;
        }

        $districtId = $this->resolveDistrictId($record, $regionId);
        if ($districtId && (int) $school->district_id !== $districtId) {
            $school->district_id = $districtId;
            $dirty = true;
        }

        if ($dirty) {
            $school->save();
        }

        return [
            'created' => $created,
            'changed' => $dirty,
        ];
    }

    private function createSchoolFromCsvRecord(
        array $record,
        string $code,
        string $registrationNumber,
        string $name,
        string $matchValue
    ): School {
        $resolvedRegionId = $this->resolveRegionId($record);
        $resolvedDistrictId = $this->resolveDistrictId($record, $resolvedRegionId);
        $ownership = ! empty($record['ownership']) ? strtoupper((string) $record['ownership']) : 'GOVERNMENT';

        if (! in_array($ownership, ['GOVERNMENT', 'NON-GOVERNMENT'], true)) {
            throw new \RuntimeException("Invalid ownership '{$record['ownership']}'.");
        }

        if ($code === '') {
            throw new \RuntimeException("Unable to create missing CSEE school for '{$matchValue}' without a code.");
        }

        return School::create([
            'code' => $code,
            'registration_number' => $registrationNumber !== '' ? $registrationNumber : $this->resolveRegistrationNumber($code),
            'source_system' => NectaCsee2025CentreSyncService::SOURCE_SYSTEM,
            'name' => $name !== '' ? $name : $matchValue,
            'region_id' => $resolvedRegionId ?? $this->ensureFallbackRegionId(),
            'district_id' => $resolvedDistrictId,
            'ownership' => $ownership,
            'school_type' => School::TYPE_SECONDARY,
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);
    }

    private function findCsvMatchSchool(string $code, string $registrationNumber, string $name): ?School
    {
        $normalizedName = $name !== '' ? $this->normalizedLookupName($name) : '';

        if ($code !== '') {
            $school = School::query()
                ->where('source_system', NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
                ->where('code', $code)
                ->first();

            if ($school) {
                return $school;
            }
        }

        if ($registrationNumber !== '') {
            $school = School::query()
                ->where('source_system', NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
                ->where('registration_number', $registrationNumber)
                ->first();

            if ($school) {
                return $school;
            }
        }

        if ($normalizedName !== '') {
            $school = School::query()
                ->where('source_system', NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
                ->whereRaw('UPPER(TRIM(name)) = ?', [$normalizedName])
                ->first();

            if ($school) {
                return $school;
            }
        }

        if ($code !== '') {
            $school = School::query()
                ->where('code', $code)
                ->first();

            if ($school) {
                return $school;
            }
        }

        if ($registrationNumber !== '') {
            $school = School::query()
                ->where('registration_number', $registrationNumber)
                ->first();

            if ($school) {
                return $school;
            }
        }

        if ($normalizedName !== '') {
            return School::query()
                ->whereRaw('UPPER(TRIM(name)) = ?', [$normalizedName])
                ->first();
        }

        return null;
    }

    private function resolveRegionId(array $record): ?int
    {
        $value = ($record['region_id'] ?? '') ?: ($record['region_code'] ?? '') ?: ($record['region'] ?? '') ?: ($record['region_name'] ?? '') ?: null;

        if (! $value) {
            return null;
        }

        if (ctype_digit($value)) {
            return Region::query()->whereKey((int) $value)->value('id');
        }

        return Region::query()
            ->where('code', $value)
            ->orWhereRaw('UPPER(TRIM(name)) = ?', [$this->normalizedLookupName($value)])
            ->value('id');
    }

    private function resolveDistrictId(array $record, ?int $regionId = null): ?int
    {
        $value = ($record['district_id'] ?? '') ?: ($record['district_code'] ?? '') ?: ($record['district'] ?? '') ?: ($record['district_name'] ?? '') ?: null;

        if (! $value) {
            return null;
        }

        $districtQuery = District::query();

        if ($regionId) {
            $districtQuery->where('region_id', $regionId);
        }

        if (ctype_digit($value)) {
            return $districtQuery->whereKey((int) $value)->value('id');
        }

        $normalizedValue = $this->normalizedLookupName($value);

        return (clone $districtQuery)
            ->where(function ($query) use ($value, $normalizedValue) {
                $query->where('code', $value)
                    ->orWhereRaw('UPPER(TRIM(name)) = ?', [$normalizedValue]);
            })
            ->value('id');
    }

    private function loadReferenceMaps(): void
    {
        if ($this->referenceSchools instanceof Collection) {
            return;
        }

        $this->referenceSchools = School::query()
            ->where('source_system', '!=', NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
            ->get(['id', 'code', 'registration_number', 'name', 'district_id', 'council_id', 'region_id', 'ownership']);

        $this->referenceByCode = $this->referenceSchools
            ->filter(fn (School $school) => filled($school->code))
            ->keyBy(fn (School $school) => $school->code);

        $this->referenceByRegistrationNumber = $this->referenceSchools
            ->filter(fn (School $school) => filled($school->registration_number))
            ->keyBy(fn (School $school) => $school->registration_number);

        $this->referenceByNormalizedName = $this->referenceSchools
            ->filter(fn (School $school) => filled($school->name))
            ->keyBy(fn (School $school) => $this->normalizedLookupName($school->name));
    }
}
