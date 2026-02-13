<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkEntryChange extends Model {
    
    protected $table = 'mark_entry_changes';

    protected $fillable = [
        'raw_mark_id',
        'changed_by',
        'change_type',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'changed_at',
        'ip_address',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function mark() {
        return $this->belongsTo(RawMark::class, 'raw_mark_id');
    }

    public function changedByUser() {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
