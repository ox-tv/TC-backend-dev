<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeleteAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public $deleteAccountLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link)
    {
        $this->deleteAccountLink = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $app_name = config("general.SITE_NAME");

        return $this->subject( "{$app_name} - Delete Account")->view('emails.delete-account-dark');
    }
}
