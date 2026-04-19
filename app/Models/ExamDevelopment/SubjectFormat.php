<?php

namespace App\Models\ExamDevelopment;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SubjectFormat extends Model
{
    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'format_code',
        'format_name',
        'version_year',
        'candidate_scope',
        'total_papers',
        'general_objectives_text',
        'general_competencies_text',
        'general_instructions',
        'administrative_notes',
        'source_reference',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function papers()
    {
        return $this->hasMany(SubjectFormatPaper::class)->orderBy('display_order');
    }

    public function notes()
    {
        return $this->hasMany(SubjectFormatNote::class)->orderBy('display_order');
    }

    public function projects()
    {
        return $this->hasMany(ExamProject::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
