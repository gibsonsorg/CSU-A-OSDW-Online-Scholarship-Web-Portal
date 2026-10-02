<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $scholarshipName,
    ) {}

    public function notificationSubject(): string
    {
        return 'Your Scholarship Application Has Been Received';
    }

    public function notificationBody(): string
    {
        return 'Dear ' . $this->studentName . ', this is to confirm that your application for '
            . $this->scholarshipName
            . ' has been received and is currently under review. We will notify you by email once a decision has been made. If you have any questions in the meantime, feel free to visit the OSDW office in Aparri.';
    }

    public function build(): self
    {
        return $this->subject($this->notificationSubject())
            ->view('emails.application-received')
            ->with(['notificationBody' => $this->notificationBody()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Application received email job failed', [
            'scholarship' => $this->scholarshipName,
            'error' => $exception->getMessage(),
        ]);
    }
}