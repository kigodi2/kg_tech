<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarkingCentre extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MarkEntryAssignment::class);
    }
}
