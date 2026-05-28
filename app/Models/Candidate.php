<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'candidate_id',
        'prem_no',
        'full_name',
        'gender',
        'exam_type',
        'combination',
        'combination_id',
        'candidate_type',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'candidate_type' => 'string',
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['exam_year'];

    const GENDER_MALE = 'M';
    const GENDER_FEMALE = 'F';

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function combination()
    {
        return $this->belongsTo(Combination::class, 'combination', 'code');
    }

    /**
     * Relational combination lookup via combination_id FK (NECTA-aligned)
     */
    public function combinationTemplate()
    {
        return $this->belongsTo(Combination::class, 'combination_id');
    }

    public function examRegistrations()
    {
        return $this->hasMany(CandidateExamRegistration::class);
    }

    public function subjectSelections()
    {
        return $this->hasMany(CandidateSubjectSelection::class);
    }

    public function marks()
    {
        return $this->hasMany(SubjectMarks::class);
    }

    public function rawMarks()
    {
        return $this->hasMany(RawMark::class);
    }

    public function results()
    {
        return $this->hasMany(CandidateResult::class);
    }

    public function finalGrades()
    {
        return $this->hasMany(FinalGrade::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function isRegisteredFor($examTypeId, $year)
    {
        return $this->examRegistrations()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->exists();
    }

    public function getRegisteredSubjects($examTypeId, $year)
    {
        return $this->subjectSelections()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();
    }

    /**
     * Protect the combination attribute for PSLE candidates
     */
    public function getCombinationAttribute($value)
    {
        if ($this->exam_type === 'PSLE') {
            return null;
        }
        return $value;
    }

    /**
     * Get the exam year from the first/latest exam registration
     * Used for displaying in the candidates table
     */
    public function getExamYearAttribute()
    {
        // If examRegistrations relationship is loaded, use it
        if ($this->relationLoaded('examRegistrations') && $this->examRegistrations->count() > 0) {
            // Get the latest registration (usually there's only one per exam type per year)
            $latestRegistration = $this->examRegistrations->first();
            if ($latestRegistration && $latestRegistration->examYear) {
                return $latestRegistration->examYear->year_label;
            }
        }
        
        // Fallback: query directly
        $registration = $this->examRegistrations()->with('examYear')->first();
        if ($registration && $registration->examYear) {
            return $registration->examYear->year_label;
        }
        
        return null;
    }
}
