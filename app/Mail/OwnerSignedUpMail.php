<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// To the admin: a property owner signed up and awaits approval
class OwnerSignedUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $owner)
    {
    }

    public function build()
    {
        return $this->subject('New property owner awaiting approval: '.$this->owner->name)
            ->view('email.ownerSignedUp');
    }
}
