<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMark extends Model
{
    use HasFactory;

    protected $table = 'raw_marks';

    protected $fillable = [
        'mark_import_batch_id',
        'candidate_id',
        'subject_id',
        'row_number',
        'candidate_index_number',
        'full_name',
        'paper_1_marks',
        'paper_2_marks',
        'paper_3_marks',
        'practical_marks',
        'project_marks',
        'subject_status',
        'status_reason',
        'has_errors',
        'has_warnings',
        'warning_messages',
        'error_messages',
        'raw_data',
        'processed_at',
        'is_locked',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'has_errors' => 'boolean',
        'has_warnings' => 'boolean',
        'error_messages' => 'array',
        'warning_messages' => 'array',
        'raw_data' => 'array',
        'processed_at' => 'datetime',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function batch()
    {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lockedByUser()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function changes()
    {
        return $this->hasMany(MarkEntryChange::class, 'raw_mark_id');
    }

    // ==================== SCOPES ====================

    public function scopeWithErrors($query)
    {
        return $query->where('has_errors', true);
    }

    public function scopeWithoutErrors($query)
    {
        return $query->where('has_errors', false);
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeWithWarnings($query)
    {
        return $query->where('has_warnings', true);
    }

    public function scopeAbsent($query)
    {
        return $query->whereNotNull('subject_status');
    }

    // ==================== METHODS ====================

    public function addError(string $message): self
    {
        $errors = $this->error_messages ?? [];
        $errors[] = $message;

        $this->update([
            'error_messages' => $errors,
            'has_errors' => true,
        ]);

        return $this;
    }

    public function clearErrors(): self
    {
        $this->update([
            'error_messages' => [],
            'has_errors' => false,
        ]);

        return $this;
    }

    public function getErrorsHtml(): string
    {
        if (!$this->has_errors || !$this->error_messages) {
            return '';
        }

        return '<ul class="list-disc list-inside">' .
            implode('', array_map(fn($msg) => "<li>$msg</li>", $this->error_messages)) .
            '</ul>';
    }

    /**
     * Lock this row after successful processing
     */
    public function lock(int $userId): self
    {
        if ($this->is_locked) {
            throw new \Exception('Row is already locked');
        }

        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $userId,
        ]);

        return $this;
    }

    /**
     * Unlock this row (restricted action)
     */
    public function unlock(int $userId): self
    {
        if (!$this->is_locked) {
            throw new \Exception('Row is not locked');
        }

        $this->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        // Log unlock action for audit
        \Log::info("RawMark row {$this->id} unlocked by user {$userId}", [
            'batch_id' => $this->mark_import_batch_id,
            'row_number' => $this->row_number,
            'index_number' => $this->candidate_index_number,
        ]);

        return $this;
    }

    /**
     * Prevent updates if row is locked
     */
    public function preventLocked(string $operation = 'update'): self
    {
        if ($this->is_locked) {
            throw new \Exception("Cannot {$operation} a locked row. Unlock the row first if changes are necessary.");
        }

        return $this;
    }
}
