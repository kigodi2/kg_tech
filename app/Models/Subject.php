<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'written_papers',
        'has_practical',
        'has_project',
        'exam_type_id',
        'max_marks',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_practical' => 'boolean',
        'has_project' => 'boolean',
        'written_papers' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function selections()
    {
        return $this->hasMany(CandidateSubjectSelection::class);
    }

    public function marks()
    {
        return $this->hasMany(SubjectMarks::class);
    }

    public function paperStructures()
    {
        return $this->hasMany(SubjectPaperStructure::class);
    }

    public function combinations()
    {
        return $this->belongsToMany(
            Combination::class,
            'combination_subject',
            'subject_id',
            'combination_id'
        )->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
