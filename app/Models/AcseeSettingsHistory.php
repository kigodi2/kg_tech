<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcseeSettingsHistory extends Model
{
    protected $table = 'acsee_settings_history';

    public $timestamps = false;

    protected $fillable = ['setting_id', 'old_value', 'new_value', 'changed_by', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function setting()
    {
        return $this->belongsTo(SystemSetting::class, 'setting_id');
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
