<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenewalRequirementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $scholarshipName,
    ) {}

    public function notificationSubject(): string
    {
        return 'Action Required: Submit Your Requirements for This Semester';
    }

    public function notificationBody(): string
    {
        return "Dear {$this->studentName},\n\n"
            . "Your approved grant for {$this->scholarshipName} is being renewed for this semester. To continue receiving this scholarship, please log in to the CSU Grants portal and submit your updated supporting documents.\n\n"
            . "What to do:\n"
            . "1. Log in to the CSU Grants portal.\n"
            . "2. Open your application and tap View & Apply.\n"
            . "3. Upload your documents for this semester and submit.\n\n"
            . "If you have any questions, feel free to visit the OSDW Aparri office.\n\n"
            . "Thank you, Scholarship Office";
    }

    public function build(): self
    {
        return $this->subject($this->notificationSubject())
            ->view('emails.renewal-requirement')
            ->with([
                'studentName' => $this->studentName,
                'scholarshipName' => $this->scholarshipName,
                'notificationBody' => $this->notificationBody(),
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Renewal requirement email job failed', [
            'student_name' => $this->studentName,
            'scholarship' => $this->scholarshipName,
            'error' => $exception->getMessage(),
        ]);
    }
}
