<?php

namespace App\Models\ExamDevelopment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MarkingScheme extends Model
{
    protected $fillable = [
        'question_id',
        'scheme_type',
        'total_marks',
        'answer_text',
        'rubric_json',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'rubric_json' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function items()
    {
        return $this->hasMany(MarkingSchemeItem::class)->orderBy('display_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
