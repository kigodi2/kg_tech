<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'registration_number',
        'ownership',
        'district_id',
        'council_id',
        'region_id',
        'school_type',
        'education_level',
        'address',
        'phone',
        'email',
        'principal_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_PRIMARY = 'PRIMARY';
    const TYPE_SECONDARY = 'SECONDARY';
    const TYPE_BOTH = 'BOTH';

    public function council()
    {
        return $this->belongsTo(DistrictCouncil::class, 'council_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function markImportBatches()
    {
        return $this->hasMany(MarkImportBatch::class);
    }

    public function registrations()
    {
        return $this->hasManyThrough(
            CandidateExamRegistration::class,
            Candidate::class,
            'school_id',
            'candidate_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCouncil($query, $councilId)
    {
        return $query->where('council_id', $councilId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('school_type', $type);
    }

    /**
     * Validate school type based on exam registrations
     */
    public function validateExamTypeSchoolType()
    {
        // Check if this school has CSEE or ACSEE registrations
        $hasSecondaryExam = $this->registrations()
            ->whereHas('examType', function ($query) {
                $query->whereIn('code', ['CSEE', 'ACSEE']);
            })
            ->exists();

        if ($hasSecondaryExam && $this->school_type !== self::TYPE_SECONDARY && $this->school_type !== self::TYPE_BOTH) {
            throw new \Exception('Schools registered for CSEE or ACSEE exams must have school_type "SECONDARY" or "BOTH"');
        }

        // Check if this school has PSLE registrations
        $hasPrimaryExam = $this->registrations()
            ->whereHas('examType', function ($query) {
                $query->where('code', 'PSLE');
            })
            ->exists();

        if ($hasPrimaryExam && $this->school_type !== self::TYPE_PRIMARY && $this->school_type !== self::TYPE_BOTH) {
            throw new \Exception('Schools registered for PSLE exams must have school_type "PRIMARY" or "BOTH"');
        }
    }

    /**
     * Auto-enforce school types based on exam registrations
     */
    public function enforceExamTypeSchoolType()
    {
        // Check for CSEE or ACSEE registrations
        $hasSecondaryExam = $this->registrations()
            ->whereHas('examType', function ($query) {
                $query->whereIn('code', ['CSEE', 'ACSEE']);
            })
            ->exists();

        // Check for PSLE registrations
        $hasPrimaryExam = $this->registrations()
            ->whereHas('examType', function ($query) {
                $query->where('code', 'PSLE');
            })
            ->exists();

        // Determine the required type
        $newType = null;

        if ($hasSecondaryExam && $hasPrimaryExam) {
            // School has both PRIMARY and SECONDARY exams
            $newType = self::TYPE_BOTH;
        } elseif ($hasSecondaryExam) {
            // School has CSEE/ACSEE only
            $newType = self::TYPE_SECONDARY;
        } elseif ($hasPrimaryExam) {
            // School has PSLE only
            $newType = self::TYPE_PRIMARY;
        }

        // Apply the type if it differs from current
        if ($newType && $this->school_type !== $newType) {
            $this->school_type = $newType;
            $this->save();
        }
    }

    /**
     * Auto-enforce SECONDARY type for CSEE/ACSEE schools (deprecated - use enforceExamTypeSchoolType)
     */
    public function ensureSecondaryTypeForSecondaryExams()
    {
        $this->enforceExamTypeSchoolType();
    }
}
