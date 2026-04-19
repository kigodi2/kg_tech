<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultPortalLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'region_id',
        'school_id',
        'token_hash',
        'name',
        'meta_json',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ResultPortalItem::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
