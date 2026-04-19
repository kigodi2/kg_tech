<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingRule extends Model
{
    use HasFactory;

    protected $table = 'grading_rules';

    protected $fillable = [
        'grading_profile_id',
        'grade',
        'min_percentage',
        'max_percentage',
        'grade_name',
        'points',
        'is_principal',
        'is_subsidiary',
        'sort_order',
        'is_disabled',
    ];

    protected $casts = [
        'is_principal' => 'boolean',
        'is_subsidiary' => 'boolean',
        'is_disabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function gradingProfile()
    {
        return $this->belongsTo(GradingProfile::class);
    }

    public function scopeByProfile($query, $profileId)
    {
        return $query->where('grading_profile_id', $profileId);
    }

    public static function getGradeForMarks($profileId, $marks)
    {
        return self::where('grading_profile_id', $profileId)
            ->where('min_percentage', '<=', $marks)
            ->where('max_percentage', '>=', $marks)
            ->first();
    }
}
