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
        public string $firstName,
        public string $scholarshipName,
        public array $sections,
    ) {}

    public function build(): self
    {
        $subject = 'Update on Your Scholarship Application — ' . $this->scholarshipName;

        return $this->subject($subject)->view('emails/application-section-rejected');
    }
}