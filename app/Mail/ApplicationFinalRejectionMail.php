<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationFinalRejectionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $scholarshipName,
    ) {}

    public function build(): self
    {
        return $this->subject('Update on Your Scholarship Application')
            ->view('emails.application-final-rejection');
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Final application rejection notification failed', [
            'scholarship' => $this->scholarshipName,
            'error' => $exception->getMessage(),
        ]);
    }
}