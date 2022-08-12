<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PublisherVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verification_link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link)
    {
        $this->verification_link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Email verification')->view('emails.publisher-email-verification-dark');
    }
}
