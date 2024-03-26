<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PublisherLowTCGBalanceMail extends Mailable
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
        return $this->from('monetize@todayscrypto.com')->subject('Monetization - Low TCG Balance')->view('emails.monetization-low-tcg-balance-dark');
    }
}
