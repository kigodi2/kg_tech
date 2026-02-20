<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkModerationAction extends Model
{
    use HasFactory;

    protected $table = 'mark_moderation_actions';

    protected $fillable = [
        'action', 'scope', 'actor_id', 'mark_import_batch_id',
        'run_id', 'exam_year_id', 'school_id', 'subject_id',
        'district_id', 'candidate_id', 'affected_rows',
        'reason', 'correlation_id',
    ];

    const ACTION_APPROVE = 'APPROVE';
    const ACTION_REJECT = 'REJECT';
    const ACTION_OVERRIDE = 'OVERRIDE';

    const SCOPE_SINGLE_SUBJECT = 'single_subject';
    const SCOPE_SCHOOL = 'school';
    const SCOPE_DISTRICT = 'district';
    const SCOPE_CANDIDATE = 'candidate';
    const SCOPE_RUN = 'run';

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function batch()
    {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function run()
    {
        return $this->belongsTo(MarkImportRun::class, 'run_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
