<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionMarkEntry extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'exam_type_id',
        'exam_type',
        'exam_year_id',
        'candidate_id',
        'candidate_no',
        'subject_id',
        'school_id',
        'region_id',
        'entered_by',
        'status',
        'total',
        'submitted_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function items()
    {
        return $this->hasMany(QuestionMarkEntryItem::class, 'entry_id')->orderBy('question_no');
    }
}
