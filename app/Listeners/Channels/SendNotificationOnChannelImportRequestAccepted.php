<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCreated;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\NewImportRequest;
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
                'channel' => ChannelMinimalItem::make($channel),
            ],
            get_class($channel),
            $channel->id
        ));

        return true;
    }
}
