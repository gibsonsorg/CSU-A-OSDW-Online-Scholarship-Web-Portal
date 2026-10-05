<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationClosureMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $scholarshipName,
        public string $rejectionRemark,
    ) {}

    public function notificationSubject(): string
    {
        return 'Your Scholarship Application Has Been Closed';
    }

    public function notificationBody(): string
    {
        return 'Dear ' . $this->studentName . ",\n\n"
            . 'Your application for ' . $this->scholarshipName . " has been closed after its final resubmission.\n\n"
            . 'Reason for closure: ' . $this->rejectionRemark . "\n\n"
            . "Best regards,\n\n"
            . 'Scholarship Office';
    }

    public function build(): self
    {
        return $this->subject($this->notificationSubject())
            ->view('emails.application-closure')
            ->with([
                'studentName' => $this->studentName,
                'scholarshipName' => $this->scholarshipName,
                'rejectionRemark' => $this->rejectionRemark,
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Application closure email job failed', [
            'scholarship' => $this->scholarshipName,
            'error' => $exception->getMessage(),
        ]);
    }
}