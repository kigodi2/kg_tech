<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type',
        'exam_year_id',
        'snapshot_id',
        'process_id',
        'scope_type',
        'scope_id',
        'candidates_count',
        'schools_count',
        'mean_aggt',
        'mean_gpa',
        'division_counts',
        'irregularity_counts',
        'subject_grade_distributions',
        'generated_at',
    ];

    protected $casts = [
        'division_counts' => 'array',
        'irregularity_counts' => 'array',
        'subject_grade_distributions' => 'array',
        'generated_at' => 'datetime',
    ];
}

