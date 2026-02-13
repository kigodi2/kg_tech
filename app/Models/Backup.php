<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Backup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admin_id',
        'type',
        'exam_year_id',
        'filename',
        'path',
        'manifest',
        'checksum_algo',
        'checksum',
        'signature',
        'size_bytes',
        'verified',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'manifest' => 'json',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==================== SCOPES ====================

    public function scopeFullSystem($query)
    {
        return $query->where('type', 'full_system');
    }

    public function scopeExamYear($query)
    {
        return $query->where('type', 'exam_year');
    }

    public function scopeMetadataOnly($query)
    {
        return $query->where('type', 'metadata_only');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== HELPERS ====================

    public function isFullSystem(): bool
    {
        return $this->type === 'full_system';
    }

    public function isExamYearBackup(): bool
    {
        return $this->type === 'exam_year';
    }

    public function isMetadataOnly(): bool
    {
        return $this->type === 'metadata_only';
    }

    public function getStatusBadge(): string
    {
        if (!$this->verified) {
            return 'warning';
        }
        return 'success';
    }

    public function getStatusLabel(): string
    {
        if (!$this->verified) {
            return 'Unverified';
        }
        return 'Verified';
    }

    public function getSizeFormatted(): string
    {
        $bytes = (int)$this->size_bytes;
        
        if ($bytes >= 1073741824) { // 1 GB
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) { // 1 MB
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) { // 1 KB
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    public function getFullPath(): string
    {
        return storage_path('app/' . $this->path);
    }

    public function exists(): bool
    {
        return file_exists($this->getFullPath());
    }
}
