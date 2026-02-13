<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImportFile extends Model
{
    protected $fillable = [
        'bulk_import_id',
        'subject_id',
        'subject_code',
        'filename',
        'status',
        'rows_total',
        'rows_success',
        'rows_failed',
        'error_log',
        'file_hash',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bulkImport(): BelongsTo
    {
        return $this->belongsTo(BulkImport::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get success rate as percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->rows_total === 0) {
            return 0;
        }

        return ($this->rows_success / $this->rows_total) * 100;
    }

    /**
     * Log an error
     */
    public function logError(int $rowNumber, string $indexNumber, string $reason): void
    {
        $errorEntry = json_encode([
            'row' => $rowNumber,
            'index_number' => $indexNumber,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->error_log = ($this->error_log ?? '') . $errorEntry . "\n";
        $this->save();
    }

    /**
     * Get parsed error log as array
     */
    public function getParsedErrors(): array
    {
        if (!$this->error_log) {
            return [];
        }

        $errors = [];
        foreach (explode("\n", trim($this->error_log)) as $line) {
            if (trim($line)) {
                $errors[] = json_decode($line, true);
            }
        }

        return $errors;
    }
}
