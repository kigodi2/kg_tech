<?php

namespace App\Services\MarkEntry;

use App\Models\RawMark;
use App\Models\MarkEntryValidation;
use App\Models\Candidate;
use App\Models\Subject;
use App\Models\ExamYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PsleMarkValidationService
{
    /**
     * Validate a single RawMark record and create/update validation entries.
     */
    public function validateRawMark(RawMark $rawMark): array
    {
        $errors = [];
        $subject = $rawMark->subject;
        $candidate = $rawMark->candidate;
        $batch = $rawMark->batch;
        $yearId = $batch?->exam_year_id;
        
        // 1. Candidate must exist
        if (!$candidate) {
            $errors[] = [
                'type' => 'Missing Relationship',
                'severity' => 'critical',
                'message' => "Candidate record not found for index number {$rawMark->candidate_index_number}."
            ];
        } else {
            // 2. Candidate must belong to selected school
            if ($rawMark->batch && $candidate->school_id !== $rawMark->batch->school_id) {
                $errors[] = [
                    'type' => 'Candidate Outside School',
                    'severity' => 'high',
                    'message' => "Candidate {$rawMark->candidate_index_number} belongs to a different school."
                ];
            }

            // 5. Candidate must be registered for the selected exam year
            if ($yearId) {
                $isRegistered = $candidate->examRegistrations()
                    ->where('exam_year_id', $yearId)
                    ->exists();
                if (!$isRegistered) {
                    $errors[] = [
                        'type' => 'Candidate Not Registered',
                        'severity' => 'critical',
                        'message' => "Candidate is not registered for PSLE in the selected exam year."
                    ];
                }
            }
        }

        // 8-11. Mark Validations
        $mark = $rawMark->paper_1_marks;
        $status = $rawMark->subject_status;

        if ($status === 'ABS') {
            // 12. ABS cannot have a numeric mark
            if ($mark !== null) {
                $errors[] = [
                    'type' => 'ABS Conflict',
                    'severity' => 'high',
                    'message' => "Candidate marked as ABS but has a numeric mark ({$mark})."
                ];
            }
        } else {
            // 8. Mark is required unless status is ABS
            if ($mark === null) {
                $errors[] = [
                    'type' => 'Missing Mark',
                    'severity' => 'medium',
                    'message' => "Mark is missing for this candidate."
                ];
            } elseif (!is_numeric($mark)) {
                // 9. Mark must be numeric
                $errors[] = [
                    'type' => 'Wrong Format',
                    'severity' => 'high',
                    'message' => "Mark must be a numeric value."
                ];
            } else {
                // 10. Mark must not be below 0
                if ($mark < 0) {
                    $errors[] = [
                        'type' => 'Negative Mark',
                        'severity' => 'critical',
                        'message' => "Mark cannot be negative."
                    ];
                }
                // 11. Mark must not exceed subject maximum (PSLE is usually 50)
                if ($mark > 50) {
                    $errors[] = [
                        'type' => 'Mark Above Maximum',
                        'severity' => 'critical',
                        'message' => "Mark exceeds the maximum allowed (50)."
                    ];
                }
            }
        }

        // 13. Duplicate Check
        if ($candidate && $subject && $yearId) {
            $duplicate = RawMark::where('candidate_id', $candidate->id)
                ->where('subject_id', $subject->id)
                ->where('id', '!=', $rawMark->id)
                ->whereHas('batch', fn($q) => $q->where('exam_year_id', $yearId))
                ->exists();
            if ($duplicate) {
                $errors[] = [
                    'type' => 'Duplicate Entry',
                    'severity' => 'high',
                    'message' => "Duplicate mark entry found for this candidate and subject in the same year."
                ];
            }
        }

        // Sync with mark_entry_validations table
        $this->syncValidationErrors($rawMark, $errors);

        return $errors;
    }

    /**
     * Run validation for a given scope.
     */
    public function runValidation(array $filters, User $user): int
    {
        $query = RawMark::query()->with(['batch', 'candidate', 'subject']);

        if (!empty($filters['exam_year_id'])) {
            $year = ExamYear::find($filters['exam_year_id']);
            if ($year) {
                $query->whereHas('batch', fn($q) => $q->where('exam_year', $year->year_label));
            }
        }
        if (!empty($filters['region_id'])) {
            $query->whereHas('batch', fn($q) => $q->where('region_id', $filters['region_id']));
        }
        if (!empty($filters['district_id'])) {
            $query->whereHas('batch', fn($q) => $q->where('district_id', $filters['district_id']));
        }
        if (!empty($filters['school_id'])) {
            $query->whereHas('batch', fn($q) => $q->where('school_id', $filters['school_id']));
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['batch_id'])) {
            $query->where('mark_import_batch_id', $filters['batch_id']);
        }

        // Apply role scoping
        if ($user->hasRole('mark_officer') || $user->portal_role === 'mark_officer') {
            $query->whereHas('batch', fn($q) => $q->where('region_id', $user->region_id));
        } elseif ($user->hasRole('reo') || $user->portal_role === 'mock_rao') {
            $query->whereHas('batch', fn($q) => $q->where('region_id', $user->region_id));
        }

        $count = 0;
        $query->chunk(100, function ($marks) use (&$count) {
            foreach ($marks as $mark) {
                $this->validateRawMark($mark);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Sync validation errors to the database.
     */
    protected function syncValidationErrors(RawMark $rawMark, array $errors): void
    {
        // 1. Mark existing open errors for this RawMark as resolved if they are not in the new error list
        $existingErrors = MarkEntryValidation::where('raw_mark_id', $rawMark->id)
            ->where('status', 'open')
            ->get();

        $newErrorTypes = collect($errors)->pluck('type')->toArray();

        foreach ($existingErrors as $oldError) {
            if (!in_array($oldError->error_type, $newErrorTypes)) {
                $oldError->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolution_comment' => 'Automatically resolved by re-validation.'
                ]);
            }
        }

        // Get ExamYear ID from batch label
        $examYearId = null;
        if ($rawMark->batch?->exam_year) {
            $examYearId = ExamYear::where('year_label', $rawMark->batch->exam_year)->first()?->id;
        }

        // 2. Create or update errors
        foreach ($errors as $error) {
            MarkEntryValidation::updateOrCreate(
                [
                    'raw_mark_id' => $rawMark->id,
                    'error_type' => $error['type'],
                    'status' => 'open'
                ],
                [
                    'exam_year_id' => $examYearId,
                    'region_id' => $rawMark->batch?->region_id,
                    'district_id' => $rawMark->batch?->district_id,
                    'school_id' => $rawMark->batch?->school_id,
                    'subject_id' => $rawMark->subject_id,
                    'candidate_id' => $rawMark->candidate_id,
                    'severity' => $error['severity'],
                    'message' => $error['message'],
                ]
            );
        }

        // 3. Update RawMark summary flags
        $rawMark->update([
            'has_errors' => collect($errors)->where('severity', '!=', 'warning')->isNotEmpty(),
            'has_warnings' => collect($errors)->where('severity', 'warning')->isNotEmpty(),
            'error_messages' => collect($errors)->where('severity', '!=', 'warning')->pluck('message')->toArray(),
            'warning_messages' => collect($errors)->where('severity', 'warning')->pluck('message')->toArray(),
        ]);
    }

    public function getValidationErrors(array $filters, User $user)
    {
        $query = MarkEntryValidation::with(['rawMark', 'candidate', 'school', 'subject', 'district', 'region']);

        if (!empty($filters['exam_year_id'])) $query->where('exam_year_id', $filters['exam_year_id']);
        if (!empty($filters['region_id'])) $query->where('region_id', $filters['region_id']);
        if (!empty($filters['district_id'])) $query->where('district_id', $filters['district_id']);
        if (!empty($filters['school_id'])) $query->where('school_id', $filters['school_id']);
        if (!empty($filters['subject_id'])) $query->where('subject_id', $filters['subject_id']);
        if (!empty($filters['error_type'])) $query->where('error_type', $filters['error_type']);
        if (!empty($filters['severity'])) $query->where('severity', $filters['severity']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        else $query->where('status', 'open');

        // Role Scoping
        if (!$user->isAdmin()) {
            $query->where('region_id', $user->region_id);
        }

        return $query->latest()->paginate(20)->withQueryString();
    }
}
