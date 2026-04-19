<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinalGrade extends Model
{
    use HasFactory;

    protected $table = 'final_grades';

    protected $fillable = [
        'candidate_id',
        'exam_type_id',
        'year',
        'grading_profile_id',
        'overall_grade',
        'total_marks',
        'grade_points',
        'gpa',
        'division',
        'grading_breakdown',
        'is_published',
        'published_at',
        'is_locked',
        'locked_at',
        'process_id',
        'snapshot_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'grading_breakdown' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function gradingProfile()
    {
        return $this->belongsTo(GradingProfile::class);
    }

    public function publish()
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function lock()
    {
        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
        ]);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }
}
