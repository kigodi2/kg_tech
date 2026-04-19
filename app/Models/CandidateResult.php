<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateResult extends Model
{
    use HasFactory;

    protected $table = 'candidate_results';

    protected $fillable = [
        'candidate_id',
        'exam_type_id',
        'year',
        'overall_grade',
        'result_status',
        'total_marks',
        'grade_points',
        'division',
        'is_verified',
        'verified_at',
        'is_published',
        'published_at',
        'is_locked',
        'locked_at',
        'process_id',
        'snapshot_id',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
        'verified_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
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

    public function subjectMarks()
    {
        return $this->hasMany(SubjectMarks::class, 'candidate_id', 'candidate_id');
    }


    public function verify()
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
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

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }
}
