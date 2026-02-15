<?php

namespace App\Services\Districts;

use App\Models\District;
use App\Models\Region;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistrictImportService
{
    /**
     * Validate CSV file and return preview (Phase 1: Dry-run)
     */
    public function validateCSV(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'CSV file is empty',
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
                'errors' => [],
                'summary' => [],
                'can_import' => false
            ];
        }

        // Normalize headers
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);
        $header = array_map(fn($h) => str_replace(' ', '_', $h), $header);

        $rowNumber = 0;
        $validCount = 0;
        $errorRows = [];
        $errorSummary = [];
        $seenNames = []; // Track duplicates within file

        // Preload all regions for lookups
        $regionsById = Region::pluck('id')->flip()->toArray();
        $regionsByCode = Region::pluck('id', 'code')->toArray();

        // Preload existing district names+region combinations
        $existingDistricts = District::all();
        $existingCombos = [];
        foreach ($existingDistricts as $d) {
            $existingCombos[$d->name . '|' . $d->region_id] = true;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to columns
            $record = $this->mapRowToRecord($row, $header);
            $rowErrors = [];

            // Validate name
            $this->validateName($record['name'] ?? null, $rowErrors);

            // Validate region ID
            $regionId = null;
            $this->validateRegionId($record['region_id'] ?? null, $rowErrors, $regionsById, $regionsByCode);
            
            // Get the resolved region ID for further checks
            if (!empty($record['region_id'])) {
                $regionVal = trim($record['region_id']);
                if (is_numeric($regionVal)) {
                    $regionId = (int)$regionVal;
                } else {
                    $regionId = $regionsByCode[$regionVal] ?? null;
                }
            }

            // Check for duplicates within file (by name+region)
            if (!empty($record['name']) && $regionId) {
                $combo = $record['name'] . '|' . $regionId;
                if (isset($seenNames[$combo])) {
                    $rowErrors[] = "District '{$record['name']}' appears multiple times for this region in file";
                }
                $seenNames[$combo] = true;
            }

            // Check for duplicates in DB (by name+region)
            if (!empty($record['name']) && $regionId) {
                $combo = $record['name'] . '|' . $regionId;
                if (isset($existingCombos[$combo])) {
                    $rowErrors[] = "District '{$record['name']}' already exists in this region";
                }
            }

            // Validate status (optional)
            if (!empty($record['status'])) {
                $this->validateStatus($record['status'] ?? null, $rowErrors);
            }

            // Validate description (optional)
            if (!empty($record['description'])) {
                $this->validateDescription($record['description'] ?? null, $rowErrors);
            }

            // Collect results
            if (empty($rowErrors)) {
                $validCount++;
            } else {
                $errorRows[] = [
                    'row_number' => $rowNumber,
                    'normalized_row' => [
                        'name' => $record['name'] ?? '',
                        'region_id' => $record['region_id'] ?? '',
                        'description' => $record['description'] ?? '',
                        'status' => $record['status'] ?? '',
                    ],
                    'errors' => $this->groupErrorsByField($rowErrors),
                    'primary_error' => reset($rowErrors) ?: 'Unknown error'
                ];

                // Track error types
                foreach ($rowErrors as $err) {
                    $key = strtolower(str_replace(' ', '_', substr($err, 0, 30)));
                    $errorSummary[$key] = ($errorSummary[$key] ?? 0) + 1;
                }
            }
        }

        fclose($handle);

        return [
            'success' => count($errorRows) === 0,
            'message' => count($errorRows) === 0 ? 'All rows valid' : count($errorRows) . ' row(s) have errors',
            'total_rows' => $rowNumber,
            'valid_count' => $validCount,
            'invalid_count' => count($errorRows),
            'errors' => array_slice($errorRows, 0, 100),
            'total_errors' => count($errorRows),
            'summary' => $errorSummary,
            'can_import' => $validCount > 0
        ];
    }

    /**
     * Commit the import (Phase 2)
     */
    public function commitImport(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'CSV file is empty',
                'imported_count' => 0,
                'failed_count' => 0,
                'errors' => []
            ];
        }

        // Normalize headers
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);
        $header = array_map(fn($h) => str_replace(' ', '_', $h), $header);

        $importedCount = 0;
        $failedCount = 0;
        $errors = [];
        $rowNumber = 0;
        $seenNames = [];

        // Preload lookups
        $regions = Region::all()->keyBy('id');
        $regionsCode = Region::all()->keyBy('code');
        $existingDistricts = District::all();

        try {
            DB::transaction(function () use (
                &$importedCount,
                &$failedCount,
                &$errors,
                $handle,
                $header,
                &$rowNumber,
                &$seenNames,
                $regions,
                $regionsCode,
                $existingDistricts
            ) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Map row to columns
                    $record = $this->mapRowToRecord($row, $header);
                    $rowErrors = [];

                    // Validate name
                    if (empty($record['name'])) {
                        $rowErrors[] = 'Name is required';
                    } else if (strlen($record['name']) > 255) {
                        $rowErrors[] = 'Name must be 255 characters or less';
                    }

                    // Lookup region
                    $regionId = null;
                    if (empty($record['region_id'])) {
                        $rowErrors[] = 'Region ID is required';
                    } else {
                        $regionVal = trim($record['region_id']);
                        
                        if (is_numeric($regionVal)) {
                            $regionId = (int)$regionVal;
                            if (!isset($regions[$regionId])) {
                                $rowErrors[] = "Region ID {$regionVal} does not exist";
                            }
                        } else {
                            if (isset($regionsCode[$regionVal])) {
                                $regionId = $regionsCode[$regionVal]->id;
                            } else {
                                $rowErrors[] = "Region code '{$regionVal}' does not exist";
                            }
                        }
                    }

                    // Check for duplicates within file
                    if (!empty($record['name']) && $regionId) {
                        $combo = $record['name'] . '|' . $regionId;
                        if (isset($seenNames[$combo])) {
                            $rowErrors[] = "District name appears multiple times for this region in file";
                        }
                        $seenNames[$combo] = true;
                    }

                    // Check for duplicates in DB
                    if (!empty($record['name']) && $regionId) {
                        $exists = $existingDistricts->where('name', $record['name'])
                            ->where('region_id', $regionId)
                            ->first();
                        if ($exists) {
                            $rowErrors[] = "District already exists in this region";
                        }
                    }

                    // Validate status (optional)
                    $status = 'active'; // default
                    if (!empty($record['status'])) {
                        $statusVal = strtolower(trim($record['status']));
                        if (in_array($statusVal, ['active', 'inactive'])) {
                            $status = $statusVal;
                        } else {
                            $rowErrors[] = 'Status must be active or inactive';
                        }
                    }

                    // Validate description (optional)
                    $description = null;
                    if (!empty($record['description'])) {
                        $desc = trim($record['description']);
                        if (strlen($desc) > 500) {
                            $rowErrors[] = 'Description must be 500 characters or less';
                        } else {
                            $description = $desc;
                        }
                    }

                    // If validation failed, skip this row
                    if (!empty($rowErrors)) {
                        $failedCount++;
                        $errors[] = [
                            'row_number' => $rowNumber,
                            'name' => $record['name'] ?? '',
                            'errors' => $rowErrors
                        ];
                        continue;
                    }

                    // Generate district code
                    $lastDistrict = District::where('region_id', $regionId)
                        ->orderByRaw("CAST(SUBSTR(code, -2) AS UNSIGNED) DESC")
                        ->first();

                    $nextNumber = 1;
                    if ($lastDistrict && $lastDistrict->code) {
                        $lastNumber = (int) substr($lastDistrict->code, -2);
                        $nextNumber = $lastNumber + 1;
                    }

                    $regionCode = $regions[$regionId]->code ?? '';
                    $code = $regionCode . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

                    // Create district
                    try {
                        District::create([
                            'code' => $code,
                            'name' => trim($record['name']),
                            'region_id' => $regionId,
                            'description' => $description,
                            'status' => $status,
                        ]);

                        $importedCount++;

                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = [
                            'row_number' => $rowNumber,
                            'name' => $record['name'] ?? '',
                            'errors' => ['Database error: ' . $e->getMessage()]
                        ];
                    }
                }
            });

            return [
                'success' => $failedCount === 0,
                'message' => $importedCount . ' district(s) imported successfully' . 
                    ($failedCount > 0 ? " ($failedCount failed)" : ''),
                'imported_count' => $importedCount,
                'failed_count' => $failedCount,
                'errors' => array_slice($errors, 0, 100),
                'total_errors' => count($errors),
                'summary' => [
                    'total_processed' => $rowNumber,
                    'total_succeeded' => $importedCount,
                    'total_failed' => $failedCount
                ]
            ];

        } catch (\Exception $e) {
            Log::error('District import commit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            fclose($handle);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'imported_count' => $importedCount,
                'failed_count' => $failedCount,
                'errors' => []
            ];
        }
    }

    /**
     * Map CSV row to associative array
     */
    private function mapRowToRecord(array $row, array $headers): array
    {
        $record = [];
        foreach ($headers as $idx => $header) {
            $record[$header] = trim($row[$idx] ?? '');
        }
        return $record;
    }

    /**
     * Validate district name
     */
    private function validateName(?string $name, array &$errors): void
    {
        if (empty($name)) {
            $errors[] = 'Name is required';
            return;
        }

        $name = trim($name);

        if (strlen($name) > 255) {
            $errors[] = 'Name must be 255 characters or less';
        }
    }

    /**
     * Validate region ID
     */
    private function validateRegionId(?string $regionId, array &$errors, array $regionsById, array $regionsByCode): void
    {
        if (empty($regionId)) {
            $errors[] = 'Region ID is required';
            return;
        }

        $regionVal = trim($regionId);

        if (is_numeric($regionVal)) {
            if (!isset($regionsById[(int)$regionVal])) {
                $errors[] = "Region ID {$regionVal} does not exist";
            }
        } else {
            if (!isset($regionsByCode[$regionVal])) {
                $errors[] = "Region code '{$regionVal}' does not exist";
            }
        }
    }

    /**
     * Validate status value
     */
    private function validateStatus(?string $status, array &$errors): void
    {
        if (empty($status)) {
            return;
        }

        $statusVal = strtolower(trim($status));

        if (!in_array($statusVal, ['active', 'inactive'])) {
            $errors[] = 'Status must be active or inactive';
        }
    }

    /**
     * Validate description
     */
    private function validateDescription(?string $description, array &$errors): void
    {
        if (empty($description)) {
            return;
        }

        $desc = trim($description);

        if (strlen($desc) > 500) {
            $errors[] = 'Description must be 500 characters or less';
        }
    }

    /**
     * Group errors by field name
     */
    private function groupErrorsByField(array $errors): array
    {
        $grouped = [];
        foreach ($errors as $error) {
            if (preg_match('/^(\w+)/', $error, $matches)) {
                $field = strtolower($matches[1]);
                if (!isset($grouped[$field])) {
                    $grouped[$field] = [];
                }
                $grouped[$field][] = $error;
            } else {
                if (!isset($grouped['general'])) {
                    $grouped['general'] = [];
                }
                $grouped['general'][] = $error;
            }
        }
        return $grouped;
    }
}
