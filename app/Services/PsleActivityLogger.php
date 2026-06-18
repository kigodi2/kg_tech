<?php

namespace App\Services;

use App\Models\PsleActivityLog;
use App\Models\School;

class PsleActivityLogger
{
    public function log(array $data): PsleActivityLog
    {
        $school = null;
        if (!empty($data['school_id'])) {
            $school = School::find($data['school_id']);
        }

        return PsleActivityLog::create([
            'exam_year_id' => $data['exam_year_id'] ?? null,
            'region_id' => $data['region_id'] ?? $school?->region_id,
            'district_id' => $data['district_id'] ?? $school?->district_id,
            'school_id' => $data['school_id'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'event_type' => $data['event_type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'affected_candidates' => (int) ($data['affected_candidates'] ?? 0),
            'affected_marks' => (int) ($data['affected_marks'] ?? 0),
            'ip_address' => $data['ip_address'] ?? request()?->ip(),
            'user_agent' => isset($data['user_agent'])
                ? substr((string) $data['user_agent'], 0, 255)
                : substr((string) request()?->userAgent(), 0, 255),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
