<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkModerationReview extends Model {
    
    protected $table = 'mark_moderation_reviews';

    protected $fillable = [
        'mark_import_batch_id',
        'reviewer_id',
        'review_type',
        'status',
        'feedback',
        'flagged_issues',
        'reviewed_at',
        'signature',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'flagged_issues' => 'array',
    ];

    public function batch() {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    public function reviewer() {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
