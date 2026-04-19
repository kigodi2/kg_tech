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
        $updateCount = 0;
        $errorRows = [];
        $errorSummary = [];
        $seenCodes = []; // Track duplicates within file

        // Preload all regions and districts for lookups
        $regionsById = Region::pluck('id')->flip()->toArray();
        $regionsByCode = Region::pluck('id', 'code')->toArray();
        $districtsById = District::pluck('id')->flip()->toArray();
        $districtsByCode = District::pluck('id', 'code')->toArray();

        // Preload existing schools so matching codes can be treated as updates
        $existingSchools = School::query()->get()->keyBy('code');

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
            $existingSchool = null;
            if (!empty($record['code'])) {
                $existingSchool = $existingSchools->get(trim($record['code']));
            }

            $this->validateCode($record['code'] ?? null, $rowErrors, $seenCodes);
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
                $normalizedCode = trim((string) ($record['code'] ?? ''));
                $seenCodes[$normalizedCode] = true;

                if ($existingSchool) {
                    $updateCount++;
                }
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
            'update_count' => $updateCount,
            'new_count' => max(0, $validCount - $updateCount),
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
                'updated_count' => 0,
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
        $updatedCount = 0;
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
                &$updatedCount,
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

                    $existingSchool = null;
                    if (!empty($record['code'])) {
                        $existingSchool = $existingSchools->get(trim($record['code']));
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

                    // Update existing school when the code matches; otherwise create a new school
                    try {
                        $normalizedCode = trim($record['code']);
                        $normalizedName = trim($record['name']);

                        if ($existingSchool) {
                            if ($existingSchool->name !== $normalizedName) {
                                $existingSchool->update([
                                    'name' => $normalizedName,
                                ]);
                            }

                            $updatedCount++;
                        } else {
                            $school = School::create([
                                'code' => $normalizedCode,
                                'name' => $normalizedName,
                                'region_id' => $regionId,
                                'district_id' => $districtId,
                                'ownership' => $ownership ?? 'GOVERNMENT',
                                'is_active' => true,
                            ]);

                            $existingSchools->put($normalizedCode, $school);
                            $importedCount++;
                        }

                        $seenCodes[$normalizedCode] = true;

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
                'message' => $this->buildCompletionMessage($importedCount, $updatedCount, $failedCount),
                'imported_count' => $importedCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'failed_count' => $failedCount,
                'errors' => array_slice($errors, 0, 100),
                'total_errors' => count($errors),
                'summary' => [
                    'total_processed' => $rowNumber,
                    'total_succeeded' => $importedCount + $updatedCount,
                    'total_updated' => $updatedCount,
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
                'updated_count' => $updatedCount,
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
    private function validateCode(?string $code, array &$errors, array $seenCodes): void
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

    private function buildCompletionMessage(int $importedCount, int $updatedCount, int $failedCount): string
    {
        $parts = [];

        if ($importedCount > 0) {
            $parts[] = $importedCount . ' school(s) imported';
        }

        if ($updatedCount > 0) {
            $parts[] = $updatedCount . ' school(s) updated';
        }

        if (empty($parts)) {
            $parts[] = 'No schools imported or updated';
        }

        $message = implode(', ', $parts) . ' successfully';

        if ($failedCount > 0) {
            $message .= " ($failedCount failed)";
        }

        return $message;
    }
}
