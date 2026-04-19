<?php

namespace App\Mail;

use App\Models\ExamSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExamSubmissionApproved extends Mailable
{
    use Queueable, SerializesModels;

    public ExamSubmission $submission;

    /**
     * Create a new message instance.
     */
    public function __construct(ExamSubmission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Exam Submission Has Been Approved',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.exam-submission-approved',
            with: [
                'submission' => $this->submission,
                'user' => $this->submission->user,
                'examType' => $this->submission->examType,
                'subject' => $this->submission->subject,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}