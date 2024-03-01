<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChannelQualifiedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $channelName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($channelName)
    {
        $this->channelName = $channelName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your channel has been qualified')->view('emails.channel-qualified-dark');
    }
}
