<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class SubjectFormatSection extends Model
{
    protected $fillable = [
        'subject_format_paper_id',
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
        return $this->belongsTo(SubjectFormatPaper::class, 'subject_format_paper_id');
    }

    public function rules()
    {
        return $this->hasMany(SubjectFormatQuestionRule::class)->orderBy('display_order');
    }

    public function projectSections()
    {
        return $this->hasMany(ExamProjectSection::class);
    }
}
