<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{
    use HasFactory;

    protected $table = 'class_levels';

    protected $fillable = [
        'code',
        'name',
        'level_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_STANDARD = 'STANDARD';
    const TYPE_FORM = 'FORM';

    public function examTypes()
    {
        return $this->belongsToMany(
            ExamType::class,
            'exam_type_class_levels',
            'class_level_id',
            'exam_type_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('level_type', $type);
    }
}
