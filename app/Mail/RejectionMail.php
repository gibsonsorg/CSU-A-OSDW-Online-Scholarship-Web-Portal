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

    public function __construct(User $user, string $message = '')
    {
        $this->user = $user;
        $this->message = $message;
    }

    public function build(): self
    {
        return $this->subject('Account Application Status')
            ->view('emails.rejection')
            ->with([
                'user' => $this->user,
                'rejectionReason' => $this->message,
            ]);
    }
}
