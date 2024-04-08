<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestRejected;
use App\Mail\PublisherRejectedMail;
use Illuminate\Support\Facades\Mail;

class SendEmailOnPublisherRequestRejected
{

    public function handle(PublisherRequestRejected $event)
    {
        $user = $event->user;
        $reason = $event->reason;

        $supportLink = config('PUBLISHER_SUPPORT_URL');

        Mail::to($user->email)
            ->queue(new PublisherRejectedMail($reason, $supportLink));

        return true;
    }
}
