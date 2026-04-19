<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class PracticalApparatusList extends Model
{
    protected $fillable = [
        'exam_project_paper_id',
        'title',
        'issued_before_days',
        'notes',
    ];

    public function paper()
    {
        return $this->belongsTo(ExamProjectPaper::class, 'exam_project_paper_id');
    }

    public function items()
    {
        return $this->hasMany(PracticalApparatusItem::class)->orderBy('display_order');
    }
}
