<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Submission Rejected</title>
</head>
<body>
    <h1>Exam Submission Rejected</h1>

    <p>Dear {{ $user->name }},</p>

    <p>Your exam submission has been rejected by the administrator.</p>

    <h2>Submission Details:</h2>
    <ul>
        <li><strong>Exam Type:</strong> {{ $examType->name }}</li>
        <li><strong>Subject:</strong> {{ $subject->name }}</li>
        <li><strong>Original Filename:</strong> {{ $submission->original_filename }}</li>
        <li><strong>Submitted At:</strong> {{ $submission->submitted_at->format('Y-m-d H:i:s') }}</li>
        <li><strong>Rejected At:</strong> {{ $submission->validated_at->format('Y-m-d H:i:s') }}</li>
    </ul>

    <h2>Rejection Reason:</h2>
    <p>{{ $rejectionReason }}</p>

    <p>Please review the feedback and resubmit your exam paper after making the necessary corrections.</p>

    <p>Best regards,<br>
    NECTA Exam Management System</p>
</body>
</html>