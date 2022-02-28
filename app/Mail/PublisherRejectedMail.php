<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PublisherRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reason;
    public $supportLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reason, $supportLink)
    {
        $this->reason = $reason;
        $this->supportLink = $supportLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $app_name = config("general.SITE_NAME");

        return $this->subject( "{$app_name} - Publisher request rejected")->view('emails.publisher-rejected-dark');
    }
}
