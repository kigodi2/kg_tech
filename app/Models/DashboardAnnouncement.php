<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardAnnouncement extends Model
{
    use HasFactory;

    public const TYPE_EVENT = 'event';
    public const TYPE_NEWS = 'news';

    protected $fillable = [
        'type',
        'title',
        'publish_date',
        'link_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEvents($query)
    {
        return $query->where('type', self::TYPE_EVENT);
    }

    public function scopeNews($query)
    {
        return $query->where('type', self::TYPE_NEWS);
    }
}
