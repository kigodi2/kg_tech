<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Submission Approved</title>
</head>
<body>
    <h1>Exam Submission Approved</h1>

    <p>Dear {{ $user->name }},</p>

    <p>Your exam submission has been approved by the administrator.</p>

    <h2>Submission Details:</h2>
    <ul>
        <li><strong>Exam Type:</strong> {{ $examType->name }}</li>
        <li><strong>Subject:</strong> {{ $subject->name }}</li>
        <li><strong>Original Filename:</strong> {{ $submission->original_filename }}</li>
        <li><strong>Submitted At:</strong> {{ $submission->submitted_at->format('Y-m-d H:i:s') }}</li>
        <li><strong>Approved At:</strong> {{ $submission->validated_at->format('Y-m-d H:i:s') }}</li>
    </ul>

    <p>You can now proceed with your exam preparation or any next steps.</p>

    <p>Best regards,<br>
    NECTA Exam Management System</p>
</body>
</html>