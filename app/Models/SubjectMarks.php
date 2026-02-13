<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectMarks extends Model
{
    use HasFactory;

    protected $table = 'subject_marks';

    protected $fillable = [
        'candidate_id',
        'subject_id',
        'exam_type_id',
        'year',
        'paper_1',
        'paper_2',
        'paper_3',
        'marks_obtained',
        'max_marks',
        'percentage',
        'grade',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Accessor: Get the average mark for this subject
     * Divides marks_obtained by number of papers for multi-paper subjects
     */
    public function getAverageAttribute(): float
    {
        if (!$this->marks_obtained) {
            return 0;
        }

        // Load subject if not already loaded
        if (!$this->relationLoaded('subject')) {
            $this->load('subject');
        }

        $subject = $this->subject;
        if (!$subject) {
            return $this->marks_obtained;
        }

        // Calculate total papers for this subject
        $totalPapers = ($subject->written_papers ?? 1) + 
                      ($subject->has_practical ? 1 : 0) + 
                      ($subject->has_project ? 1 : 0);

        // Return average if multiple papers, otherwise return as-is
        return $totalPapers > 1 
            ? round($this->marks_obtained / $totalPapers, 2)
            : $this->marks_obtained;
    }

    /**
     * Accessor: Get the grade for the average mark
     */
    public function getGradeFromAverageAttribute(): string
    {
        $gradingService = app(\App\Services\Results\NectaGradingService::class);
        return $gradingService->calculateGrade($this->average);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }
}
