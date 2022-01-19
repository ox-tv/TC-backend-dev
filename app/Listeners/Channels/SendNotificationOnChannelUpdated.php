<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelUpdated;
use App\Events\VideoCreated;
use App\Events\VideoUpdated;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\Notification;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use App\Notifications\NewVideoPublished;
use App\Notifications\TCNotification\TCNotification;
use App\Notifications\UpdateChannelStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationOnChannelUpdated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(ChannelUpdated $event)
    {
        $oldChannel = $event->oldChannel;
        $channel = $event->channel;

        if(request()->is('api/admin/*') && !empty($channel->status) && $oldChannel->status != $channel->status){
            TCNotification::send(collect([$channel->owner]), new UpdateChannelStatus(
                Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
                Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                [
                    'prev_status' => Channel::STATUS_TEXT[$oldChannel->status],
                    'current_status' => Channel::STATUS_TEXT[$channel->status],
                ],
                get_class($channel),
                $channel->id
            ));
        }

        return true;
    }
}
