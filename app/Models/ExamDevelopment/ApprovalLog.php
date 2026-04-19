<?php

namespace App\Models\ExamDevelopment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'old_status',
        'new_status',
        'comment',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
