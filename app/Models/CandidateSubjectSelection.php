<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateSubjectSelection extends Model
{
    use HasFactory;

    protected $table = 'candidate_subject_selections';

    protected $fillable = [
        'candidate_id',
        'exam_type_id',
        'exam_year_id',
        'subject_id',
        'year',
        'is_active',
        'is_principal',
        'source',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_principal' => 'boolean',
        'source' => 'string',
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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function marks()
    {
        // Get the mark for this candidate-subject combination
        // Use whereColumn to join on multiple foreign keys
        return SubjectMarks::where('candidate_id', $this->candidate_id)
            ->where('subject_id', $this->subject_id)
            ->where('exam_type_id', $this->exam_type_id)
            ->limit(1);
    }
    
    public function mark()
    {
        // Eager-loadable relationship (returns single mark)
        return $this->hasOne(SubjectMarks::class, 'subject_id', 'subject_id')
            ->where('candidate_id', $this->candidate_id)
            ->where('exam_type_id', $this->exam_type_id);
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
}
