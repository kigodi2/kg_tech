<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectPaperWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'paper_code',
        'weight',
        'max_mark',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'float',
        'max_mark' => 'float',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
