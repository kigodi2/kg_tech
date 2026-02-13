<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamType extends Model
{
    use HasFactory;

    protected $table = 'exam_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'level',
        'education_level',
        'min_candidates',
        'max_papers',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const CODE_PSLE = 'PSLE';
    const CODE_SFNA = 'SFNA';
    const CODE_FTNA = 'FTNA';
    const CODE_CSEE = 'CSEE';
    const CODE_ACSEE = 'ACSEE';

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function combinations()
    {
        return $this->hasMany(Combination::class);
    }

    public function papers()
    {
        return $this->hasMany(ExamPaper::class);
    }

    public function registrations()
    {
        return $this->hasMany(CandidateExamRegistration::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'exam_type', 'code');
    }

    public function classLevels()
    {
        return $this->belongsToMany(
            ClassLevel::class,
            'exam_type_class_levels',
            'exam_type_id',
            'class_level_id'
        );
    }

    public function gradingProfiles()
    {
        return $this->hasMany(GradingProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
