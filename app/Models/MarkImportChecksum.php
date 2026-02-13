<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkImportChecksum extends Model
{
    use HasFactory;

    protected $table = 'mark_import_checksums';

    protected $fillable = [
        'mark_import_batch_id',
        'checksum',
        'candidate_count',
        'candidate_index_numbers',
        'generated_at',
    ];

    protected $casts = [
        'candidate_index_numbers' => 'array',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function batch()
    {
        return $this->belongsTo(MarkImportBatch::class, 'mark_import_batch_id');
    }

    // ==================== METHODS ====================

    /**
     * Verify checksum against provided data
     */
    public function verifyChecksum(string $providedChecksum): bool
    {
        return hash_equals($this->checksum, $providedChecksum);
    }
}
