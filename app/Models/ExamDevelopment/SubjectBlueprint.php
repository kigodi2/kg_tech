<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class SubjectBlueprint extends Model
{
    protected $fillable = [
        'subject_format_paper_id',
        'blueprint_name',
        'total_items',
        'total_weight',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function paper()
    {
        return $this->belongsTo(SubjectFormatPaper::class, 'subject_format_paper_id');
    }

    public function topics()
    {
        return $this->hasMany(SubjectBlueprintTopic::class)->orderBy('display_order');
    }
}
