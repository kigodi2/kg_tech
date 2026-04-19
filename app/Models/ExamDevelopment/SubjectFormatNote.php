<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class SubjectFormatNote extends Model
{
    protected $fillable = [
        'subject_format_id',
        'subject_format_paper_id',
        'note_type',
        'note_text',
        'applies_to_candidates',
        'applies_to_admins',
        'display_order',
    ];

    protected $casts = [
        'applies_to_candidates' => 'boolean',
        'applies_to_admins' => 'boolean',
    ];

    public function format()
    {
        return $this->belongsTo(SubjectFormat::class, 'subject_format_id');
    }

    public function paper()
    {
        return $this->belongsTo(SubjectFormatPaper::class, 'subject_format_paper_id');
    }
}
