<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HeroMembershipExipreSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public $heroMembershipPageLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link)
    {
        $this->heroMembershipPageLink = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Hodl Membership is ending soon')->view('emails.hero-membership-expire-soon-dark');
    }
}
