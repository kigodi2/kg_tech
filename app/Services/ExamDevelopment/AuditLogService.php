<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamDevelopmentAuditLog;

class AuditLogService
{
    public function record(string $action, ?string $entityType = null, $entityId = null, ?array $oldValues = null, ?array $newValues = null, array $metadata = [], ?string $status = 'success'): ExamDevelopmentAuditLog
    {
        return ExamDevelopmentAuditLog::query()->create([
            'module' => 'exam-development',
            'action' => $action,
            'exam_year_id' => null,
            'user_id' => auth()->id(),
            'status' => $status,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
