<?php

namespace App\Models\ExamDevelopment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class QuestionVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'version_no',
        'question_text',
        'change_summary',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
