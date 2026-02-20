<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * GradingProfile
 *
 * Stores grading configurations: grade boundaries, GPA mapping, and competence levels.
 * Versioned per academic year. Can be locked after results are published.
 */
class GradingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type_id',
        'exam_year_id',
        'name',
        'version',
        'grade_boundaries',
        'gpa_mapping',
        'competence_levels',
        'is_active',
        'is_locked',
        'locked_at',
        'locked_by_id',
    ];

    protected $casts = [
        'grade_boundaries' => 'array',
        'gpa_mapping' => 'array',
        'competence_levels' => 'array',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_id');
    }

    public function gradingRules()
    {
        return $this->hasMany(GradingRule::class);
    }

    /**
     * Get all grading rules for this profile
     */
    public function rules()
    {
        return $this->hasMany(GradingRule::class);
    }

    /**
     * Scope: active profiles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate grade for given marks
     */
    public function calculateGrade($marks)
    {
        foreach ($this->grade_boundaries as $boundary) {
            if ($marks >= $boundary['min'] && $marks <= $boundary['max']) {
                return $boundary['grade'];
            }
        }
        return 'F'; // Fail
    }

    /**
     * Calculate GPA for given grade
     */
    public function calculateGPA($grade)
    {
        return $this->gpa_mapping[$grade] ?? 0.0;
    }

    /**
     * Get competence level for grade
     */
    public function getCompetenceLevel($grade)
    {
        return $this->competence_levels[$grade] ?? 'Unknown';
    }
}
