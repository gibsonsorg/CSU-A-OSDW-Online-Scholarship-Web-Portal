<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $scholarshipName;
    public string $status;

    public function __construct(string $userName, string $scholarshipName, string $status)
    {
        $this->userName = $userName;
        $this->scholarshipName = $scholarshipName;
        $this->status = strtolower($status);
    }

    public function build(): self
    {
        $subject = $this->status === 'approved'
            ? 'Scholarship Application Approved'
            : 'Scholarship Application Update';

        return $this->subject($subject)
            ->view('emails.application-status')
            ->with([
                'userName' => $this->userName,
                'scholarshipName' => $this->scholarshipName,
                'status' => $this->status,
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Application status email job failed', [
            'scholarship' => $this->scholarshipName,
            'status' => $this->status,
            'error' => $exception->getMessage(),
        ]);
    }
}
