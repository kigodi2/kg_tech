<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectPanelAssignment extends Model
{
    use HasFactory;

    protected $table = 'subject_panel_assignments';

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'exam_year_id',
        'subject_id',
        'region_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the assigned subject IDs for a given panel leader user.
     */
    public static function getSubjectIdsForUser(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('subject_id')
            ->toArray();
    }

    /**
     * Get the active assignment record for a panel leader.
     */
    public static function getActiveForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->with(['subject', 'examYear', 'region'])
            ->first();
    }
}
