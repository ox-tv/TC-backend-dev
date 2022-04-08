<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestRejected;
use App\Events\VideoViewed;
use App\Mail\PublisherRejectedMail;
use Illuminate\Support\Facades\Mail;

class SendEmailOnPublisherRequestRejected
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
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
