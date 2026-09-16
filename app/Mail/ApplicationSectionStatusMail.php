<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationSectionStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $section,
        public string $reason,
        public ?string $documentName = null,
    ) {}

    public function build(): self
    {
        $isDocument = $this->documentName !== null;
        $subject = $isDocument
            ? 'Scholarship Application - Document Rejected'
            : 'Scholarship Application - ' . $this->section . ' Rejected';

        return $this->subject($subject)->view('emails/application-section-rejected');
    }
}