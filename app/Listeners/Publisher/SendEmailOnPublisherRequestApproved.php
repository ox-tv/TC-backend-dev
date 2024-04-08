<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestApproved;
use App\Mail\PublisherApprovedMail;
use Illuminate\Support\Facades\Mail;

class SendEmailOnPublisherRequestApproved
{

    public function handle(PublisherRequestApproved $event)
    {
        $user = $event->user;

        Mail::to($user->email)
            ->queue(new PublisherApprovedMail());

        return true;
    }
}
