<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultPortalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'result_portal_link_id',
        'label',
        'region_slug',
        'file_id',
        'file_path',
        'sort_key',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(ResultPortalLink::class, 'result_portal_link_id');
    }
}
