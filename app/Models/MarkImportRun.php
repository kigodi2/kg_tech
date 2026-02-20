<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkImportRun extends Model
{
    use HasFactory;

    protected $table = 'mark_import_runs';

    protected $fillable = [
        'user_id', 'exam_year_id', 'school_id', 'subject_id',
        'mark_import_batch_id', 'scope', 'scope_type', 'file_name',
        'original_file_name', 'file_size', 'stored_path', 'checksum',
        'status', 'total_rows', 'success_rows', 'error_rows',
        'warning_rows', 'summary', 'started_at', 'completed_at',
        'correlation_id', 'region_id', 'district_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function errors()
    {
        return $this->hasMany(MarkImportRunError::class, 'run_id');
    }

    public function rows()
    {
        return $this->hasMany(MarkImportRunRow::class, 'run_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public static function start(int $userId, int $examYearId, int $schoolId, int $subjectId, string $fileName, ?int $fileSize = null, string $scope = 'school'): self
    {
        return static::create([
            'user_id' => $userId,
            'exam_year_id' => $examYearId,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'scope' => $scope,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function complete(int $totalRows, int $successRows, int $errorRows, int $warningRows = 0, ?string $summary = null): self
    {
        $this->update([
            'status' => $errorRows > 0 ? self::STATUS_COMPLETED : self::STATUS_COMPLETED,
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'error_rows' => $errorRows,
            'warning_rows' => $warningRows,
            'summary' => $summary,
            'completed_at' => now(),
        ]);

        return $this;
    }

    public function fail(string $summary): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'summary' => $summary,
            'completed_at' => now(),
        ]);

        return $this;
    }
}
