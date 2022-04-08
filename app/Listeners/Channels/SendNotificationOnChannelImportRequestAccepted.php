<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelResource;
use App\Models\Notification;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnChannelImportRequestAccepted
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(ChannelImportRequestAccepted $event)
    {
        $channel = $event->channel;

        TCNotification::send(collect([$channel->owner]), new ImportRequestAccepted(
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'channel' => ChannelResource::make($channel),
            ],
            get_class($channel),
            $channel->id
        ));

        return true;
    }
}
