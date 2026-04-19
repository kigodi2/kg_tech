<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class ExamProjectSection extends Model
{
    protected $fillable = [
        'exam_project_paper_id',
        'subject_format_section_id',
        'section_code',
        'section_name',
        'instructions',
        'total_marks',
        'number_of_questions',
        'questions_to_answer',
        'is_all_compulsory',
        'display_order',
    ];

    protected $casts = [
        'is_all_compulsory' => 'boolean',
    ];

    public function paper()
    {
        return $this->belongsTo(ExamProjectPaper::class, 'exam_project_paper_id');
    }

    public function formatSection()
    {
        return $this->belongsTo(SubjectFormatSection::class, 'subject_format_section_id');
    }

    public function slots()
    {
        return $this->hasMany(ExamProjectSlot::class)->orderBy('display_order');
    }
}
