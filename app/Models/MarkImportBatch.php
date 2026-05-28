<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarkImportBatch extends Model
{
    use HasFactory;

    protected $table = 'mark_import_batches';

    protected $fillable = [
        'batch_code',
        'batch_name',
        'batch_type',
        'exam_year',
        'exam_year_id',
        'region_id',
        'district_id',
        'school_id',
        'subject_id',
        'exam_type_id',
        'assignment_id',
        'status',
        'total_records',
        'valid_records',
        'error_records',
        'created_by',
        'imported_by',
        'imported_at',
        'validated_by',
        'validated_at',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
        'processed_by',
        'processed_at',
        'notes',
        'lifecycle_state',
        'lifecycle_history',
        'rejection_reason',
        'requires_resubmission',
        'resubmitted_from_batch_id',
        'latest_review_id',
        'batch_hash',
        'promoted_count',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'validated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Status constants — full lifecycle
    const STATUS_DRAFT = 'draft';
    const STATUS_VALIDATED = 'validated';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_LOCKED = 'locked';
    const STATUS_PROCESSED = 'processed';

    const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_VALIDATED => 'Validated',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_LOCKED => 'Locked',
        self::STATUS_PROCESSED => 'Processed',
    ];

    // ==================== RELATIONSHIPS ====================

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function assignment()
    {
        return $this->belongsTo(MarkEntryAssignment::class, 'assignment_id');
    }

    public function markingCentre()
    {
        return $this->belongsTo(MarkingCentre::class, 'marking_centre_id');
    }

    public function rawMarks(): HasMany
    {
        return $this->hasMany(RawMark::class);
    }

    public function importRuns(): HasMany
    {
        return $this->hasMany(MarkImportRun::class, 'mark_import_batch_id');
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(MarkModerationAction::class, 'mark_import_batch_id');
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(MarkRejection::class, 'mark_import_batch_id');
    }

    public function importedByUser()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checksum()
    {
        return $this->hasOne(MarkImportChecksum::class);
    }

    public function reviews()
    {
        return $this->hasMany(MarkModerationReview::class, 'mark_import_batch_id');
    }

    public function latestReview()
    {
        return $this->belongsTo(MarkModerationReview::class, 'latest_review_id');
    }

    public function approvals()
    {
        return $this->hasMany(MarkBatchApproval::class, 'mark_import_batch_id');
    }

    public function lifecycleStates()
    {
        return $this->hasMany(MarkEntryLifecycleState::class, 'mark_import_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedByUser()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lockedByUser()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ==================== SCOPES ====================

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('exam_year', $year);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_VALIDATED,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED,
            self::STATUS_LOCKED,
        ]);
    }

    public function scopeForUserScope($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isRegionalOfficer() || $user->getScopeType() === 'region') {
            $regionId = $user->region_id ?? $user->getRegionId();
            if ($regionId) {
                return $query->where('region_id', $regionId);
            }
        }

        if ($user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        if ($user->school_id) {
            return $query->where('school_id', $user->school_id);
        }

        return $query->whereRaw('1 = 0');
    }

    // ==================== STATUS HELPERS ====================

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function hasErrors(): bool
    {
        return ($this->error_records ?? 0) > 0;
    }

    public function canBeSubmitted(): bool
    {
        return ($this->isValidated() || $this->isDraft()) && !$this->hasErrors();
    }

    public function canBeApproved(): bool
    {
        return in_array($this->lifecycle_state ?? $this->status, [
            'submitted', 'validated', 'awaiting_moderation',
        ]);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->lifecycle_state ?? $this->status, [
            'submitted', 'validated', 'awaiting_moderation',
        ]);
    }

    public function canBeLocked(): bool
    {
        return $this->isApproved();
    }

    public function canBeUnlocked(): bool
    {
        return $this->isLocked();
    }

    // ==================== LEGACY TRANSITION METHODS ====================

    public function validate(string $validatedBy): bool
    {
        if (!$this->isDraft()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_VALIDATED,
            'validated_by' => $validatedBy,
            'validated_at' => now(),
        ]);

        return true;
    }

    public function lock(string $lockedBy): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_LOCKED,
            'locked_by' => $lockedBy,
            'locked_at' => now(),
        ]);

        return true;
    }

    public function process(string $processedBy): bool
    {
        if (!$this->isLocked()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);

        return true;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_VALIDATED => 'bg-blue-100 text-blue-800',
            self::STATUS_SUBMITTED => 'bg-indigo-100 text-indigo-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            self::STATUS_LOCKED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSED => 'bg-emerald-100 text-emerald-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getErrorsCount(): int
    {
        return $this->rawMarks()->where('has_errors', true)->count();
    }
}
