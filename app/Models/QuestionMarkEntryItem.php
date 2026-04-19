<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionMarkEntryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'question_no',
        'max_mark',
        'score',
    ];

    protected $casts = [
        'max_mark' => 'decimal:2',
        'score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entry()
    {
        return $this->belongsTo(QuestionMarkEntry::class, 'entry_id');
    }
}
