<?php

namespace App\Models\ExamDevelopment;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamProject extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_DEVELOPMENT = 'in_development';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'subject_format_id',
        'exam_year',
        'project_code',
        'project_name',
        'status',
        'description',
        'created_by',
        'approved_by',
        'locked_at',
        'published_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function format()
    {
        return $this->belongsTo(SubjectFormat::class, 'subject_format_id');
    }

    public function papers()
    {
        return $this->hasMany(ExamProjectPaper::class)->orderBy('display_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
