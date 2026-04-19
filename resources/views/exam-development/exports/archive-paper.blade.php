<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827}.meta{margin-bottom:16px}.block{margin-top:12px;padding-top:8px;border-top:1px solid #cbd5e1}</style></head>
<body>
    <h1>Archive Copy</h1>
    <div class="meta">Project: {{ $paper->project->project_name }} | Paper: {{ $paper->paper_code }} | Generated: {{ now()->format('Y-m-d H:i') }}</div>
    @foreach($paper->sections as $section)
        <div class="block">
            <strong>{{ $section->section_name }}</strong>
            @foreach($section->slots as $slot)
                <div style="margin-top:8px;">
                    <strong>{{ $slot->slot_label }}</strong> · {{ $slot->assignedQuestion?->title ?: 'Unassigned' }}
                    <div>{!! nl2br(e($slot->assignedQuestion?->question_text ?: 'Question pending assignment')) !!}</div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
