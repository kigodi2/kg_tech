<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ACSEE Entry Outliers QA Report</title>
    <style>
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; color: #222; margin: 12px; }
        .header { margin-bottom: 12px; border-bottom: 1px solid #999; padding-bottom: 8px; }
        .title { font-size: 16px; font-weight: bold; }
        .subtitle { font-size: 11px; color: #444; margin-top: 2px; }
        .meta { margin-top: 6px; font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d2d2d2; padding: 5px 6px; vertical-align: top; }
        th { background: #1f4e79; color: #fff; text-align: left; font-size: 9px; }
        td { font-size: 9px; }
        .footer { margin-top: 10px; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">ACSEE Entry Outliers (QA) Report</div>
        <div class="subtitle">{{ $tabLabel }}</div>
        <div class="meta">
            Generated: {{ $generatedAt->format('d M Y H:i') }} | By: {{ $generatedBy }}
            @if(!empty($filters['exam_year_id'])) | Exam Year ID: {{ $filters['exam_year_id'] }} @endif
            @if(!empty($filters['status'])) | Status: {{ $filters['status'] }} @endif
            @if(!empty($filters['school_id'])) | School ID: {{ $filters['school_id'] }} @endif
            @if(!empty($filters['subject_id'])) | Subject ID: {{ $filters['subject_id'] }} @endif
            @if(!empty($filters['q'])) | Search: {{ $filters['q'] }} @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Batch</th>
                <th>School</th>
                <th>Subject</th>
                <th>Candidate</th>
                <th>Issue</th>
                <th>Message</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['batch_code'] ?? '—' }}</td>
                    <td>{{ $row['school_name'] ?? '—' }}</td>
                    <td>{{ $row['subject_name'] ?? '—' }}</td>
                    <td>{{ $row['candidate_index_number'] ?? '—' }}</td>
                    <td>{{ $row['issue_type'] ?? '—' }}</td>
                    <td>{{ $row['message'] ?? '—' }}</td>
                    <td>{{ $row['review_action'] ?? 'Manual review' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">No outlier rows matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Rows exported: {{ count($rows) }}
    </div>
</body>
</html>
