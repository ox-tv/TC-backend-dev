<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\Channels\ChannelImportRequestCreated;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Message\MessageItem;
use App\Mail\ImportRequestCompletedMail;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\ImportRequestCompleted;
use App\Notifications\NewImportRequest;
use App\Notifications\TCNotification\TCNotification;
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
