<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RejectionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $message;
    public int $attempt;

    public function __construct(User $user, string $message = '', int $attempt = 1)
    {
        $this->user = $user;
        $this->message = $message;
        $this->attempt = $attempt;
    }

    public function build(): self
    {
        $subject = $this->attempt >= 2 
            ? 'Registration Application Rejected — Maximum Attempts Reached'
            : 'Registration Application Rejected';
            
        return $this->subject($subject)
            ->view('emails.rejection')
            ->with([
                'user' => $this->user,
                'rejectionReason' => $this->message,
                'attempt' => $this->attempt,
            ]);
    }
}
