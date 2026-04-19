<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class PracticalPaperVariant extends Model
{
    protected $fillable = [
        'subject_format_paper_id',
        'variant_code',
        'name',
        'candidate_min',
        'candidate_max',
        'notes',
    ];

    public function formatPaper()
    {
        return $this->belongsTo(SubjectFormatPaper::class, 'subject_format_paper_id');
    }
}
