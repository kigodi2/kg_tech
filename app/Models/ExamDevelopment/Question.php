<?php

namespace App\Models\ExamDevelopment;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'paper_type',
        'topic_name',
        'subtopic_name',
        'competency_code',
        'difficulty_level',
        'question_type',
        'title',
        'question_text',
        'marks',
        'status',
        'author_id',
        'reviewer_id',
        'approved_by',
        'current_version_no',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('display_order');
    }

    public function attachments()
    {
        return $this->hasMany(QuestionAttachment::class)->orderBy('display_order');
    }

    public function versions()
    {
        return $this->hasMany(QuestionVersion::class)->orderByDesc('version_no');
    }

    public function metadataRow()
    {
        return $this->hasOne(QuestionMetadata::class);
    }

    public function markingSchemes()
    {
        return $this->hasMany(MarkingScheme::class);
    }

    public function reviewComments()
    {
        return $this->hasMany(ReviewComment::class);
    }
}
