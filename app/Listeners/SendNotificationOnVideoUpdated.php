<?php

namespace App\Listeners;

use App\Events\VideoUpdated;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Models\Video;
use App\Notifications\NewVideoPublished;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnVideoUpdated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoUpdated $event)
    {
        $oldVideo = $event->oldVideo;
        $video = $event->video;
        $channel = $video->channel;

        if ($video->status == Video::STATUS_PUBLISHED && $oldVideo->status != Video::STATUS_PUBLISHED){

            TCNotification::send($channel->subscribers, new NewVideoPublished(
                Notification::SCOPE_TEXT[Notification::SCOPE_USER],
                Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                [
                    'video' => VideoResource::make($video),
                    'channel' => ChannelMinimalItem::make($channel),
                ],
                get_class($video),
                $video->id
            ));
        }

        return true;
    }
}
