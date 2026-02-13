<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BulkImport extends Model
{
    protected $fillable = [
        'school_id',
        'district_id',
        'exam_year_id',
        'scope_type',
        'scope_id',
        'status',
        'total_files',
        'processed_files',
        'total_schools',
        'processed_schools',
        'created_by',
        'started_at',
        'completed_at',
        'error_summary',
        'zip_hash',
        'manifest_hash',
        'signature',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function examYear(): BelongsTo
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(BulkImportFile::class);
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'bulk_import_schools')
            ->withPivot('school_code', 'school_name', 'status', 'total_subjects', 'processed_subjects', 
                       'total_candidates', 'successful_candidates', 'failed_candidates', 'error_summary', 
                       'started_at', 'completed_at')
            ->withTimestamps();
    }

    /**
     * Check if this is a district-level import
     */
    public function isDistrictImport(): bool
    {
        return $this->scope_type === 'district';
    }

    /**
     * Check if this is a school-level import
     */
    public function isSchoolImport(): bool
    {
        return $this->scope_type === 'school';
    }

    /**
     * Get overall progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->isDistrictImport()) {
            if ($this->total_schools === 0) {
                return 0;
            }
            return (int) (($this->processed_schools / $this->total_schools) * 100);
        }

        if ($this->total_files === 0) {
            return 0;
        }

        return (int) (($this->processed_files / $this->total_files) * 100);
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        if ($this->isDistrictImport()) {
            $importedSchools = $this->schools()
                ->wherePivot('status', '!=', 'pending')
                ->get();

            return [
                'total_schools' => $this->total_schools,
                'processed_schools' => $this->processed_schools,
                'successful_schools' => $importedSchools->where('pivot.status', 'success')->count(),
                'partial_schools' => $importedSchools->where('pivot.status', 'partial')->count(),
                'failed_schools' => $importedSchools->where('pivot.status', 'failed')->count(),
                'total_candidates' => $importedSchools->sum('pivot.total_candidates'),
                'successful_candidates' => $importedSchools->sum('pivot.successful_candidates'),
                'failed_candidates' => $importedSchools->sum('pivot.failed_candidates'),
                'progress_percentage' => $this->getProgressPercentage(),
            ];
        }

        $files = $this->files()->get();

        return [
            'total_files' => $this->total_files,
            'processed_files' => $this->processed_files,
            'total_candidates' => $files->sum('rows_total'),
            'successful_candidates' => $files->sum('rows_success'),
            'failed_candidates' => $files->sum('rows_failed'),
            'progress_percentage' => $this->getProgressPercentage(),
        ];
    }
}
