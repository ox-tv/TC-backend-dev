<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelResource;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnChannelImportRequestCompleted
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

        TCNotification::Send(collect([$channel->owner]), new GeneralNotification(
            Notification::TYPE_IMPORT_REQUEST_COMPLETED,
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            ['channel' => ChannelResource::make($channel)],
            [
                'entity_type' => get_class($channel),
                'entity_id' => $channel->id,
            ]
        ));

        return true;
    }
}
