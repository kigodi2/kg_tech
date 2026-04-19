<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 8px; }
        .section { margin-top: 18px; }
        .question { border-top: 1px solid #cbd5e1; padding-top: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $paper->project->examType?->code }} {{ $paper->project->subject?->name }}</h1>
    <h2>{{ $paper->paper_code }} - {{ $paper->paper_name }}</h2>
    <p>Duration: {{ $paper->duration_minutes }} minutes | Total Marks: {{ $paper->total_marks }}</p>
    @foreach($paper->sections as $section)
        <div class="section">
            <h3>{{ $section->section_code ?: 'Section' }} - {{ $section->section_name }}</h3>
            <p>{{ $section->instructions }}</p>
            @foreach($section->slots as $slot)
                <div class="question">
                    <strong>{{ $slot->slot_label }}</strong>
                    <span>({{ $slot->marks_per_question }} marks)</span>
                    <div>{!! nl2br(e($slot->assignedQuestion?->question_text ?: 'Question pending assignment')) !!}</div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
