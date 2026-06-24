<?php

namespace App\Mail;

use App\Models\AssessmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssessmentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssessmentApplication $application,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        $testName = $this->application->test?->name ?? 'Mapeamento Comportamental';
        return new Envelope(
            subject: "{$testName} — Sara Linhar Consultoria",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.assessment-link',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
