<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827}.block{margin-top:18px;border-top:1px solid #cbd5e1;padding-top:10px}</style></head>
<body>
    <h1>Confidential Marking Scheme</h1>
    <h2>{{ $paper->project->subject?->name }} · {{ $paper->paper_code }}</h2>
    @foreach($paper->sections as $section)
        @foreach($section->slots as $slot)
            <div class="block">
                <strong>{{ $slot->slot_label }} · {{ $slot->assignedQuestion?->title ?: $slot->assignedQuestion?->topic_name }}</strong>
                @foreach($slot->assignedQuestion?->markingSchemes ?? [] as $scheme)
                    <div style="margin-top:8px;">{{ $scheme->scheme_type }} · {{ $scheme->total_marks }} marks</div>
                    <div>{!! nl2br(e($scheme->answer_text)) !!}</div>
                    @foreach($scheme->items as $item)
                        <div>{{ $item->item_label }} {{ $item->description }} ({{ $item->marks }})</div>
                    @endforeach
                @endforeach
            </div>
        @endforeach
    @endforeach
</body>
</html>
