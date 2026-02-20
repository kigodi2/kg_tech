<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkRejection extends Model
{
    use HasFactory;

    protected $table = 'mark_rejections';

    protected $fillable = [
        'run_id', 'mark_import_batch_id', 'candidate_id',
        'row_number', 'reason_code', 'note', 'rejected_by',
        'scope', 'correlation_id',
    ];

    const REASON_DATA_QUALITY = 'DATA_QUALITY';
    const REASON_MISSING_MARKS = 'MISSING_MARKS';
    const REASON_WRONG_SUBJECT = 'WRONG_SUBJECT';
    const REASON_DUPLICATE_SUBMISSION = 'DUPLICATE_SUBMISSION';
    const REASON_TEMPLATE_ERROR = 'TEMPLATE_ERROR';
    const REASON_OTHER = 'OTHER';

    const REASONS = [
        self::REASON_DATA_QUALITY => 'Data Quality Issue',
        self::REASON_MISSING_MARKS => 'Missing Marks',
        self::REASON_WRONG_SUBJECT => 'Wrong Subject',
        self::REASON_DUPLICATE_SUBMISSION => 'Duplicate Submission',
        self::REASON_TEMPLATE_ERROR => 'Template Error',
        self::REASON_OTHER => 'Other',
    ];

    const SCOPE_CANDIDATE = 'candidate';
    const SCOPE_SUBJECT_BATCH = 'subject_batch';
    const SCOPE_RUN = 'run';
    const SCOPE_BATCH = 'batch';

    public function run()
    {
        return $this->belongsTo(MarkImportRun::class, 'run_id');
    }

    public function batch()
    {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
