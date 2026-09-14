<?php

namespace App\Mail;

use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IdentityVerificationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ?Scholarship $scholarship)
    {
    }

    public function build(): self
    {
        return $this->subject('Identity Verification Approved')
            ->view('emails.identity-verification-approved')
            ->with([
                'user' => $this->user,
                'scholarship' => $this->scholarship,
            ]);
    }
}
