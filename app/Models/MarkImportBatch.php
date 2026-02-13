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
        'exam_year',
        'region_id',
        'district_id',
        'school_id',
        'subject_id',
        'exam_type_id',
        'status',
        'total_records',
        'valid_records',
        'error_records',
        'imported_by',
        'imported_at',
        'validated_by',
        'validated_at',
        'locked_by',
        'locked_at',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'validated_at' => 'datetime',
        'locked_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_VALIDATED = 'validated';
    const STATUS_LOCKED = 'locked';
    const STATUS_PROCESSED = 'processed';

    const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_VALIDATED => 'Validated',
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

    public function rawMarks(): HasMany
    {
        return $this->hasMany(RawMark::class);
    }

    public function checksum()
    {
        return $this->hasOne(MarkImportChecksum::class);
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

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_VALIDATED, self::STATUS_LOCKED]);
    }

    // ==================== METHODS ====================

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

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
        if (!$this->isValidated()) {
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
            self::STATUS_LOCKED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSED => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getErrorsCount(): int
    {
        return $this->rawMarks()->where('has_errors', true)->count();
    }
}
