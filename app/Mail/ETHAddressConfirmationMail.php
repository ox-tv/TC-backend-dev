<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ETHAddressConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $confirmationLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link)
    {
        $this->confirmationLink = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Change ETH Address Confirmation')->view('emails.eth-address-confirmation-dark');
    }
}
