<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkImportRunError extends Model
{
    use HasFactory;

    protected $table = 'mark_import_run_errors';

    protected $fillable = [
        'run_id', 'row_number', 'source_file', 'index_number',
        'subject_id', 'paper', 'column_name', 'error_code',
        'message', 'raw_value', 'severity',
        'is_actionable', 'is_resolved', 'resolved_by',
        'resolved_at', 'resolution_action', 'resolution_note',
        'resolution_correlation_id',
    ];

    protected $casts = [
        'is_actionable' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    const CODE_OUT_OF_RANGE = 'OUT_OF_RANGE';
    const CODE_INVALID_FORMAT = 'INVALID_FORMAT';
    const CODE_NOT_REGISTERED = 'NOT_REGISTERED';
    const CODE_DUPLICATE = 'DUPLICATE';
    const CODE_MISSING_REQUIRED = 'MISSING_REQUIRED';
    const CODE_REFERENTIAL_MISMATCH = 'REFERENTIAL_MISMATCH';
    const CODE_NON_NUMERIC = 'NON_NUMERIC';
    const CODE_INVALID_INDEX = 'INVALID_INDEX';
    const CODE_LOCKED_CONFLICT = 'LOCKED_CONFLICT';
    const CODE_DUPLICATE_UPLOAD = 'DUPLICATE_UPLOAD';
    const CODE_SUSPICIOUS_VALUES = 'SUSPICIOUS_VALUES';
    const CODE_INVALID_FILE_TYPE = 'INVALID_FILE_TYPE';
    const CODE_FILE_TOO_LARGE = 'FILE_TOO_LARGE';
    const CODE_HEADER_MISMATCH = 'HEADER_MISMATCH';
    const CODE_MISSING_REQUIRED_PAPER_MARK = 'MISSING_REQUIRED_PAPER_MARK';

    const SEVERITY_ERROR = 'error';
    const SEVERITY_WARNING = 'warning';

    const RESOLUTION_ACCEPT_INC = 'ACCEPT_INC';
    const RESOLUTION_REJECT = 'REJECT';

    public function run()
    {
        return $this->belongsTo(MarkImportRun::class, 'run_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeActionable($query)
    {
        return $query->where('is_actionable', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeBlockingUnresolved($query)
    {
        return $query->where('severity', 'error')
            ->where(function ($q) {
                $q->where('is_actionable', false)
                  ->orWhere(function ($q2) {
                      $q2->where('is_actionable', true)
                         ->where('is_resolved', false);
                  });
            });
    }
}
