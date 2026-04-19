<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class SubjectBlueprintTopic extends Model
{
    protected $fillable = [
        'subject_blueprint_id',
        'topic_name',
        'items_count',
        'percentage_weight',
        'remembering_weight',
        'understanding_weight',
        'applying_weight',
        'analysing_weight',
        'evaluating_weight',
        'creating_weight',
        'display_order',
    ];

    public function blueprint()
    {
        return $this->belongsTo(SubjectBlueprint::class, 'subject_blueprint_id');
    }
}
