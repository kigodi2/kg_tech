<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkEntryLifecycleState extends Model {
    
    protected $table = 'mark_entry_lifecycle_states';

    protected $fillable = [
        'mark_import_batch_id',
        'current_state',
        'previous_state',
        'transitioned_by',
        'transitioned_at',
        'transition_reason',
        'history',
    ];

    protected $casts = [
        'transitioned_at' => 'datetime',
        'history' => 'array',
    ];

    public function batch() {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function transitionedByUser() {
        return $this->belongsTo(User::class, 'transitioned_by');
    }
}
