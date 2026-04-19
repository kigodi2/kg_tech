<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827}.block{margin-top:16px;padding:10px;border:1px solid #cbd5e1}</style></head>
<body>
    <h1>Moderator Version</h1>
    <h2>{{ $paper->project->subject?->name }} · {{ $paper->paper_code }}</h2>
    @foreach($paper->sections as $section)
        <div class="block">
            <strong>{{ $section->section_name }}</strong>
            <div>{{ $section->instructions }}</div>
            @foreach($section->slots as $slot)
                <div style="margin-top:10px;">
                    <strong>{{ $slot->slot_label }}</strong> · {{ $slot->question_type }} · {{ $slot->marks_per_question }} marks
                    <div>{!! nl2br(e($slot->assignedQuestion?->question_text ?: 'Question pending assignment')) !!}</div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
