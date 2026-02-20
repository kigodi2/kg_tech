<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectExamStatus extends Model
{
    use HasFactory;

    protected $table = 'subject_exam_statuses';

    protected $fillable = [
        'candidate_id',
        'subject_id',
        'exam_year',
        'exam_type_id',
        'batch_id',
        'status',
        'source',
        'decided_by',
        'decided_at',
        'note',
        'run_error_id',
        'correlation_id',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    // Status constants
    const STATUS_X = 'X';       // Did not appear (all papers missing)
    const STATUS_ABS = 'ABS';   // Absent
    const STATUS_INC = 'INC';   // Incomplete (partial papers missing, accepted by moderator)

    // Source constants
    const SOURCE_VALIDATION = 'validation';
    const SOURCE_MODERATION = 'moderation';
    const SOURCE_IMPORT = 'import';

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function batch()
    {
        return $this->belongsTo(MarkImportBatch::class, 'batch_id');
    }

    public function decidedByUser()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function runError()
    {
        return $this->belongsTo(MarkImportRunError::class, 'run_error_id');
    }

    // Scopes
    public function scopeInc($query)
    {
        return $query->where('status', self::STATUS_INC);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_X);
    }

    public function scopeByCandidate($query, $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }
}
