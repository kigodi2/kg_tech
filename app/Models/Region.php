<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'schools_count',
        'candidates_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function councils()
    {
        return $this->hasMany(DistrictCouncil::class);
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function schools()
    {
        return $this->hasMany(School::class);
    }

    public function candidates()
    {
        return $this->hasManyThrough(Candidate::class, School::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
