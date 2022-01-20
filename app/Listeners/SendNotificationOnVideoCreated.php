<?php

namespace App\Listeners;

use App\Events\VideoCreated;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\ChannelStatisticsDaily;
use App\Models\Notification;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use App\Notifications\NewVideoPublished;
use App\Notifications\TCNotification\TCNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationOnVideoCreated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoCreated $event)
    {
        $video = $event->video;
        $channel = $video->channel;

        if ($video->status == Video::STATUS_PUBLISHED){

            TCNotification::send($channel->subscribers, new NewVideoPublished(
                Notification::SCOPE_TEXT[Notification::SCOPE_USER],
                Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                [
                    'video' => VideoMinimalItem::make($video),
                    'channel' => ChannelMinimalItem::make($channel),
                ],
                get_class($video),
                $video->id
            ));
        }

        return true;
    }
}
