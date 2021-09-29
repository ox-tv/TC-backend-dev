<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ETHAddressConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $confirmation_link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link)
    {
        $this->confirmation_link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $app_name = config("general.SITE_NAME");

        return $this->subject( "{$app_name} - Change ETH Address Confirmation")->view('emails.eth-address-confirmation');
    }
}
