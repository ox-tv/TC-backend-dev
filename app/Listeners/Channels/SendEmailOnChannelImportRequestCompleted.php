<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\VideoViewed;
use App\Mail\ImportRequestCompletedMail;
use Illuminate\Support\Facades\Mail;

class SendEmailOnChannelImportRequestCompleted
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(ChannelImportRequestCompleted $event)
    {
        $channel = $event->channel;

        Mail::to($channel->owner->email)
            ->queue(new ImportRequestCompletedMail());

        return true;
    }
}
