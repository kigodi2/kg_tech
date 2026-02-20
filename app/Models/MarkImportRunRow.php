<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkImportRunRow extends Model
{
    public $timestamps = false;

    protected $table = 'mark_import_run_rows';

    protected $fillable = [
        'run_id', 'row_number', 'source_file', 'index_number',
        'candidate_id', 'school_id', 'subject_id',
        'paper_1', 'paper_2', 'paper_3', 'practical', 'project', 'total',
        'is_valid', 'status', 'created_at',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    public function run()
    {
        return $this->belongsTo(MarkImportRun::class, 'run_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
