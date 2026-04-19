<?php

namespace App\Models\ExamDevelopment;

use App\Models\ExamYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamDevelopmentAuditLog extends Model
{
    protected $table = 'audit_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'module',
        'action',
        'exam_year_id',
        'user_id',
        'status',
        'entity_type',
        'entity_id',
        'metadata',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }
}
