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
        return "Greetings from the Office of Student Development and Welfare (OSDW)!\n\n"
            . "We are pleased to confirm that we have successfully received your application for the "
            . $this->scholarshipName
            . ".\n\n"
            . "Your application is currently under review. Our office will carefully evaluate the information and documents you submitted.\n\n"
            . "You will receive an email notification once a decision has been made regarding your application. You may also monitor your application status through the CSU Aparri Scholarship Portal.\n\n"
            . "Thank you for your interest in the scholarship program. We appreciate your commitment to pursuing your education.\n\n"
            . "Best regards,\n\n"
            . "OSDW Aparri";
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