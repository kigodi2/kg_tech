<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\School;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Services\IndexNumber\IndexNumberValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistrictCandidateImportController extends Controller
{
    /**
     * Import candidates by district
     * Auto-registers missing schools
     */
    public function importByDistrict(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'district_id' => 'required|exists:districts,id',
        ]);

        $district = District::findOrFail($request->district_id);

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));
            
            // Parse headers
            $headers = array_shift($data);
            $headers = array_map('strtolower', $headers);
            $headers = array_map('trim', $headers);

            // Map CSV columns
            $columnMap = $this->mapColumns($headers);

            // Statistics
            $stats = [
                'schools_registered' => 0,
                'schools_skipped' => 0,
                'candidates_imported' => 0,
                'candidates_skipped' => 0,
                'errors' => []
            ];

            // Get existing schools in this district
            $districtSchools = School::where('district_id', $district->id)->pluck('code')->toArray();

            // Process each row
            foreach ($data as $row) {
                if (empty(array_filter($row))) continue;

                try {
                    $record = array_combine($headers, $row);
                    $record = array_map('trim', $record);

                    $schoolCode = $record[$columnMap['school_code']] ?? null;
                    $candidateId = $record[$columnMap['candidate_id']] ?? null;
                    $fullName = $record[$columnMap['full_name']] ?? null;
                    $gender = $record[$columnMap['gender']] ?? null;
                    $examType = $record[$columnMap['exam_type']] ?? 'ACSEE';
                    $examYear = $record[$columnMap['exam_year']] ?? null;

                    // Validate required fields
                    if (!$schoolCode || !$candidateId || !$fullName || !$gender) {
                        continue;
                    }

                    // Register school if not exists
                    $school = School::where('code', $schoolCode)->first();
                    if (!$school) {
                        $school = School::create([
                            'code' => $schoolCode,
                            'name' => "Imported School - $schoolCode",
                            'district_id' => $district->id,
                            'region_id' => $district->region_id,
                            'ownership' => 'GOVERNMENT',
                            'is_active' => true,
                        ]);
                        $stats['schools_registered']++;
                    } else {
                        if (!in_array($school->code, $districtSchools)) {
                            // Update school's district if different
                            $school->update(['district_id' => $district->id]);
                            $stats['schools_registered']++;
                        } else {
                            $stats['schools_skipped']++;
                        }
                    }

                    // Check if candidate already exists
                     $candidate = Candidate::where('candidate_id', $candidateId)->first();
                     
                     if ($candidate) {
                         $stats['candidates_skipped']++;
                         continue;
                     }

                     // Auto-detect candidate type from index number prefix
                     $validator = new IndexNumberValidator();
                     $parsed = $validator->parse($candidateId);
                     $candidateType = $parsed?->candidate_type ?? 'SCHOOL'; // Default to SCHOOL if parsing fails

                     // Create candidate
                     $candidate = Candidate::create([
                         'school_id' => $school->id,
                         'candidate_id' => $candidateId,
                         'full_name' => $fullName,
                         'gender' => strtoupper($gender[0]),
                         'candidate_type' => $candidateType,
                     ]);

                    // Register for ACSEE if specified
                    if (strtoupper($examType) === 'ACSEE') {
                        $this->registerForACSEE($candidate, $examYear);
                    }

                    $stats['candidates_imported']++;

                } catch (\Exception $e) {
                    $stats['errors'][] = "Row error: " . $e->getMessage();
                    continue;
                }
            }

            DB::commit();

            return response()->json($stats, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 400);
        }
    }

    /**
     * Map CSV columns to expected column names
     */
    private function mapColumns($headers)
    {
        $map = [];
        
        foreach ($headers as $header) {
            if (in_array($header, ['school_code', 'school', 'center_no', 'centre_no'])) {
                $map['school_code'] = $header;
            } elseif (in_array($header, ['candidate_id', 'index_number', 'candidate_no'])) {
                $map['candidate_id'] = $header;
            } elseif (in_array($header, ['full_name', 'candidate_full_name', 'name'])) {
                $map['full_name'] = $header;
            } elseif (in_array($header, ['gender', 'sex'])) {
                $map['gender'] = $header;
            } elseif (in_array($header, ['exam_type', 'examination_type', 'exam'])) {
                $map['exam_type'] = $header;
            } elseif (in_array($header, ['exam_year', 'year', 'year_label'])) {
                $map['exam_year'] = $header;
            }
        }

        return $map;
    }

    /**
     * Register candidate for ACSEE
     */
    private function registerForACSEE(Candidate $candidate, ?string $examYear = null)
    {
        try {
            $examType = ExamType::where('code', 'ACSEE')->first();
            if (!$examType) return;

            // Resolve exam year
            if ($examYear === null) {
                $examYear = ExamYear::active()->first();
            } elseif (is_numeric($examYear)) {
                $examYear = ExamYear::where('year_label', $examYear)->first();
            }

            if (!$examYear) return;

            // Check if already registered
            $existingReg = CandidateExamRegistration::where('candidate_id', $candidate->id)
                ->where('exam_type_id', $examType->id)
                ->where('exam_year_id', $examYear->id)
                ->first();

            if ($existingReg) return;

            // Create registration
            CandidateExamRegistration::create([
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'year' => (int)$examYear->year_label,
                'registration_number' => 'REG-' . uniqid(),
                'is_active' => true,
                'is_verified' => false,
            ]);

        } catch (\Exception $e) {
            // Log but don't fail import
            \Log::warning('ACSEE registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Get registered schools in a district
     */
    public function getDistrictSchools($districtId)
    {
        $schools = School::where('district_id', $districtId)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $schools
        ]);
    }

    /**
     * Get all districts with school counts
     */
    public function getDistricts()
    {
        $districts = District::withCount('schools')
            ->orderBy('name')
            ->get()
            ->map(function ($district) {
                return [
                    'id' => $district->id,
                    'name' => $district->name,
                    'schools_count' => $district->schools_count,
                ];
            });

        return response()->json([
            'data' => $districts
        ]);
    }
}
