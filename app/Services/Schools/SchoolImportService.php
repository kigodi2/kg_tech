<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\Region;
use App\Models\District;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolImportService
{
    /**
     * Validate CSV file and return preview (Phase 1: Dry-run)
     *
     * Returns:
     * - success (bool)
     * - total_rows (int)
     * - valid_count (int)
     * - invalid_count (int)
     * - errors (array) - details per failed row
     * - summary (array) - counts by error type
     * - can_import (bool) - true if at least one valid row
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
        $seenCodes = []; // Track duplicates within file

        // Preload all regions and districts for lookups
        $regionsById = Region::pluck('id')->flip()->toArray();
        $regionsByCode = Region::pluck('id', 'code')->toArray();
        $districtsById = District::pluck('id')->flip()->toArray();
        $districtsByCode = District::pluck('id', 'code')->toArray();

        // Preload existing school codes for duplicate check
        $existingCodes = School::pluck('code')->flip()->toArray();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to columns
            $record = $this->mapRowToRecord($row, $header);
            $rowErrors = [];

            // Validate each field
            $this->validateCode($record['code'] ?? null, $rowErrors, $seenCodes, $existingCodes, $rowNumber);
            $this->validateName($record['name'] ?? null, $rowErrors);
            $this->validateRegionId($record['region_id'] ?? null, $rowErrors, $regionsById, $regionsByCode);
            
            // District ID is optional
            if (!empty($record['district_id'])) {
                $this->validateDistrictId($record['district_id'] ?? null, $rowErrors, $districtsById, $districtsByCode);
            }

            // Ownership is optional
            if (!empty($record['ownership'])) {
                $this->validateOwnership($record['ownership'] ?? null, $rowErrors);
            }

            // Collect results
            if (empty($rowErrors)) {
                $validCount++;
                $seenCodes[$record['code']] = true;
            } else {
                $errorRows[] = [
                    'row_number' => $rowNumber,
                    'normalized_row' => [
                        'code' => $record['code'] ?? '',
                        'name' => $record['name'] ?? '',
                        'region_id' => $record['region_id'] ?? '',
                        'district_id' => $record['district_id'] ?? '',
                        'ownership' => $record['ownership'] ?? '',
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
            'errors' => array_slice($errorRows, 0, 100), // Limit to first 100 for display
            'total_errors' => count($errorRows),
            'summary' => $errorSummary,
            'can_import' => $validCount > 0
        ];
    }

    /**
     * Re-validate and commit the import (Phase 2)
     *
     * Returns:
     * - success (bool)
     * - message (string)
     * - imported_count (int)
     * - errors (array)
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
                'skipped_count' => 0,
                'failed_count' => 0,
                'errors' => []
            ];
        }

        // Normalize headers
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);
        $header = array_map(fn($h) => str_replace(' ', '_', $h), $header);

        $importedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];
        $rowNumber = 0;
        $seenCodes = [];

        // Preload lookups
        $regions = Region::all()->keyBy('id');
        $regionsCode = Region::all()->keyBy('code');
        $districts = District::all()->keyBy('id');
        $districtsCode = District::all()->keyBy('code');
        $existingSchools = School::all()->keyBy('code');

        try {
            DB::transaction(function () use (
                &$importedCount,
                &$skippedCount,
                &$failedCount,
                &$errors,
                $handle,
                $header,
                &$rowNumber,
                &$seenCodes,
                $regions,
                $regionsCode,
                $districts,
                $districtsCode,
                $existingSchools
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

                    // Validate
                    if (empty($record['code'])) {
                        $rowErrors[] = 'Code is required';
                    }
                    if (empty($record['name'])) {
                        $rowErrors[] = 'Name is required';
                    }

                    // Check for duplicates within file
                    if ($record['code'] && isset($seenCodes[$record['code']])) {
                        $rowErrors[] = 'Code appears multiple times in file';
                    }

                    // Check for duplicates in DB
                    if ($record['code'] && isset($existingSchools[$record['code']])) {
                        $rowErrors[] = 'Code already exists in database';
                    }

                    // Lookup region
                    $regionId = null;
                    if (!empty($record['region_id'])) {
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
                    } else {
                        $rowErrors[] = 'Region ID is required';
                    }

                    // Lookup district (optional)
                    $districtId = null;
                    if (!empty($record['district_id'])) {
                        $districtVal = trim($record['district_id']);
                        
                        if (is_numeric($districtVal)) {
                            $districtId = (int)$districtVal;
                            if (!isset($districts[$districtId])) {
                                $rowErrors[] = "District ID {$districtVal} does not exist";
                            }
                        } else {
                            if (isset($districtsCode[$districtVal])) {
                                $districtId = $districtsCode[$districtVal]->id;
                            } else {
                                $rowErrors[] = "District code '{$districtVal}' does not exist";
                            }
                        }
                    }

                    // Validate ownership (optional)
                    $ownership = null;
                    if (!empty($record['ownership'])) {
                        $ownershipVal = strtoupper(trim($record['ownership']));
                        if (in_array($ownershipVal, ['GOVERNMENT', 'NON-GOVERNMENT'])) {
                            $ownership = $ownershipVal;
                        } else {
                            $rowErrors[] = 'Ownership must be GOVERNMENT or NON-GOVERNMENT';
                        }
                    }

                    // If validation failed, skip this row
                    if (!empty($rowErrors)) {
                        $failedCount++;
                        $errors[] = [
                            'row_number' => $rowNumber,
                            'code' => $record['code'] ?? '',
                            'errors' => $rowErrors
                        ];
                        continue;
                    }

                    // Create school
                    try {
                        School::create([
                            'code' => trim($record['code']),
                            'name' => trim($record['name']),
                            'region_id' => $regionId,
                            'district_id' => $districtId,
                            'ownership' => $ownership ?? 'GOVERNMENT',
                            'is_active' => true,
                        ]);

                        $importedCount++;
                        $seenCodes[$record['code']] = true;

                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = [
                            'row_number' => $rowNumber,
                            'code' => $record['code'] ?? '',
                            'errors' => ['Database error: ' . $e->getMessage()]
                        ];
                    }
                }
            });

            return [
                'success' => $failedCount === 0,
                'message' => $importedCount . ' school(s) imported successfully' . 
                    ($failedCount > 0 ? " ($failedCount failed)" : ''),
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
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
            Log::error('School import commit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            fclose($handle);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'failed_count' => $failedCount,
                'errors' => []
            ];
        }
    }

    /**
     * Map CSV row to associative array using headers
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
     * Validate school code
     */
    private function validateCode(?string $code, array &$errors, array $seenCodes, array $existingCodes, int $rowNumber): void
    {
        if (empty($code)) {
            $errors[] = 'Code is required';
            return;
        }

        $code = trim($code);

        if (strlen($code) > 30) {
            $errors[] = 'Code must be 30 characters or less';
            return;
        }

        if (isset($seenCodes[$code])) {
            $errors[] = "Code '{$code}' appears multiple times in file";
        }

        if (isset($existingCodes[$code])) {
            $errors[] = "Code '{$code}' already exists in database";
        }
    }

    /**
     * Validate school name
     */
    private function validateName(?string $name, array &$errors): void
    {
        if (empty($name)) {
            $errors[] = 'Name is required';
            return;
        }

        $name = trim($name);

        if (strlen($name) > 150) {
            $errors[] = 'Name must be 150 characters or less';
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
     * Validate district ID
     */
    private function validateDistrictId(?string $districtId, array &$errors, array $districtsById, array $districtsByCode): void
    {
        if (empty($districtId)) {
            return; // District ID is optional
        }

        $districtVal = trim($districtId);

        if (is_numeric($districtVal)) {
            if (!isset($districtsById[(int)$districtVal])) {
                $errors[] = "District ID {$districtVal} does not exist";
            }
        } else {
            if (!isset($districtsByCode[$districtVal])) {
                $errors[] = "District code '{$districtVal}' does not exist";
            }
        }
    }

    /**
     * Validate ownership value
     */
    private function validateOwnership(?string $ownership, array &$errors): void
    {
        if (empty($ownership)) {
            return; // Ownership is optional
        }

        $ownershipVal = strtoupper(trim($ownership));

        if (!in_array($ownershipVal, ['GOVERNMENT', 'NON-GOVERNMENT'])) {
            $errors[] = 'Ownership must be GOVERNMENT or NON-GOVERNMENT';
        }
    }

    /**
     * Group errors by field name
     */
    private function groupErrorsByField(array $errors): array
    {
        $grouped = [];
        foreach ($errors as $error) {
            // Try to extract field name from error message
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
