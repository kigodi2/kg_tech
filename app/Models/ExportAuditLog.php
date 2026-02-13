<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ExportAuditLog Model
 * 
 * Tracks all data exports (PDF, CSV) for compliance and audit purposes
 */
class ExportAuditLog extends Model
{
    use HasFactory;

    protected $table = 'export_audit_logs';

    protected $fillable = [
        'user_id',
        'module',
        'format',
        'year',
        'region_id',
        'district_id',
        'school_id',
        'exported_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
