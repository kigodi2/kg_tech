<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserScope extends Model
{
    use HasFactory;

    protected $table = 'user_scopes';

    protected $fillable = ['user_id', 'scope_type', 'scope_id'];

    // Scope type constants
    const SCOPE_REGION = 'region';
    const SCOPE_DISTRICT = 'district';
    const SCOPE_SCHOOL = 'school';

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic scope relationship
     */
    public function scopeable()
    {
        return match ($this->scope_type) {
            self::SCOPE_REGION => $this->belongsTo(Region::class, 'scope_id'),
            self::SCOPE_DISTRICT => $this->belongsTo(District::class, 'scope_id'),
            self::SCOPE_SCHOOL => $this->belongsTo(School::class, 'scope_id'),
            default => null,
        };
    }

    /**
     * Get the actual scoped entity (Region, District, or School)
     */
    public function getScopeableAttribute()
    {
        return match ($this->scope_type) {
            self::SCOPE_REGION => Region::find($this->scope_id),
            self::SCOPE_DISTRICT => District::find($this->scope_id),
            self::SCOPE_SCHOOL => School::find($this->scope_id),
            default => null,
        };
    }
}
