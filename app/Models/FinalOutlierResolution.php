<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalOutlierResolution extends Model
{
    protected $fillable = [
        'exam_type_id',
        'year',
        'resolution_key',
        'tab',
        'candidate_id',
        'subject_id',
        'school_id',
        'resolved_by',
        'resolved_at',
        'note',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}

