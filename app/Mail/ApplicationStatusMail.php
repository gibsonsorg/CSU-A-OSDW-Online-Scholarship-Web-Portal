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
    public ?string $rejectionReason;

    public function __construct(string $userName, string $scholarshipName, string $status, ?string $rejectionReason = null)
    {
        $this->userName = $userName;
        $this->scholarshipName = $scholarshipName;
        $this->status = strtolower($status);
        $this->rejectionReason = $rejectionReason;
    }

    public function build(): self
    {
        return $this->subject($this->notificationSubject())
            ->view('emails.application-status')
            ->with([
                'userName' => $this->userName,
                'scholarshipName' => $this->scholarshipName,
                'status' => $this->status,
                'rejectionReason' => $this->rejectionReason,
            ]);
    }

    public function notificationSubject(): string
    {
        return $this->status === 'approved'
            ? 'Scholarship Application Approved'
            : 'Scholarship Application Update';
    }

    public function notificationBody(): string
    {
        if ($this->status === 'approved') {
            $message = 'Congratulations! Your application for the "' . $this->scholarshipName . '" has been APPROVED.'
                . "\n\nYou may now proceed with the next steps or wait for further instructions.";
        } elseif ($this->rejectionReason === 'Does not meet the eligibility requirements') {
            $message = 'This is to inform you that your application for ' . $this->scholarshipName
                . ' did not meet certain eligibility requirements. You are welcome to apply for another scholarship offered by the government. If you have any questions, feel free to visit the OSDW office in Aparri.';
        } else {
            $message = "We regret to inform you that your application for '{$this->scholarshipName}' has been REJECTED. Please ensure your submitted documents are clear and complete and that you fill out the application form correctly. You may check your application status for more details and reapply once all requirements are met.";
        }

        return 'Dear ' . $this->userName . ",\n\n" . $message . "\n\nBest regards,\nScholarship Office";
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
