<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class QuestionMetadata extends Model
{
    protected $table = 'question_metadata';

    protected $fillable = [
        'question_id',
        'estimated_minutes',
        'requires_calculator',
        'requires_diagram',
        'requires_apparatus',
        'blueprint_topic_label',
    ];

    protected $casts = [
        'requires_calculator' => 'boolean',
        'requires_diagram' => 'boolean',
        'requires_apparatus' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
