<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateExamRegistration extends Model
{
    use HasFactory;

    protected $table = 'candidate_exam_registrations';

    protected $fillable = [
        'candidate_id',
        'exam_type_id',
        'exam_year_id',
        'year',
        'registration_number',
        'status',
        'grade',
        'gpa',
        'division',
        'total_marks',
        'total_points',
        'result_status',
        'published_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'verification_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function subjectSelections()
    {
        return $this->hasMany(CandidateSubjectSelection::class, 'candidate_id', 'candidate_id')
            ->where('exam_type_id', $this->exam_type_id)
            ->where('year', $this->year);
    }

    public function marks()
    {
        return $this->hasManyThrough(
            SubjectMarks::class,
            CandidateSubjectSelection::class,
            'candidate_id',
            'subject_id',
            'candidate_id',
            'id'
        )->where('subject_marks.year', $this->year);
    }

    public function result()
    {
        return $this->hasOne(CandidateResult::class, 'candidate_id', 'candidate_id')
            ->where('exam_type_id', $this->exam_type_id)
            ->where('year', $this->year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
