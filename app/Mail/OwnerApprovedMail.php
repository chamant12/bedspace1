<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// To the owner: their account was approved
class OwnerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $owner)
    {
    }

    public function build()
    {
        return $this->subject('Your Bedspace account is approved')->view('email.ownerApproved');
    }
}
