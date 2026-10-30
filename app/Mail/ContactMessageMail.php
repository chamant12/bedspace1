<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// To the admin: a visitor used the contact form (reply goes straight to the visitor)
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function build()
    {
        return $this->subject('Website enquiry: '.($this->contactMessage->subject ?: 'message from '.$this->contactMessage->name))
            ->replyTo($this->contactMessage->email, $this->contactMessage->name)
            ->view('email.contactMessage');
    }
}
