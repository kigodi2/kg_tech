<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class SubjectFormatPaper extends Model
{
    protected $fillable = [
        'subject_format_id',
        'paper_code',
        'paper_no',
        'paper_name',
        'paper_type',
        'duration_minutes',
        'total_marks',
        'questions_total',
        'questions_to_answer',
        'has_sections',
        'candidate_notes',
        'admin_notes',
        'display_order',
    ];

    protected $casts = [
        'has_sections' => 'boolean',
    ];

    public function format()
    {
        return $this->belongsTo(SubjectFormat::class, 'subject_format_id');
    }

    public function sections()
    {
        return $this->hasMany(SubjectFormatSection::class)->orderBy('display_order');
    }

    public function notes()
    {
        return $this->hasMany(SubjectFormatNote::class)->orderBy('display_order');
    }

    public function blueprints()
    {
        return $this->hasMany(SubjectBlueprint::class)->orderBy('id');
    }

    public function projectPapers()
    {
        return $this->hasMany(ExamProjectPaper::class);
    }

    public function practicalVariants()
    {
        return $this->hasMany(PracticalPaperVariant::class);
    }
}
