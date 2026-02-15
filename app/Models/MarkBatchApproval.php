<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkBatchApproval extends Model {
    
    protected $table = 'mark_batch_approvals';

    protected $fillable = [
        'mark_import_batch_id',
        'approval_level',
        'approval_type',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'signature',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function batch() {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function approvedByUser() {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
