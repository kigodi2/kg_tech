<?php

namespace App\Services;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Combination;
use Illuminate\Support\Facades\Log;
use Exception;

class ExamTypeService
{
    /**
     * Create a new subject for an exam type
     */
    public function createSubject(ExamType $examType, array $data): Subject
    {
        Log::info('ExamTypeService: Creating subject', [
            'exam_type_id' => $examType->id,
            'data' => $data,
        ]);

        try {
            // Convert camelCase to snake_case
            $attributes = [
                'code' => $data['code'] ?? null,
                'name' => $data['name'] ?? null,
                'category' => $data['category'] ?? null,
                'subject_group_label' => $data['subjectGroupLabel'] ?? null,
                'written_papers' => $data['writtenPapers'] ?? 1,
                'paper_pattern_label' => $data['paperPatternLabel'] ?? null,
                'has_practical' => $data['hasPractical'] ?? false,
                'has_project' => $data['hasProject'] ?? false,
                'description' => $data['description'] ?? null,
                'max_marks' => $data['max_marks'] ?? 100,
                'is_active' => $data['is_active'] ?? true,
                'exam_type_id' => $examType->id,
            ];

            Log::info('ExamTypeService: Converted attributes', $attributes);

            $subject = Subject::create($attributes);

            Log::info('ExamTypeService: Subject created successfully', [
                'subject_id' => $subject->id,
                'code' => $subject->code,
            ]);

            return $subject;
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to create subject', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing subject
     */
    public function updateSubject(Subject $subject, array $data): Subject
    {
        Log::info('ExamTypeService: Updating subject', [
            'subject_id' => $subject->id,
            'data' => $data,
        ]);

        try {
            // Convert camelCase to snake_case
            $attributes = [
                'code' => $data['code'] ?? $subject->code,
                'name' => $data['name'] ?? $subject->name,
                'category' => $data['category'] ?? $subject->category,
                'subject_group_label' => array_key_exists('subjectGroupLabel', $data) ? $data['subjectGroupLabel'] : $subject->subject_group_label,
                'written_papers' => $data['writtenPapers'] ?? $subject->written_papers,
                'paper_pattern_label' => array_key_exists('paperPatternLabel', $data) ? $data['paperPatternLabel'] : $subject->paper_pattern_label,
                'has_practical' => $data['hasPractical'] ?? $subject->has_practical,
                'has_project' => $data['hasProject'] ?? $subject->has_project,
                'description' => $data['description'] ?? $subject->description,
                'max_marks' => $data['max_marks'] ?? $subject->max_marks,
                'is_active' => $data['is_active'] ?? $subject->is_active,
            ];

            $subject->update($attributes);

            Log::info('ExamTypeService: Subject updated successfully', [
                'subject_id' => $subject->id,
            ]);

            return $subject;
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to update subject', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a subject
     */
    public function deleteSubject(Subject $subject): bool
    {
        Log::info('ExamTypeService: Deleting subject', [
            'subject_id' => $subject->id,
            'code' => $subject->code,
        ]);

        try {
            $subject->delete();

            Log::info('ExamTypeService: Subject deleted successfully', [
                'subject_id' => $subject->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to delete subject', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all subjects for an exam type
     */
    public function getSubjects(ExamType $examType): array
    {
        Log::info('ExamTypeService: Getting subjects', [
            'exam_type_id' => $examType->id,
        ]);

        try {
            $subjects = $examType->subjects()->get();

            Log::info('ExamTypeService: Subjects retrieved', [
                'count' => $subjects->count(),
            ]);

            return $subjects->toArray();
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to get subjects', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new combination for an exam type
     */
    public function createCombination(ExamType $examType, array $data): Combination
    {
        Log::info('ExamTypeService: Creating combination', [
            'exam_type_id' => $examType->id,
            'data' => $data,
        ]);

        try {
            $attributes = [
                'code' => $data['code'] ?? null,
                'subjects' => $data['subjects'] ?? null,
                'exam_type_id' => $examType->id,
            ];

            $combination = Combination::create($attributes);

            Log::info('ExamTypeService: Combination created successfully', [
                'combination_id' => $combination->id,
            ]);

            return $combination;
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to create combination', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing combination
     */
    public function updateCombination(Combination $combination, array $data): Combination
    {
        Log::info('ExamTypeService: Updating combination', [
            'combination_id' => $combination->id,
            'data' => $data,
        ]);

        try {
            $attributes = [
                'code' => $data['code'] ?? $combination->code,
                'subjects' => $data['subjects'] ?? $combination->subjects,
            ];

            $combination->update($attributes);

            Log::info('ExamTypeService: Combination updated successfully', [
                'combination_id' => $combination->id,
            ]);

            return $combination;
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to update combination', [
                'combination_id' => $combination->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a combination
     */
    public function deleteCombination(Combination $combination): bool
    {
        Log::info('ExamTypeService: Deleting combination', [
            'combination_id' => $combination->id,
        ]);

        try {
            $combination->delete();

            Log::info('ExamTypeService: Combination deleted successfully', [
                'combination_id' => $combination->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to delete combination', [
                'combination_id' => $combination->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all combinations for an exam type
     */
    public function getCombinations(ExamType $examType): array
    {
        Log::info('ExamTypeService: Getting combinations', [
            'exam_type_id' => $examType->id,
        ]);

        try {
            $combinations = $examType->combinations()->get();

            Log::info('ExamTypeService: Combinations retrieved', [
                'count' => $combinations->count(),
            ]);

            return $combinations->toArray();
        } catch (Exception $e) {
            Log::error('ExamTypeService: Failed to get combinations', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
