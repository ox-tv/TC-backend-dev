<?php

namespace App\Listeners;

use App\Events\VideoCreated;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Models\Video;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnVideoCreated
{

    public function handle(VideoCreated $event)
    {
        $video = $event->video;
        $channel = $video->channel;

        if ($video->status == Video::STATUS_PUBLISHED){

            TCNotification::Send($channel->subscribers, new GeneralNotification(
                Notification::TYPE_NEW_VIDEO_PUBLISHED,
                Notification::SCOPE_TEXT[Notification::SCOPE_USER],
                [
                    'video' => VideoResource::make($video),
                    'channel' => ChannelResource::make($channel),
                ],
                [
                    'entity_type' => get_class($video),
                    'entity_id' => $video->id,
                ]
            ));
        }

        return true;
    }
}
