<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestCompleted;
use App\Mail\ImportRequestCompletedMail;
use Illuminate\Support\Facades\Mail;

class SendEmailOnChannelImportRequestCompleted
{

    public function handle(ChannelImportRequestCompleted $event)
    {
        $channel = $event->channel;

        Mail::to($channel->owner->email)
            ->queue(new ImportRequestCompletedMail());

        return true;
    }
}
