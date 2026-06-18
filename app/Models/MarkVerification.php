<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkVerification extends Model
{
    use HasFactory;

    protected $table = 'mark_verifications';

    protected $fillable = [
        'raw_mark_id',
        'candidate_id',
        'school_id',
        'subject_id',
        'exam_year_id',
        'verified_by',
        'status',
        'return_reason',
        'returned_to_user_id',
        'returned_at',
        'verified_at',
        'correction_round',
    ];

    protected $casts = [
        'returned_at'     => 'datetime',
        'verified_at'     => 'datetime',
        'correction_round' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING    = 'pending';
    const STATUS_VERIFIED   = 'verified';
    const STATUS_RETURNED   = 'returned_for_correction';
    const STATUS_CORRECTED  = 'corrected_resubmitted';

    const STATUSES = [
        self::STATUS_PENDING   => 'Pending Review',
        self::STATUS_VERIFIED  => 'Verified',
        self::STATUS_RETURNED  => 'Returned for Correction',
        self::STATUS_CORRECTED => 'Corrected & Resubmitted',
    ];

    // Pre-set return reasons for the dropdown
    const RETURN_REASONS = [
        'Wrong mark entered',
        'Missing paper mark',
        'Candidate marked absent incorrectly',
        'Paper score exceeds maximum',
        'Marks do not match submitted sheet',
        'Duplicate entry detected',
        'Other',
    ];

    // ==================== RELATIONSHIPS ====================

    public function rawMark()
    {
        return $this->belongsTo(RawMark::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function returnedTo()
    {
        return $this->belongsTo(User::class, 'returned_to_user_id');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    // ==================== HELPERS ====================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isCorrected(): bool
    {
        return $this->status === self::STATUS_CORRECTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get or create the verification record for a raw mark.
     */
    public static function findOrCreateForRawMark(RawMark $rawMark): self
    {
        return self::firstOrCreate(
            ['raw_mark_id' => $rawMark->id],
            [
                'candidate_id'   => $rawMark->candidate_id,
                'school_id'      => $rawMark->batch?->school_id,
                'subject_id'     => $rawMark->subject_id,
                'exam_year_id'   => $rawMark->batch?->exam_year_id,
                'status'         => self::STATUS_PENDING,
                'correction_round' => 0,
            ]
        );
    }
}
