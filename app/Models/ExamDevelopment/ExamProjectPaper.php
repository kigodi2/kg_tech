<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class ExamProjectPaper extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ASSEMBLING = 'assembling';
    public const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_EXPORTED = 'exported';

    protected $fillable = [
        'exam_project_id',
        'subject_format_paper_id',
        'paper_code',
        'paper_name',
        'paper_type',
        'duration_minutes',
        'total_marks',
        'status',
        'display_order',
    ];

    public function project()
    {
        return $this->belongsTo(ExamProject::class, 'exam_project_id');
    }

    public function formatPaper()
    {
        return $this->belongsTo(SubjectFormatPaper::class, 'subject_format_paper_id');
    }

    public function sections()
    {
        return $this->hasMany(ExamProjectSection::class)->orderBy('display_order');
    }

    public function reviewComments()
    {
        return $this->hasMany(ReviewComment::class);
    }

    public function apparatusLists()
    {
        return $this->hasMany(PracticalApparatusList::class);
    }

    public function confidentialInstructions()
    {
        return $this->hasMany(PracticalConfidentialInstruction::class);
    }
}
