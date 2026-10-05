<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationCorrectionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $scholarshipName,
        public string $rejectionRemark,
        public CarbonInterface $resubmissionDeadline,
    ) {}

    public function notificationSubject(): string
    {
        return 'Action Required: Your Scholarship Application Needs Corrections';
    }

    public function notificationBody(): string
    {
        return 'Hi ' . $this->studentName . ",\n\n"
            . 'Your application for ' . $this->scholarshipName . " needs a few corrections before it can be reviewed.\n\n"
            . 'What to fix: ' . $this->rejectionRemark . "\n\n"
            . "What to do:\n\n"
            . "1. Log in to the CSU Grants portal.\n"
            . "2. Open your application and tap View & Apply.\n"
            . "3. Correct the items above and submit again.\n\n"
            . 'Deadline: ' . $this->resubmissionDeadline->format('F j, Y g:i A') . "\n\n"
            . 'Your slot is being held for you until then. This is your final resubmission, so please double-check your documents and form before sending. If the deadline passes, the slot will be released and your application will expire.' . "\n\n"
            . "If you have questions, visit the OSDW Aparri office.\n\n"
            . 'Thank you,';
    }

    public function build(): self
    {
        return $this->subject($this->notificationSubject())
            ->view('emails.application-correction')
            ->with([
                'studentName' => $this->studentName,
                'scholarshipName' => $this->scholarshipName,
                'rejectionRemark' => $this->rejectionRemark,
                'deadline' => $this->resubmissionDeadline->format('F j, Y g:i A'),
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Application correction email job failed', [
            'scholarship' => $this->scholarshipName,
            'error' => $exception->getMessage(),
        ]);
    }
}