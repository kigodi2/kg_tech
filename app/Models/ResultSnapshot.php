<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type',
        'exam_year_id',
        'process_id',
        'scope_type',
        'scope_id',
        'version',
        'is_active',
        'published_by',
        'published_at',
        'snapshot_hash',
        'publish_notes',
        'locked_by',
        'locked_at',
        'lock_reason',
        'unlocked_by',
        'unlocked_at',
        'unlock_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
    ];

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function process()
    {
        return $this->belongsTo(ResultProcess::class, 'process_id');
    }
}
