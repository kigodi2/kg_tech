<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportJob extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'report_type',
        'status',
        'parameters',
        'file_path',
        'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];
}
