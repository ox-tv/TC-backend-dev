<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonetizationPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $amount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('monetize@oxintv.com')->subject('You’ve got paid!')->view('emails.monetization-paid-dark');
    }
}
