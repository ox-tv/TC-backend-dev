<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelUpdated;
use App\Models\Channel;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnChannelUpdated
{

    public function handle(ChannelUpdated $event)
    {
        $oldChannel = $event->oldChannel;
        $channel = $event->channel;

        if(request()->is('api/admin/*') && !empty($channel->status) && $oldChannel->status != $channel->status){
            TCNotification::Send(collect([$channel->owner]), new GeneralNotification(
                Notification::TYPE_UPDATE_CHANNEL_STATUS,
                Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
                [
                    'prev_status' => Channel::STATUS_TEXT[$oldChannel->status],
                    'current_status' => Channel::STATUS_TEXT[$channel->status],
                ],
                [
                    'entity_type' => get_class($channel),
                    'entity_id' => $channel->id,
                ]
            ));
        }

        return true;
    }
}
