<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class QuestionAttachment extends Model
{
    protected $fillable = [
        'question_id',
        'file_path',
        'file_type',
        'caption',
        'display_order',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
