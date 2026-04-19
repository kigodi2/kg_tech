<?php

namespace App\Models\ExamDevelopment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReviewComment extends Model
{
    protected $fillable = [
        'question_id',
        'exam_project_paper_id',
        'comment_type',
        'comment_text',
        'status',
        'created_by',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function paper()
    {
        return $this->belongsTo(ExamProjectPaper::class, 'exam_project_paper_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
