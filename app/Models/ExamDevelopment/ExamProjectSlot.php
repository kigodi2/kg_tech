<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class ExamProjectSlot extends Model
{
    protected $fillable = [
        'exam_project_section_id',
        'rule_id',
        'slot_label',
        'question_no',
        'question_type',
        'items_per_question',
        'marks_per_item',
        'marks_per_question',
        'is_compulsory',
        'choice_group',
        'display_order',
        'assigned_question_id',
    ];

    protected $casts = [
        'is_compulsory' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(ExamProjectSection::class, 'exam_project_section_id');
    }

    public function rule()
    {
        return $this->belongsTo(SubjectFormatQuestionRule::class, 'rule_id');
    }

    public function assignedQuestion()
    {
        return $this->belongsTo(Question::class, 'assigned_question_id');
    }

    public function paperQuestions()
    {
        return $this->hasMany(PaperQuestion::class, 'exam_project_slot_id');
    }
}
