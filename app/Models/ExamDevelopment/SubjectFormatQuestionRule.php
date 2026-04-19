<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class SubjectFormatQuestionRule extends Model
{
    protected $fillable = [
        'subject_format_section_id',
        'question_no_from',
        'question_no_to',
        'question_type',
        'items_per_question',
        'marks_per_item',
        'marks_per_question',
        'total_marks',
        'answer_mode',
        'is_compulsory',
        'choice_count',
        'display_order',
    ];

    protected $casts = [
        'is_compulsory' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(SubjectFormatSection::class, 'subject_format_section_id');
    }

    public function projectSlots()
    {
        return $this->hasMany(ExamProjectSlot::class, 'rule_id');
    }
}
