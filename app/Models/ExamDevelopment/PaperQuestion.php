<?php

namespace App\Models\ExamDevelopment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaperQuestion extends Model
{
    protected $fillable = [
        'exam_project_slot_id',
        'question_id',
        'custom_marks',
        'custom_instructions',
        'inserted_by',
    ];

    public function slot()
    {
        return $this->belongsTo(ExamProjectSlot::class, 'exam_project_slot_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function insertedBy()
    {
        return $this->belongsTo(User::class, 'inserted_by');
    }
}
