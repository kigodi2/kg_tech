<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];

    // Role code constants
    const CODE_ADMIN = 'admin';
    const CODE_REGIONAL_OFFICER = 'regional_officer';
    const CODE_DISTRICT_DATA_ENTRY_OFFICER = 'district_data_entry_officer';
    const CODE_DISTRICT_SUPERVISOR = 'district_supervisor';
    const CODE_SCHOOL_REGISTRAR = 'school_registrar';

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope: Get role by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
