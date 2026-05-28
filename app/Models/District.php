<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $appends = [
        'display_code',
    ];

    protected $fillable = [
        'code',
        'name',
        'region_id',
        'description',
        'status',
        'candidates_count',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
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
        return $query->where('status', 'active');
    }

    public static function normalizeCodeForDisplay(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        if (preg_match('/^PSLE\d{2}[-\s]?(\d{4})$/', $code, $matches)) {
            return 'PS' . $matches[1];
        }

        return $code;
    }

    public function getDisplayCodeAttribute(): ?string
    {
        return self::normalizeCodeForDisplay($this->code);
    }
}
