<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class PracticalConfidentialInstruction extends Model
{
    protected $fillable = [
        'exam_project_paper_id',
        'release_hours_before',
        'instruction_text',
        'is_confidential',
    ];

    protected $casts = [
        'is_confidential' => 'boolean',
    ];

    public function paper()
    {
        return $this->belongsTo(ExamProjectPaper::class, 'exam_project_paper_id');
    }
}
