<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCombinationRequest;
use App\Http\Requests\UpdateCombinationRequest;
use App\Models\ExamType;
use App\Models\Combination;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CombinationController extends Controller
{
    /**
     * List all combinations for an exam type with pagination and filtering
     * 
     * GET /api/exam-types/{code}/combinations
     */
    public function index($code, Request $request): JsonResponse
    {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();

            $query = $examType->combinations()
                ->with('subjects')
                ->orderBy('category')
                ->orderBy('code');

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $query->search($request->search);
            }

            // Apply category filter
            if ($request->has('category') && !empty($request->category)) {
                $query->byCategory($request->category);
            }

            // Paginate results
            $pageSize = (int) $request->get('page_size', 25);
            $combinations = $query->paginate($pageSize);

            // Format response
            $data = $combinations->map(function ($combo) {
                return [
                    'id' => $combo->id,
                    'code' => $combo->code,
                    'category' => $combo->category,
                    'description' => $combo->description,
                    'subject_count' => $combo->subject_count,
                    'subjects' => $combo->subjects->map(function ($subject) {
                        return [
                            'id' => $subject->id,
                            'code' => $subject->code,
                            'name' => $subject->name,
                            'category' => $subject->category,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $combinations->currentPage(),
                    'per_page' => $combinations->perPage(),
                    'total' => $combinations->total(),
                    'total_pages' => $combinations->lastPage(),
                    'has_previous' => $combinations->currentPage() > 1,
                    'has_next' => $combinations->hasMorePages(),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading combinations: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new combination
     * 
     * POST /api/exam-types/{code}/combinations
     */
    public function store($code, StoreCombinationRequest $request): JsonResponse
    {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();

            DB::beginTransaction();

            // Create combination
            $combination = $examType->combinations()->create([
                'code' => $request->code,
                'category' => $request->category,
                'description' => $request->description,
            ]);

            // Attach subjects
            if ($request->has('subject_ids') && !empty($request->subject_ids)) {
                $combination->syncSubjects($request->subject_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Combination created successfully',
                'data' => [
                    'id' => $combination->id,
                    'code' => $combination->code,
                    'category' => $combination->category,
                    'description' => $combination->description,
                    'subjects' => $combination->subjects->map(fn ($s) => [
                        'id' => $s->id,
                        'code' => $s->code,
                        'name' => $s->name,
                    ]),
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating combination: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a combination
     * 
     * PUT /api/exam-types/{code}/combinations/{id}
     */
    public function update($code, $id, UpdateCombinationRequest $request): JsonResponse
    {
        try {
            $combination = Combination::findOrFail($id);

            // Verify combination belongs to the exam type
            if ($combination->exam_type->code !== $code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combination does not belong to this exam type',
                ], 403);
            }

            DB::beginTransaction();

            // Update fields
            $combination->update([
                'code' => $request->code,
                'category' => $request->category,
                'description' => $request->description,
            ]);

            // Sync subjects
            if ($request->has('subject_ids')) {
                $combination->syncSubjects($request->subject_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Combination updated successfully',
                'data' => [
                    'id' => $combination->id,
                    'code' => $combination->code,
                    'category' => $combination->category,
                    'description' => $combination->description,
                    'subjects' => $combination->subjects->map(fn ($s) => [
                        'id' => $s->id,
                        'code' => $s->code,
                        'name' => $s->name,
                    ]),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Combination not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating combination: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a combination
     * 
     * DELETE /api/exam-types/{code}/combinations/{id}
     */
    public function destroy($code, $id): JsonResponse
    {
        try {
            $combination = Combination::findOrFail($id);

            // Verify combination belongs to the exam type
            if ($combination->exam_type->code !== $code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combination does not belong to this exam type',
                ], 403);
            }

            $combination->delete();

            return response()->json([
                'success' => true,
                'message' => 'Combination deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Combination not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting combination: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Import combinations from CSV
     * 
     * POST /api/exam-types/{code}/combinations/import
     */
    public function import($code, Request $request): JsonResponse
    {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();

            // Validate file
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
            ]);

            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not read file',
                ], 400);
            }

            // Read header
            $headers = fgetcsv($handle, 1000, ',');
            if (!$headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid CSV format: missing header',
                ], 400);
            }

            $importedCount = 0;
            $errors = [];

            DB::beginTransaction();

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $data = array_combine($headers, $row);

                    // Create or update combination
                    $combination = $examType->combinations()->updateOrCreate(
                        ['code' => $data['CODE'] ?? ''],
                        [
                            'category' => $data['CATEGORY'] ?? 'ARTS',
                            'description' => $data['DESCRIPTION'] ?? '',
                        ]
                    );

                    // Attach subjects by code
                    if (!empty($data['SUBJECT_CODES'])) {
                        $codes = array_map('trim', explode(',', $data['SUBJECT_CODES']));
                        $subjects = Subject::whereIn('code', $codes)->get();
                        $combination->syncSubjects($subjects->pluck('id')->toArray());
                    }

                    $importedCount++;
                } catch (\Exception $e) {
                    $rowNum = $importedCount + 1;
                    $errors[] = "Row {$rowNum}: {$e->getMessage()}";
                }
            }

            fclose($handle);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$importedCount} combinations imported successfully",
                'imported_count' => $importedCount,
                'errors' => $errors,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exam type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error importing combinations: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Export combinations to CSV
     * 
     * GET /api/exam-types/{code}/combinations/export
     */
    public function export($code): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();
            $combinations = $examType->combinations()->with('subjects')->get();

            // Create CSV response
            $callback = function () use ($combinations) {
                $file = fopen('php://output', 'w');

                // Write header
                fputcsv($file, ['CODE', 'CATEGORY', 'DESCRIPTION', 'SUBJECT_CODES']);

                // Write data
                foreach ($combinations as $combo) {
                    $subjectCodes = $combo->subjects()->pluck('code')->join(',');
                    fputcsv($file, [
                        $combo->code,
                        $combo->category,
                        $combo->description,
                        $subjectCodes,
                    ]);
                }

                fclose($file);
            };

            return response()->streamDownload($callback, "combinations_{$code}.csv", [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"combinations_{$code}.csv\"",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->download(
                tmpfile(),
                'error.txt',
                ['Content-Type' => 'text/plain'],
                false
            );
        }
    }
}
