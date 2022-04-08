<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestApproved;
use App\Events\VideoViewed;
use App\Mail\PublisherApprovedMail;
use Illuminate\Support\Facades\Mail;

class SendEmailOnPublisherRequestApproved
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(PublisherRequestApproved $event)
    {
        $user = $event->user;

        Mail::to($user->email)
            ->queue(new PublisherApprovedMail());

        return true;
    }
}
